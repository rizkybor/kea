<?php

namespace Database\Seeders;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Career\Models\Company;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data demo Job Connector: perusahaan mitra (fiktif), lowongan, profil talent, dan satu lamaran berjalan.
 */
class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $companies = collect([
            ['Nusantara Fintech', 'Fintech', 'Jakarta Selatan', 'Platform pembayaran digital untuk UMKM.'],
            ['Kreasi Studio Digital', 'Agensi kreatif', 'Bandung', 'Agensi produk digital dan branding.'],
            ['Data Lintas Indonesia', 'Data & analitik', 'Yogyakarta', 'Konsultan data untuk retail dan logistik.'],
        ])->map(fn (array $c) => Company::create([
            'name' => $c[0], 'slug' => Str::slug($c[0]), 'industry' => $c[1], 'city' => $c[2],
            'description' => $c[3], 'website' => 'https://example.com/'.Str::slug($c[0]), 'is_verified' => true,
        ]));

        $partner = User::where('email', 'partner@kea.test')->firstOrFail();
        $partner->update(['company_id' => $companies[0]->id]);

        $category = fn (string $slug) => Category::where('slug', $slug)->value('id');

        $jobs = [
            [0, 'Junior Backend Engineer (Laravel)', 'teknologi', EmploymentType::FullTime, WorkMode::Hybrid, 'Jakarta Selatan', 7_000_000, 10_000_000, true, ['Laravel', 'PostgreSQL', 'REST API', 'Git'], true],
            [0, 'Frontend Developer (Vue/Nuxt)', 'teknologi', EmploymentType::FullTime, WorkMode::Remote, 'Remote', 7_500_000, 11_000_000, false, ['Vue', 'Nuxt', 'Tailwind', 'TypeScript'], true],
            [1, 'UI/UX Designer', 'desain', EmploymentType::FullTime, WorkMode::Onsite, 'Bandung', 6_000_000, 9_000_000, true, ['Figma', 'User Research', 'Design System'], true],
            [1, 'Digital Marketing Intern', 'marketing', EmploymentType::Internship, WorkMode::Hybrid, 'Bandung', 2_500_000, 3_500_000, true, ['SEO', 'Content', 'Meta Ads'], false],
            [2, 'Data Analyst', 'data', EmploymentType::Contract, WorkMode::Remote, 'Remote', 8_000_000, 12_000_000, false, ['SQL', 'Python', 'Looker Studio'], true],
        ];

        foreach ($jobs as [$c, $title, $cat, $type, $mode, $location, $min, $max, $show, $skills, $alumniOnly]) {
            $companies[$c]->jobPostings()->create([
                'slug' => Str::slug($title.' '.$companies[$c]->name),
                'title' => $title,
                'category_id' => $category($cat),
                'posted_by' => $c === 0 ? $partner->id : null,
                'employment_type' => $type,
                'work_mode' => $mode,
                'location' => $location,
                'salary_min' => $min,
                'salary_max' => $max,
                'show_salary' => $show,
                'description' => "Kami mencari {$title} untuk bergabung dengan tim {$companies[$c]->name}. Kamu akan bekerja langsung dengan tim produk untuk membangun fitur yang dipakai ribuan pengguna.",
                'requirements' => ['Lulusan bootcamp/SMK/S1 semua jurusan', 'Punya portofolio minimal 2 proyek', 'Komunikatif dan mau belajar', 'Menguasai '.implode(', ', array_slice($skills, 0, 2))],
                'skills' => $skills,
                'alumni_only' => $alumniOnly,
                'status' => JobStatus::Open,
                'published_at' => now()->subDays(random_int(1, 10)),
                'closes_at' => now()->addWeeks(4),
            ]);
        }

        // Alumni masuk talent pool (consent) dengan CV, dan satu lamaran yang sudah sampai tahap interview.
        $alumni = User::where('email', 'alumni@kea.test')->firstOrFail();
        Storage::disk('local')->put('cv/demo-bima.pdf', self::placeholderPdf('CV Bima Santoso'));
        $alumni->update([
            'city' => 'Jakarta Timur',
            'skills' => ['Laravel', 'PostgreSQL', 'Vue', 'Nuxt', 'Tailwind', 'Git'],
            'open_to_work' => true,
            'talent_consent_at' => now()->subMonth(),
            'portfolio_url' => 'https://github.com/bima-demo',
            'linkedin_url' => 'https://www.linkedin.com/in/bima-demo',
            'bio' => 'Alumni Fullstack Web Development KEA. Fokus di backend Laravel dan suka merapikan API.',
            'cv_path' => 'cv/demo-bima.pdf',
        ]);

        $job = $companies[0]->jobPostings()->where('title', 'like', 'Junior Backend%')->firstOrFail();
        Storage::disk('local')->copy('cv/demo-bima.pdf', 'applications/demo-bima.pdf');
        $application = JobApplication::create([
            'job_posting_id' => $job->id, 'user_id' => $alumni->id, 'status' => ApplicationStatus::Applied,
            'cover_letter' => 'Saya alumni bootcamp Fullstack KEA dengan final project aplikasi booking berbasis Laravel + Nuxt.',
            'cv_path' => 'applications/demo-bima.pdf', 'portfolio_url' => $alumni->portfolio_url,
        ]);
        $application->forceFill(['created_at' => now()->subDays(6)])->save();
        $application->events()->create(['status' => ApplicationStatus::Applied, 'actor_id' => $alumni->id, 'created_at' => now()->subDays(6)]);
        $application->events()->create(['status' => ApplicationStatus::Reviewing, 'actor_id' => $partner->id, 'created_at' => now()->subDays(4)]);
        $application->events()->create(['status' => ApplicationStatus::Interview, 'actor_id' => $partner->id, 'note' => 'Interview user Kamis 10.00 WIB via Google Meet.', 'created_at' => now()->subDays(2)]);
        $application->update(['status' => ApplicationStatus::Interview]);

        User::where('email', 'learner@kea.test')->update(['skills' => json_encode(['HTML', 'CSS', 'Laravel']), 'city' => 'Depok']);
    }

    /**
     * PDF satu halaman minimal untuk data demo.
     */
    private static function placeholderPdf(string $text): string
    {
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            .'4 0 obj<</Length '.strlen($stream).">>stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }
}

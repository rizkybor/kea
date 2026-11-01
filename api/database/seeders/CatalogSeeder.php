<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\LessonType;
use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Program;
use App\Modules\Catalog\Models\Testimonial;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Teknologi', 'Desain', 'Marketing', 'Data', 'Bisnis & HR', 'Karier'])
            ->mapWithKeys(fn (string $name, int $i) => [$name => Category::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $i])]);

        $mentors = User::role('mentor')->orderBy('id')->get();

        $bootcampFaqs = [
            ['q' => 'Apa syarat garansi kerja?', 'a' => 'Kehadiran live minimal 80%, semua tugas wajib bernilai minimal 70, final project lulus review, dan aktif melamar minimal 30 lowongan dalam 6 bulan setelah lulus.'],
            ['q' => 'Bagaimana kalau saya ketinggalan sesi live?', 'a' => 'Rekaman tersedia di Ruang Kelas setelah sesi selesai. Kehadiran tetap dihitung dari sesi live.'],
            ['q' => 'Apakah ada cicilan?', 'a' => 'Ada. Pilih metode Kartu Kredit / Cicilan atau paylater saat checkout.'],
            ['q' => 'Butuh laptop spesifikasi apa?', 'a' => 'Minimal RAM 8 GB, prosesor setara Intel i5 generasi 8, dan koneksi internet stabil.'],
        ];
        $bootcampBenefits = [
            ['title' => '24 sesi live', 'body' => 'Rekaman tersedia setelah sesi.'],
            ['title' => '3 proyek portofolio', 'body' => 'Direview mentor praktisi.'],
            ['title' => 'Sertifikat terverifikasi', 'body' => 'Kode & QR bisa dicek recruiter.'],
            ['title' => 'Career support', 'body' => 'Review CV, mock interview, job connector.'],
        ];

        $programs = [
            ['fullstack-web-development', 'FSWD', 'Fullstack Web Development', ProgramType::Bootcamp, 'Teknologi', 7_500_000, 4_990_000, true,
                'Bangun aplikasi web end-to-end dengan Laravel, Vue/Nuxt, dan PostgreSQL. Lulus dengan portofolio 3 proyek dan akses ke mitra hiring.', '4 bulan', 'Live online', 4.8, 312],
            ['ui-ux-design-product', 'UIUX', 'UI/UX Design & Product', ProgramType::Bootcamp, 'Desain', 6_500_000, 4_490_000, true,
                'Riset pengguna, wireframe, prototyping di Figma, dan design system. Lulus dengan 3 studi kasus portofolio.', '3 bulan', 'Live online', 4.9, 268],
            ['digital-marketing', 'DM', 'Digital Marketing', ProgramType::Bootcamp, 'Marketing', 5_500_000, 3_990_000, true,
                'Strategi konten, SEO, ads, dan analitik kampanye bersama praktisi agensi dan brand.', '3 bulan', 'Live online', 4.7, 401],
            ['data-analytics', 'DA', 'Data Analytics', ProgramType::Bootcamp, 'Data', 6_900_000, 4_790_000, true,
                'SQL, spreadsheet lanjutan, Python, dan dashboard BI untuk pengambilan keputusan bisnis.', '3 bulan', 'Live online', 4.8, 220],
            ['human-resources-management', 'HRM', 'Human Resources Management', ProgramType::Bootcamp, 'Bisnis & HR', 4_900_000, 3_490_000, false,
                'Rekrutmen, people analytics, payroll, dan hubungan industrial sesuai regulasi Indonesia.', '2 bulan', 'Live online', 4.6, 154],
            ['figma-auto-layout-design-system', 'FIGMA', 'Figma Auto Layout & Design System', ProgramType::ShortClass, 'Desain', 750_000, 499_000, false,
                'Kelas intensif 3 sesi untuk menguasai auto layout, komponen, dan variables di Figma.', '1 minggu', '3 sesi · Live', 4.9, 98],
            ['laravel-untuk-pemula', 'LRV', 'Laravel untuk Pemula', ProgramType::VideoCourse, 'Teknologi', 399_000, 249_000, false,
                'Belajar Laravel dari nol: routing, Eloquent, autentikasi, sampai deploy. Akses selamanya.', 'Self-paced', 'Akses selamanya', 4.7, 530],
            ['seo-fundamental', 'SEO', 'SEO Fundamental', ProgramType::VideoCourse, 'Marketing', 349_000, 199_000, false,
                'Riset keyword, on-page, technical SEO, dan pengukuran di Google Search Console.', 'Self-paced', 'Akses selamanya', 4.6, 610],
            ['webinar-memulai-karier-di-tech', 'WEB', 'Webinar: Memulai Karier di Tech', ProgramType::Event, 'Karier', 0, null, false,
                'Sesi sharing gratis bersama praktisi tentang jalur karier, portofolio, dan proses rekrutmen di perusahaan teknologi.', '1 sesi', 'Online', null, 0],
        ];

        foreach ($programs as $i => [$slug, $code, $title, $type, $category, $price, $promo, $guarantee, $summary, $duration, $format, $rating, $ratingCount]) {
            $program = Program::create([
                'category_id' => $categories[$category]->id,
                'type' => $type,
                'code' => $code,
                'slug' => $slug,
                'title' => $title,
                'summary' => $summary,
                'description' => $summary,
                'level' => 'pemula',
                'duration_label' => $duration,
                'format_label' => $format,
                'price' => $price,
                'promo_price' => $promo,
                'installment_from' => $type === ProgramType::Bootcamp ? (int) ceil(($promo ?? $price) / 12 / 1000) * 1000 : null,
                'has_job_guarantee' => $guarantee,
                'benefits' => $type === ProgramType::Bootcamp ? $bootcampBenefits : null,
                'faqs' => $type === ProgramType::Bootcamp ? $bootcampFaqs : [['q' => 'Apakah dapat sertifikat?', 'a' => 'Ya, sertifikat terverifikasi diterbitkan setelah semua materi selesai.']],
                'rating' => $rating,
                'rating_count' => $ratingCount,
                'enrolled_count' => $ratingCount * 2,
                'is_published' => true,
            ]);

            $this->seedCurriculum($program);

            if ($type->requiresCohort()) {
                $this->seedCohorts($program, $mentors, $i);
            }
        }

        $testimonials = [
            ['Ayu Lestari', 'Frontend Developer', 'Perusahaan E-commerce', 'Final project di KEA jadi bahan utama waktu interview. Mentor review kodenya detail banget, bukan cuma "sudah bagus".'],
            ['Rizal Hakim', 'UI/UX Designer', 'Startup Edutech', 'Saya career switcher dari akuntansi. Struktur tugas mingguan bikin saya konsisten, dan portofolio saya selesai sebelum lulus.'],
            ['Maya Anggraini', 'Digital Marketing Specialist', 'Agensi Kreatif', 'Materinya langsung dipakai di kerjaan. Sesi mock interview dari career team juga sangat membantu.'],
        ];
        foreach ($testimonials as $i => [$name, $role, $company, $quote]) {
            Testimonial::create([
                'program_id' => Program::where('type', ProgramType::Bootcamp)->orderBy('id')->skip($i)->value('id'),
                'name' => $name, 'role' => $role, 'company' => $company, 'quote' => $quote,
            ]);
        }
    }

    private function seedCurriculum(Program $program): void
    {
        $modules = $program->slug === 'fullstack-web-development'
            ? [
                ['Modul 1 · Fondasi Web & Git', ['HTML semantik & aksesibilitas', 'CSS modern & Tailwind', 'Git workflow tim'], [
                    ['HTML Semantik & Aksesibilitas', LessonType::Video, 20],
                    ['CSS Modern & Tailwind', LessonType::Video, 24],
                    ['Git Workflow Tim', LessonType::Video, 18, 'Tugas 1 · Landing Page Responsif', 7],
                ]],
                ['Modul 2 · JavaScript & Vue/Nuxt', ['JavaScript modern', 'Komponen Vue', 'Nuxt SSR & routing'], [
                    ['JavaScript Modern (ES2023)', LessonType::Video, 26],
                    ['Komponen & State di Vue', LessonType::Video, 28],
                    ['Kuis: JavaScript & Vue', LessonType::Quiz, null],
                    ['Nuxt: SSR, Routing & Data Fetching', LessonType::Live, 120, 'Tugas 2 · Katalog dengan Nuxt', 21],
                ]],
                ['Modul 3 · Laravel REST API & Database', ['Routing & controller', 'Eloquent & relasi', 'Auth Sanctum', 'CRUD API'], [
                    ['Routing & Controller', LessonType::Video, 18],
                    ['Migration & Seeder', LessonType::Video, 22],
                    ['Relasi Eloquent', LessonType::Video, 25],
                    ['Auth dengan Sanctum', LessonType::Live, 120],
                    ['Membangun CRUD API', LessonType::Video, 30, 'Tugas 3 · CRUD API', 42],
                ]],
                ['Modul 4 · Deployment & Final Project', ['Nginx + PHP-FPM', 'CI/CD', 'Final project'], [
                    ['REST API Best Practice', LessonType::Live, 120],
                    ['Deploy ke VPS (Nginx + Supervisor)', LessonType::Video, 30],
                    ['Final Project: Aplikasi Fullstack', LessonType::Assignment, null, 'Final Project · Aplikasi Fullstack', 70, true],
                ]],
            ]
            : [
                ['Modul 1 · Fondasi', ['Konsep dasar', 'Tools yang dipakai industri'], [
                    ['Pengenalan & Konsep Dasar', LessonType::Video, 15],
                    ['Tools Industri', LessonType::Video, 20],
                    ['Kuis: Fondasi', LessonType::Quiz, null],
                ]],
                ['Modul 2 · Praktik', ['Studi kasus', 'Latihan terbimbing'], [
                    ['Studi Kasus Industri', LessonType::Video, 25],
                    ['Latihan Terbimbing', LessonType::Article, null, 'Tugas Praktik', 14],
                ]],
            ];

        foreach ($modules as $s => [$title, $topics, $lessons]) {
            $section = $program->sections()->create(['title' => $title, 'position' => $s + 1, 'topics' => $topics]);

            foreach ($lessons as $l => $lesson) {
                [$lessonTitle, $type, $duration] = $lesson;
                $section->lessons()->create([
                    'title' => $lessonTitle,
                    'type' => $type,
                    'position' => $l + 1,
                    'duration_minutes' => $duration,
                    'summary' => "Ringkasan materi {$lessonTitle}: poin penting, contoh kode/kasus, dan link resource tambahan.",
                    'is_preview' => $s === 0 && $l === 0,
                    'assignment_title' => $lesson[3] ?? null,
                    'assignment_instructions' => isset($lesson[3])
                        ? "Kerjakan {$lesson[3]} sesuai brief. Kumpulkan link repository/Figma atau file. Rubrik: fungsionalitas 40%, kualitas kode/desain 30%, dokumentasi 20%, ketepatan waktu 10%."
                        : null,
                    'assignment_due_day' => $lesson[4] ?? null,
                    'is_final_project' => $lesson[5] ?? false,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $mentors
     */
    private function seedCohorts(Program $program, $mentors, int $offset): void
    {
        $batches = $program->type === ProgramType::Bootcamp
            ? [
                ['Batch 11 · Weekday', 'weekday', 'Senin & Kamis, 19.00–21.00 WIB', -35, 30, 30],
                ['Batch 12 · Weekday', 'weekday', 'Senin & Kamis, 19.00–21.00 WIB', 10 + $offset, 30, 27],
                ['Batch 12 · Weekend', 'weekend', 'Sabtu, 09.00–12.00 WIB', 17 + $offset, 30, 8],
                ['Batch 11 · Weekend', 'weekend', 'Sabtu, 09.00–12.00 WIB', 5 + $offset, 25, 25],
            ]
            : [['Batch 1', $program->type === ProgramType::Event ? 'weekday' : 'weekend', 'Online', 7 + $offset, $program->type === ProgramType::Event ? 500 : 40, 12]];

        foreach ($batches as [$name, $schedule, $label, $startDays, $quota, $taken]) {
            $startsAt = now('Asia/Jakarta')->addDays($startDays)->setTime($schedule === 'weekend' ? 9 : 19, 0)->utc();

            $cohort = $program->cohorts()->create([
                'name' => $name,
                'schedule_type' => $schedule,
                'schedule_label' => $label,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonths(3),
                'quota' => $quota,
                'seats_taken' => $taken,
                'community_url' => 'https://chat.whatsapp.com/kea-demo',
            ]);

            $cohort->mentors()->attach($mentors->take(2)->pluck('id'));
        }
    }
}

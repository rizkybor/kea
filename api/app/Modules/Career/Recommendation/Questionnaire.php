<?php

namespace App\Modules\Career\Recommendation;

use App\Modules\Career\Enums\CareerTrack;
use Illuminate\Validation\Rule;

/**
 * Kuesioner Mission Based Learning (LMS-08): minat, target karier, kondisi, dan preferensi belajar.
 */
class Questionnaire
{
    public const UNSURE = 'unsure';

    /**
     * Minat → target karier yang relevan. Track pertama = paling relevan (bobot penuh), sisanya pendukung.
     *
     * @var array<string, list<CareerTrack>>
     */
    public const INTEREST_TRACKS = [
        'build' => [CareerTrack::SoftwareEngineer, CareerTrack::FrontendDeveloper],
        'design' => [CareerTrack::UiUxDesigner, CareerTrack::FrontendDeveloper],
        'data' => [CareerTrack::DataAnalyst],
        'promote' => [CareerTrack::DigitalMarketer],
        'people' => [CareerTrack::HrProfessional],
    ];

    /**
     * Status yang tujuannya mendapat pekerjaan: jalur utama diarahkan ke bootcamp bergaransi.
     */
    public const JOB_SEEKING = ['student', 'fresh_graduate', 'career_switcher'];

    /**
     * @return list<array{id: string, title: string, hint?: string, type: 'single'|'multi', max?: int, options: list<array{value: string, label: string, hint?: string}>}>
     */
    public static function questions(): array
    {
        return [
            [
                'id' => 'target_track',
                'title' => 'Mau berkarier sebagai apa?',
                'type' => 'single',
                'options' => [
                    ...array_map(fn (CareerTrack $t) => ['value' => $t->value, 'label' => $t->label()], CareerTrack::cases()),
                    ['value' => self::UNSURE, 'label' => 'Belum tahu, bantu saya memilih'],
                ],
            ],
            [
                'id' => 'interests',
                'title' => 'Aktivitas apa yang paling kamu nikmati?',
                'hint' => 'Pilih maksimal 3.',
                'type' => 'multi',
                'max' => 3,
                'options' => [
                    ['value' => 'build', 'label' => 'Membangun aplikasi atau website'],
                    ['value' => 'design', 'label' => 'Mendesain tampilan & pengalaman pengguna'],
                    ['value' => 'data', 'label' => 'Mengolah angka & menemukan insight dari data'],
                    ['value' => 'promote', 'label' => 'Membuat konten & mempromosikan produk'],
                    ['value' => 'people', 'label' => 'Mengelola orang & organisasi'],
                ],
            ],
            [
                'id' => 'experience',
                'title' => 'Seberapa jauh pengalamanmu di bidang ini?',
                'type' => 'single',
                'options' => [
                    ['value' => 'none', 'label' => 'Benar-benar mulai dari nol'],
                    ['value' => 'self_taught', 'label' => 'Pernah belajar sendiri (YouTube, kursus singkat)'],
                    ['value' => 'related_job', 'label' => 'Sudah pernah kerja/proyek di bidang terkait'],
                ],
            ],
            [
                'id' => 'status',
                'title' => 'Kondisimu saat ini?',
                'type' => 'single',
                'options' => [
                    ['value' => 'student', 'label' => 'Mahasiswa tingkat akhir'],
                    ['value' => 'fresh_graduate', 'label' => 'Fresh graduate'],
                    ['value' => 'career_switcher', 'label' => 'Ingin pindah karier'],
                    ['value' => 'employed', 'label' => 'Sudah bekerja, ingin naik level'],
                ],
            ],
            [
                'id' => 'availability',
                'title' => 'Kapan kamu bisa belajar?',
                'type' => 'single',
                'options' => [
                    ['value' => 'weekday', 'label' => 'Weekday malam'],
                    ['value' => 'weekend', 'label' => 'Weekend'],
                    ['value' => 'flexible', 'label' => 'Fleksibel, sesuai waktu luang'],
                ],
            ],
            [
                'id' => 'learning_style',
                'title' => 'Cara belajar yang paling cocok?',
                'type' => 'single',
                'options' => [
                    ['value' => 'live', 'label' => 'Live bareng mentor & teman se-cohort', 'hint' => 'Terjadwal, ada tugas mingguan'],
                    ['value' => 'self_paced', 'label' => 'Mandiri lewat video', 'hint' => 'Bebas waktu, akses selamanya'],
                    ['value' => 'mixed', 'label' => 'Campuran keduanya'],
                ],
            ],
            [
                'id' => 'budget',
                'title' => 'Budget belajar yang kamu siapkan?',
                'type' => 'single',
                'options' => [
                    ['value' => 'low', 'label' => 'Di bawah Rp500 ribu'],
                    ['value' => 'mid', 'label' => 'Rp500 ribu – Rp3 juta', 'hint' => 'Bisa dicicil'],
                    ['value' => 'high', 'label' => 'Di atas Rp3 juta'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = ['answers' => ['required', 'array']];

        foreach (self::questions() as $q) {
            $values = array_column($q['options'], 'value');
            $key = "answers.{$q['id']}";

            if ($q['type'] === 'multi') {
                $rules[$key] = ['required', 'array', 'min:1', 'max:'.$q['max']];
                $rules["{$key}.*"] = [Rule::in($values)];
            } else {
                $rules[$key] = ['required', Rule::in($values)];
            }
        }

        return $rules;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LandingContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newsPosts = [
            [
                'title' => 'Peluncuran Resmi Portal Alumni Universitas Bumigora',
                'excerpt' => 'Portal alumni resmi diluncurkan untuk memperkuat komunikasi lintas angkatan dan jurusan.',
                'content' => 'Universitas Bumigora meluncurkan portal alumni terintegrasi sebagai media pembaruan data, kolaborasi profesional, dan publikasi kegiatan resmi kampus.',
                'published_at' => now()->subDays(10),
                'is_published' => true,
            ],
            [
                'title' => 'Program Mentoring Karier Alumni untuk Mahasiswa Tingkat Akhir',
                'excerpt' => 'Alumni dari berbagai sektor industri berbagi pengalaman dalam sesi mentoring karier.',
                'content' => 'Program mentoring ini menjadi jembatan antara alumni dan mahasiswa untuk meningkatkan kesiapan kerja serta wawasan dunia profesional.',
                'published_at' => now()->subDays(5),
                'is_published' => true,
            ],
            [
                'title' => 'Bumigora Career Connect 2026 Dibuka untuk Seluruh Alumni',
                'excerpt' => 'Kegiatan career connect menghadirkan mitra industri nasional untuk rekrutmen dan networking.',
                'content' => 'Kegiatan ini mencakup sesi rekrutmen, klinik CV, dan diskusi peluang karier yang terbuka bagi alumni Universitas Bumigora.',
                'published_at' => now()->subDays(2),
                'is_published' => true,
            ],
        ];

        foreach ($newsPosts as $news) {
            NewsPost::query()->updateOrCreate(
                ['slug' => Str::slug($news['title'])],
                [...$news, 'slug' => Str::slug($news['title'])],
            );
        }

        $events = [
            [
                'title' => 'Reuni Akbar Alumni Universitas Bumigora',
                'description' => 'Silaturahmi lintas angkatan, networking, dan pemaparan roadmap pengembangan alumni kampus.',
                'event_date' => now()->addWeeks(3)->toDateString(),
                'location' => 'Aula Utama Universitas Bumigora',
                'registration_url' => null,
                'is_published' => true,
            ],
            [
                'title' => 'Webinar Alumni Talk: Karier di Industri Digital',
                'description' => 'Sesi berbagi pengalaman dari alumni yang berkarier di perusahaan teknologi nasional.',
                'event_date' => now()->addWeeks(5)->toDateString(),
                'location' => 'Online (Zoom)',
                'registration_url' => null,
                'is_published' => true,
            ],
            [
                'title' => 'Bumigora Alumni Innovation Meetup',
                'description' => 'Forum kolaborasi inovasi antara alumni, dosen, dan mitra industri.',
                'event_date' => now()->addWeeks(7)->toDateString(),
                'location' => 'Innovation Hub Universitas Bumigora',
                'registration_url' => null,
                'is_published' => true,
            ],
        ];

        foreach ($events as $event) {
            Event::query()->updateOrCreate(
                ['slug' => Str::slug($event['title'])],
                [...$event, 'slug' => Str::slug($event['title'])],
            );
        }
    }
}

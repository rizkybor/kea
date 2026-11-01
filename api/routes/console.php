<?php

use Illuminate\Support\Facades\Schedule;

/*
| Jadwal sesuai blueprint §8. Jalankan `php artisan schedule:work` (Supervisor di worker-01).
*/
Schedule::command('orders:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('payments:reconcile')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('enrollments:evaluate-completion')->dailyAt('03:00')->timezone('Asia/Jakarta')->withoutOverlapping();

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ShortSchedule\ShortSchedule;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //查询代币价格
        $schedule->command('sync:tokenprice')->cron('*/2 * * * *');
        //检查昨日签到
        $schedule->command('CheckYdaySign')->cron('0 0 * * *');
        //手续费分红
//         $schedule->command('FeeDividend')->cron('5 0 * * *');
        
        //池子奖励 每日0点10分分
//         $schedule->command('SyncPoolReward')->cron('10 0 * * *');
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

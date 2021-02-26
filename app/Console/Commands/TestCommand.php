<?php

namespace App\Console\Commands;

use App\Crawler\Entities\Quiz;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'test:run';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Test something';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        for ($i = 110000; $i<=120000; $i++)
        {
            dump("running : " .$i);
//            $this->call('khoahoc_vietjack', [
//                '--id' => $i, '--data-dir' => 'storage/app/public/khvietjack'
//            ]);

            $this->call('hoc247net', [
                '--id' => $i, '--site' => 'hoc247net'
            ]);

           /* $this->call('loigiaihay', [
                '--id' => $i, '--data-dir' => 'data/loigiaihay'
            ]);*/
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

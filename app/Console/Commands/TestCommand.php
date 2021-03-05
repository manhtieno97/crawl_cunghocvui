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
        $this->call('loigiaihay', [
            '--id' => 50032, '--site' => 'loigiaihay'
        ]);
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

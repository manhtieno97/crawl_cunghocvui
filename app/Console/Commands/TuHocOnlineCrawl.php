<?php

namespace App\Console\Commands;

use App\Sites\TuHocOnline\Spider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;

class TuHocOnlineCrawl extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'tuhoconline {--f|force : force overwrite files}{--reset : reset stack} {--resume : resume stack} {--d|data-dir= : Location of crawled data}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Crawl từ tuhoconline.net';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data_dir = $this->option('data-dir');
        $reset = $this->option('reset');
        $resume = $this->option('resume');
        $force = $this->option('force');
        $spider = new Spider( $data_dir, $force );
        $spider->run($reset, $resume);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {

    }
}

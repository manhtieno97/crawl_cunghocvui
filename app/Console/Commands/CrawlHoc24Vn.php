<?php

namespace App\Console\Commands;

use App\Sites\Hoc24Vn\QuizCrawler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;

class CrawlHoc24Vn extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'hoc24vn {--id= : Id of quiz} {--f|force : force re-crawl} {--d|data-dir= : Location of crawled data}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Crawl from hoc24.vn';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->option('id');
        $data_dir = $this->option('data-dir');
        $force = $this->option('force');
        $crawler = new QuizCrawler($data_dir, $force);
        if(strpos( $id, "-")){
            list( $id, $max) = explode( "-", $id, 2);
            for(;$id <= $max; $id++){
                $crawler->process( $id );
            }
        }else{
            $ids = explode( ",", $id);
            foreach ($ids as $id){
                $crawler->process( $id );
            }
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

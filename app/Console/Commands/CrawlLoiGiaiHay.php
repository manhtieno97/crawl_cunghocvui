<?php

namespace App\Console\Commands;

use App\Sites\LoiGiaiHay\QuizCrawler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;

class CrawlLoiGiaiHay extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'loigiaihay {--id= : Id of quiz} {--f|force : force re-crawl} {--d|site= : Location of crawled data}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Crawl from loigiaihay.com';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->option('id');
        $site = $this->option('site');
        $force = $this->option('force');
        $crawler = new QuizCrawler($site, $force);
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

<?php

namespace App\Console\Commands;

use App\Sites\VnJpClub\Jls\SimpleQuizCrawler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;

class VnJpClubJls extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'vnjpclub:jls:simple
    {--u|url= : Url trang crawl, trang này chứa list các bài kiểm tra}
    {--d|data-dir= : Thu muc luu tru cau hoi}
    {--f|force : force re-crawl}
    {--k|keywords= : Keywords mặc địịnh, phân cách bằng dấu (,)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Crawl từ trang http://jls.vnjpclub.com/';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->option('url');
        $keywords = $this->option('keywords');
        $keywords = preg_split( "/,|;/", $keywords);
        $data_dir = $this->option('data-dir');
        $force = $this->option('force');
        $crawler = new SimpleQuizCrawler( $url, $keywords, $force );
        $crawler->startCrawling($data_dir);
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

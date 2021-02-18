<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class CrawlController extends Controller
{
    const id_max_default = 10000000;
    const id_min_default = 1;

    public function getCrawl()
    {
        return view('admin.crawl_by_id');
    }


    public function postCrawl(Request $request)
    {
        if(!empty($request->site))
        {
            if(!empty($request->id_from) && !empty($request->id_to) && ((int)$request->id_from < (int)$request->id_to))
            {
                for ($i = (int)$request->id_from; $i <= (int)$request->id_to; $i++)
                {
                    dump('crawl id : '.$i .' site: '.$request->site);
                    \Artisan::call($request->site, ['--id' => $i, '--site' => $request->site]);
                }
            }else{
                for ($i = self::id_min_default; $i <= self::id_max_default; $i++)
                {
                    dump('crawl id : '.$i .' site: '.$request->site);
                    \Artisan::call($request->site, ['--id' => $i, '--site' => $request->site]);
                }
            }
            return redirect('admin/question');
        }
        return redirect('admin/crawl-id');
    }
    public function getUpload()
    {
        return view('admin.upload_data');
    }
    public function postUpload(Request $request)
    {
        if(!empty($request->site))
        {

        }else{
            DB::table('questions')->where('status', Question::STATUS_QUESTION_DEFAULT)
                ->chunkById(100, function ($questions) {
                    foreach ($questions as $question) {
                        if (\Storage::disk($question->disk)->exists($question->file)) {
                            $contents = json_decode(\Storage::disk($question->disk)->get($question->file),true);
                            $api = "http://dev.cunghocvui.com/api/creawl/add-post";
                            $client = new \GuzzleHttp\Client();
                            $data = [];
                            $data['hash'] = '2fb1177038d055e19779d1588deaa2c1';
                            $data['time'] = 2131231;
                            $data['data'] = $contents;
                            try {
                                $response = $client->request('POST', 'http://dev.cunghocvui.com/api/creawl/add-post', [
                                    'headers' => [
                                        'Accept'     => 'application/json',
                                    ],
                                    'form_params' => $data
                                ]);
                                if(isset($response) && json_decode($response->getBody()->getContents(),true)['status'] == true)
                                {
                                    Question::where('id', $question->id)->update(['status' => Question::STATUS_QUESTION_UPLOAD]);
                                }else{
                                    Question::where('id', $question->id)->update(['status' => Question::STATUS_QUESTION_ERROR]);
                                }
                            }catch (\Exception $ex )
                            {
                                Question::where('id', $question->id)->update(['status' => Question::STATUS_QUESTION_ERROR]);
                            }

                        }
                    }
                });
        }
    }
}

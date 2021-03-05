<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use http\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;
use Carbon\Carbon;
class CrawlController extends Controller
{
    const id_max_default = 100;
    const id_min_default = 1;

    public function getCrawl()
    {
        return view('admin.crawl_by_id');
    }

    public function postCrawl(Request $request)
    {
        if(!empty($request->site))
        {
            $id_from = self::id_min_default;
            $id_to = self::id_max_default;
            if(!empty($request->id_from) && !empty($request->id_to) && ((int)$request->id_from < (int)$request->id_to))
            {
                $id_from = (int)$request->id_from;
                $id_to = (int)$request->id_to;
            }
            for ($i = $id_from; $i <= $id_to; $i++)
            {
                dump('crawl id : '.$i .' site: '.$request->site);
                \Artisan::call($request->site, ['--id' => $i, '--site' => $request->site]);
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
        $status = Question::STATUS_QUESTION_DEFAULT;
        if(!empty($request->status))
        {
            $status = (int)$request->status;
        }
        $data = DB::table('questions')->where('status', $status);
        if(!empty($request->site))
        {
            $data = $data->where('site', $request->site);
        }
        $data->chunkById(100, function ($questions) {
            foreach ($questions as $question) {
                if (\Storage::disk($question->disk)->exists($question->file)) {
                    $contents = json_decode(\Storage::disk($question->disk)->get($question->file),true);
                    $api = config('crawl.api');
                    $client = new \GuzzleHttp\Client();
                    $data = [];
                    $data['time'] = strtotime(Carbon::now());
                    $data['data'] = $contents;
                    $data['hash'] = $this->getHash($contents, $data['time']);

                    try {
                        $response = $client->request('POST', $api, [
                            'headers' => [
                                'Accept'     => 'application/json',
                            ],
                            'form_params' => $data
                        ]);
                        $repon = json_decode($response->getBody()->getContents(),true);
                        if(isset($response) && $repon['status'] == true)
                        {
                            Question::where('id', $question->id)->update([
                                'status' => Question::STATUS_QUESTION_UPLOAD,
                                'id_post' => $repon['id_post']
                            ]);

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
        return redirect('admin/question');
    }
    public function getHash($data ,$time)
    {
        $hash = '';
        if(!empty($data['content']))
        {
            $hash = config('crawl.private_key').$data['content'] . $time;
        }
        return md5($hash);
    }
}

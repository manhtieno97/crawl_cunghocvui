<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use CrudTrait;

    const STATUS_QUESTION_DEFAULT = 0;
    const STATUS_QUESTION_UPLOAD = 1;
    const STATUS_QUESTION_ERROR = 2;
    const STATUS_QUESTION_PUBLIC = 3;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'questions';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['album', 'link', 'question', 'answer', 'type', 'disk', 'file', 'site', 'status','id_post'];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function getStatuses()
    {
        return [
            self::STATUS_QUESTION_DEFAULT => "Default",
            self::STATUS_QUESTION_UPLOAD => "Uploaded",
            self::STATUS_QUESTION_ERROR => "Error",
            self::STATUS_QUESTION_PUBLIC => "Public",
        ];
    }
    public static function getSite()
    {
        $data = [];
        $sites = \Setting::get('site');
        if(!empty($sites))
        {
            $sites = json_decode($sites,true);
            foreach ($sites as $key => $site)
            {
                $data[trim($site['key'],' ')] = $site['name'];
            }
        }
        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}

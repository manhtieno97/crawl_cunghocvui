<?php

use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    protected $settings = [
        [
            'key'           => 'site',
            'name'          => 'Trang crawl',
            'description'   => '',
            'value'         => '[{"name":"Học 247","key":"hoc247","address":"https://hoc247.net/"}]',
            'field'         => '{"name":"value","label":"Value","type":"table","entity_singular":"option","columns":{"name":"Tên site","key":"Key word","address":"Địa chỉ"}}',
            'active'        => 1,
        ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->settings as $index => $setting) {
            DB::table('settings')->insert($setting);
        }
    }
}

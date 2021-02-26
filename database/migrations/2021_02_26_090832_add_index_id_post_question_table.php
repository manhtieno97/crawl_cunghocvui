<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexIdPostQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->index('id_post', 'questions_id_post_index');
            $table->index('site', 'questions_site_index');
            $table->index('status', 'questions_status_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_id_post_index');
            $table->dropIndex('questions_site_index');
            $table->dropIndex('questions_status_index');
        });
    }
}

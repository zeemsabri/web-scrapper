<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
    Schema::create('deals', function (Blueprint $table) {
        $table->id();
        $table->integer('pipe_drive_deal_id')->default(0);
        $table->text('deals_title');
        $table->string('deals_value', 244)->nullable();
        $table->json('deals_label')->nullable();
        $table->string('deals_currency', 10)->nullable();
        $table->integer('user_id')->nullable();
        $table->integer('person_id')->nullable();
        $table->integer('org_id')->nullable();
        $table->integer('pipeline_id')->nullable();
        $table->string('job_id', 244)->nullable();
        $table->integer('stage_id')->nullable();
        $table->string('deals_status', 50)->nullable();
        $table->string('deals_origin_id', 100)->nullable();
        $table->integer('deals_channel')->nullable();
        $table->string('deals_channel_id', 100)->nullable();
        $table->string('deals_add_time', 244)->nullable();
        $table->string('deals_won_time', 244)->nullable();
        $table->string('deals_lost_time', 244)->nullable();
        $table->string('deals_close_time', 244)->nullable();
        $table->string('deals_expected_close_date', 244)->nullable();
        $table->integer('deals_probability')->nullable();
        $table->text('deals_lost_reason')->nullable();
        $table->string('deals_visible_to', 50)->nullable();
        $table->text('address')->nullable();
        $table->text('notes')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
    });
}


    public function down()
    {
        Schema::dropIfExists('deals');
    }
};

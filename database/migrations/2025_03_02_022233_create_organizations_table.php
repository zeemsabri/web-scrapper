<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->integer('pipe_drive_org_id')->default(0);
            $table->string('org_name', 255);
            $table->string('add_time', 244)->nullable();
            $table->integer('owner_id')->nullable();
            $table->string('org_label', 255)->nullable();
            $table->json('org_label_ids')->nullable();
            $table->string('org_visible_to', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('organizations');
    }
};


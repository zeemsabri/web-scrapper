<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('person', function (Blueprint $table) {
            $table->id();
            $table->integer('pipe_drive_person_id')->default(0);
            $table->string('person_name', 255);
            $table->unsignedBigInteger('owner_id')->nullable(); // No foreign key
            $table->integer('org_id')->nullable();
            $table->json('person_email')->nullable();
            $table->json('person_phone')->nullable();
            $table->string('person_label', 244)->nullable();
            $table->json('person_label_ids')->nullable();
            $table->string('person_visible_to', 255)->nullable();
            $table->string('person_marketing_status', 255)->nullable();
            $table->string('person_add_time', 244)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('person');
    }
};


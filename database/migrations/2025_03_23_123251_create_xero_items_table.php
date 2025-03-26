<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('xero_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('is_sold')->default(true);
            $table->decimal('unit_price', 10, 2);
            $table->string('xero_item_id')->nullable(); // Xero API سے ملنے والا Item ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xero_items', function (Blueprint $table) {
            //
        });
    }
};

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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('pipe_drive_project_id')->nullable(); // Xero Invoice ID
            $table->string('pipe_drive_task_id')->nullable(); // Xero Invoice ID
            $table->string('xero_invoice_id')->nullable(); // Xero Invoice ID
            $table->string('xero_invoice_url')->nullable(); // Xero Invoice URL
            $table->string('contact_id'); // Xero Contact ID
            $table->date('date'); // Invoice Date
            $table->date('due_date'); // Due Date
            $table->decimal('total_amount', 10, 2); // Total Amount
            $table->string('status'); // Invoice Status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

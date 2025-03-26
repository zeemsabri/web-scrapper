<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'xero_invoice_id',
        'xero_invoice_url',
        'contact_id',
        'date',
        'due_date',
        'total_amount',
        'status',
        'pipe_drive_project_id',
        'pipe_drive_task_id'
    ];
}

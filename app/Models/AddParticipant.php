<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'add_participant'; 

    protected $fillable = [
        'pipedrive_add_participant_id',
        'deal_id',
        'person_id',
    ];

    protected $dates = ['deleted_at']; 
}

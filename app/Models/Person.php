<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Person extends Model
{
    use HasFactory; 

    protected $table = 'person'; // Table name (agar table ka naam default nahi hai)
    protected $fillable = [
        'pipe_drive_person_id','person_name', 'owner_id', 'org_id', 'person_email', 'person_phone', 'person_label', 'person_label_ids', 'person_visible_to', 'person_marketing_status', 'person_add_time'
    ];


}

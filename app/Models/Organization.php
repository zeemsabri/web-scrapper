<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipe_drive_org_id',
        'org_name',
        'add_time',
        'owner_id',
        'org_label',
        'org_label_ids',
        'org_visible_to',
    ];

    protected $casts = [
        'label_ids' => 'array',
    ];
}

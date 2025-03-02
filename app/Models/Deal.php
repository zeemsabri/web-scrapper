<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $table = 'deals';

    protected $fillable = [
        'pipe_drive_deal_id',
        'deals_title',
        'deals_value',
        'deals_label',
        'deals_currency',
        'user_id',
        'person_id',
        'org_id',
        'pipeline_id',
        'stage_id',
        'deals_status',
        'deals_origin_id',
        'deals_channel',
        'deals_channel_id',
        'deals_add_time',
        'deals_won_time',
        'deals_lost_time',
        'deals_close_time',
        'deals_expected_close_date',
        'deals_probability',
        'deals_lost_reason',
        'deals_visible_to',
        'description',
        'notes',
        'address',
        'job_id',
    ];

    protected $casts = [
        'deals_label' => 'array'
    ];
}

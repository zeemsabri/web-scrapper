<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XeroItem extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_sold', 'unit_price', 'xero_item_id'];
}

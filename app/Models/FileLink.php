<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'file_id',
        'type_id',
        'sheet_id',
    ];
    public $timestamps = false;
}

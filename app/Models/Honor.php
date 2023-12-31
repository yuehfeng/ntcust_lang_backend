<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Honor extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'ranking',
        'ranking_style',
        'department'
    ];
}

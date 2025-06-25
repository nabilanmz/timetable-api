<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedTimetable extends Model
{
    /** @use HasFactory<\Database\Factories\GeneratedTimetableFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'timetable',
        'active',
    ];

    protected $casts = [
        'timetable' => 'array',
    ];
}

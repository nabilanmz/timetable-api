<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetablePreference extends Model
{
    /** @use HasFactory<\Database\Factories\TimetablePreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];
}

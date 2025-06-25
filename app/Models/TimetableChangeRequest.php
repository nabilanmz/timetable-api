<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableChangeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\TimetableChangeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'generated_timetable_id',
        'message',
        'status',
        'admin_response',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generatedTimetable()
    {
        return $this->belongsTo(GeneratedTimetable::class);
    }
}

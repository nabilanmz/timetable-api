<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'timetable_id',
        'subject_id',
        'lecturer_id',
        'day_id',
        'time_slot_id',
        'activity',
        'section',
        'venue',
        'tied_to',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function day()
    {
        return $this->belongsTo(Day::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }
}

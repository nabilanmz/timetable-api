<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="TimetableEntry",
 *     type="object",
 *     title="Timetable Entry",
 *     @OA\Property(property="id", type="integer", readOnly="true"),
 *     @OA\Property(property="timetable_id", type="integer"),
 *     @OA\Property(property="subject_id", type="integer"),
 *     @OA\Property(property="lecturer_id", type="integer"),
 *     @OA\Property(property="day_id", type="integer"),
 *     @OA\Property(property="time_slot_id", type="integer"),
 *     @OA\Property(property="activity", type="string"),
 *     @OA\Property(property="section", type="string"),
 *     @OA\Property(property="venue", type="string"),
 *     @OA\Property(property="tied_to", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject"),
 *     @OA\Property(property="lecturer", ref="#/components/schemas/Lecturer"),
 *     @OA\Property(property="day", ref="#/components/schemas/Day"),
 *     @OA\Property(property="time_slot", ref="#/components/schemas/TimeSlot")
 * )
 */
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

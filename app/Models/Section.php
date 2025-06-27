<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Section",
 *     type="object",
 *     title="Section",
 *     description="A course section with activity type and tied relationships.",
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key"),
 *     @OA\Property(property="subject_id", type="string", format="uuid", description="Foreign key for the subject"),
 *     @OA\Property(property="section_number", type="string", description="The section identifier (alphanumeric)", example="FCI1"),
 *     @OA\Property(property="activity", type="string", enum={"Lecture", "Tutorial", "Lab"}, description="Type of class activity"),
 *     @OA\Property(property="lecturer_id", type="string", format="uuid", description="Foreign key for the lecturer"),
 *     @OA\Property(property="start_time", type="string", format="time", description="Class start time"),
 *     @OA\Property(property="end_time", type="string", format="time", description="Class end time"),
 *     @OA\Property(property="day_of_week", type="string", description="Day of the week for the class"),
 *     @OA\Property(property="venue", type="string", nullable=true, description="Location of the class"),
 *     @OA\Property(property="capacity", type="integer", description="Maximum number of students"),
 *     @OA\Property(property="tied_to", type="array", @OA\Items(type="string"), nullable=true, description="Array of section numbers that must be taken together"),
 *     @OA\Property(property="enrolled_students", type="integer", description="Number of students currently enrolled"),
 *     @OA\Property(property="status", type="string", enum={"Available", "Full"}, description="Availability status"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject"),
 *     @OA\Property(property="lecturer", ref="#/components/schemas/Lecturer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Section extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sections';

    protected $fillable = [
        'subject_id',
        'section_number',
        'activity',
        'lecturer_id',
        'start_time',
        'end_time',
        'day_of_week',
        'venue',
        'capacity',
        'tied_to',
    ];

    protected $casts = [
        'tied_to' => 'array',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function timetables()
    {
        return $this->belongsToMany(Timetable::class);
    }
}

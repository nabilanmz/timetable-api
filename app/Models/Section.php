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
 *     description="A course section.",
 *     @OA\Property(property="id", type="string", format="uuid", description="Primary key"),
 *     @OA\Property(property="subject_id", type="string", format="uuid", description="Foreign key for the subject"),
 *     @OA\Property(property="section_number", type="integer", description="The section number"),
 *     @OA\Property(property="lecturer_id", type="string", format="uuid", description="Foreign key for the lecturer (user)"),
 *     @OA\Property(property="start_time", type="string", format="time", description="Class start time"),
 *     @OA\Property(property="end_time", type="string", format="time", description="Class end time"),
 *     @OA\Property(property="day_of_week", type="string", description="Day of the week for the class"),
 *     @OA\Property(property="venue", type="string", nullable=true, description="Location of the class"),
 *     @OA\Property(property="capacity", type="integer", description="Maximum number of students"),
 *     @OA\Property(property="enrolled_students", type="integer", description="Number of students currently enrolled"),
 *     @OA\Property(property="status", type="string", enum={"Available", "Full"}, description="Availability status"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject"),
 *     @OA\Property(property="lecturer", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Section extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subject_id',
        'section_number',
        'lecturer_id',
        'start_time',
        'end_time',
        'day_of_week',
        'venue',
        'capacity',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'created_by'];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timetableEntries()
    {
        return $this->hasMany(TimetableEntry::class);
    }
}

Models for Laravel API Backend
User

Handles user authentication and management.
Fields: id, name, email, password, role, created_at, updated_at.
Timetable

Represents a timetable entry.
Fields: id, name, description, created_by (foreign key to User), created_at, updated_at.
Subject

Represents a subject in the timetable.
Fields: id, name, code, description, created_at, updated_at.
Lecturer

Represents a lecturer or teacher.
Fields: id, name, email, phone, department, created_at, updated_at.
Day

Represents the days of the week for scheduling.
Fields: id, name (e.g., "Monday", "Tuesday"), created_at, updated_at.
TimeSlot

Represents available time slots for scheduling.
Fields: id, start_time, end_time, created_at, updated_at.
TimetableEntry

Represents a single entry in the timetable (e.g., a subject scheduled at a specific time).
Fields: id, timetable_id (foreign key to Timetable), subject_id (foreign key to Subject), lecturer_id (foreign key to Lecturer), day_id (foreign key to Day), time_slot_id (foreign key to TimeSlot), created_at, updated_at.
Setting

Represents application-wide settings (e.g., max days, max hours).
Fields: id, key, value, created_at, updated_at.

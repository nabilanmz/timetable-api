# Timetable API - Frontend/Backend Handoff

## Overview

The timetable generation system has been completely migrated to a high-performance, modular architecture with the following key improvements:

### 🚀 **New Python Timetable Engine**
- **Genetic Algorithm Optimization**: Advanced optimization using genetic algorithms for better timetable generation
- **Activity Types Support**: Full support for Lecture, Tutorial, and Lab sections
- **Tied Sections**: Automatic handling of lecture-tutorial pairs that must be taken together
- **Flexible Scheduling**: Two optimization modes - `compact` (minimize gaps) and `spaced_out` (maximize breaks)
- **Real-time Progress**: Progress feedback during generation process

### 📊 **Enhanced Data Model**
- **Section Activity Field**: Each section now has an `activity` type (Lecture/Tutorial/Lab)
- **Tied Relationships**: Sections can be tied together (e.g., lecture + tutorial)
- **Lecturer Model**: Separate Lecturer entity with dedicated attributes
- **Alphanumeric Section Numbers**: Support for section identifiers like "FCI1", "A1", etc.
- **Real Course Data**: Seeded with 59 subjects, 87 lecturers, and 400+ sections

### 🔄 **Updated API Endpoints**

#### **New/Enhanced Endpoints:**

1. **POST /api/generate-timetable** (⭐ Main Generation Endpoint)
   ```json
   {
     "preferences": {
       "subjects": [1, 2, 3],
       "days": [1, 2, 3, 4, 5],
       "start_time": "08:00",
       "end_time": "18:00",
       "enforce_ties": "yes",
       "lecturers": [1, 5, 10],
       "mode": 1
     }
   }
   ```

2. **GET /api/preference-options** (🆕 New)
   - Returns available subjects and lecturers for preference selection

3. **GET /api/available-timeslots** (🆕 New - Time Validation Feature)
   - Returns available time slots for selected subjects
   - **Query Parameter**: `subject_ids` (comma-separated list of subject IDs)
   - **Example**: `/api/available-timeslots?subject_ids=1,2,3`
   - **Purpose**: Filters time options to show only slots that have sections for selected subjects
   - **Response Format**: Array of time slot objects with `id`, `start_time`, `end_time`

4. **GET /api/my-timetable**
   - Returns the user's active generated timetable

#### **Enhanced Existing Endpoints:**
- **GET /api/sections**: Now includes `activity`, `tied_to`, and uses Lecturer model
- **GET /api/lecturers**: Dedicated lecturer management
- **GET /api/subjects**: Enhanced with real course data

### 🎯 **Key Features for Frontend Integration**

#### **Timetable Generation Request Format:**
```json
{
  "preferences": {
    "subjects": [1, 2, 3],           // Required: Array of subject IDs
    "days": [1, 2, 3, 4, 5],         // Required: Preferred day IDs (1=Monday, etc.)
    "start_time": "08:00",           // Required: Earliest preferred start time (HH:MM)
    "end_time": "18:00",             // Required: Latest preferred end time (HH:MM)
    "enforce_ties": "yes",           // Required: "yes" or "no" for tied sections
    "lecturers": [1, 5, 10],         // Optional: Preferred lecturer IDs
    "mode": 1                        // Required: 1=compact, 2=spaced_out
  }
}
```

#### **Generated Timetable Response Format:**
```json
{
  "id": 1,
  "user_id": 1,
  "active": true,
  "timetable": {
    "Monday": [
      {
        "code": "LMPU3192",
        "subject": "Falsafah dan Isu Semasa",
        "activity": "Lecture",
        "section": "FCI1",
        "days": "Monday",
        "start_time": "10:00:00",
        "end_time": "12:00:00",
        "venue": "CNMX1001",
        "lecturer": "Dr. John Smith",
        "tied_to": ["FCI1-T"]
      }
    ],
    "Tuesday": [],
    // ... other days
  }
}
```

### 🔐 **Authentication & Security**
- **Bearer Token Required**: All endpoints require `Authorization: Bearer <token>`
- **User Registration**: POST /api/register
- **User Login**: POST /api/login
- **User Info**: GET /api/user

### ⚠️ **Important Changes for Frontend**

1. **New Activity Types**: All sections now have `activity` field (Lecture/Tutorial/Lab)
2. **Tied Sections**: The `tied_to` array indicates related sections
3. **Lecturer Model**: Use dedicated Lecturer model instead of User for lecturers
4. **Section Numbers**: Now alphanumeric strings (e.g., "FCI1") instead of just numbers
5. **Generation Modes**: Two optimization modes for different scheduling preferences
6. **Enhanced Error Handling**: Detailed error responses with specific error codes

### 📖 **API Documentation**

The complete, updated Swagger/OpenAPI documentation is available at:
- **JSON Format**: `/storage/api-docs/api-docs.json`
- **Interactive UI**: `/api/documentation` (when server is running)

### 🧪 **Testing & Validation**

All endpoints have been thoroughly tested with:
- ✅ Unit tests for all models and controllers
- ✅ Integration tests for the Python engine
- ✅ End-to-end workflow testing
- ✅ Performance optimization and validation

### 🚀 **Getting Started for Frontend Team**

1. **Use the new generation endpoint**: POST /api/generate-timetable
2. **Fetch preference options**: GET /api/preference-options
3. **Handle activity types**: Display Lecture/Tutorial/Lab appropriately
4. **Support tied sections**: Show relationships between sections
5. **Implement mode selection**: Allow users to choose compact vs spaced scheduling

### 📞 **Support & Questions**

For any questions about the API or integration, please refer to:
1. The attached Swagger documentation (`api-docs.json`)
2. The test files in `/tests/Feature/` for usage examples
3. The controller implementations for detailed request/response formats

---

**Backend Status**: ✅ Complete and Production Ready  
**API Documentation**: ✅ Updated and Comprehensive  
**Test Coverage**: ✅ 100% Core Functionality Tested  
**Performance**: ✅ Optimized with Early Termination and Caching

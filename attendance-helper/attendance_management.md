# Skill: Laravel Attendance Management (SETEC) — Attendance + Reports + Blacklist

## Goal
Implement the Attendance module in Laravel with:
- Mark attendance only for valid class sessions and selected term/date/class/teacher.
- Role-based access: teachers only access assigned classes.
- Attendance statuses: Presence / Absence / Permission (mutually exclusive).
- Require status for every student before saving (no partial save).
- Disallow future date selection.
- Bulk-save attendance for all students in one submission.
- Reports: daily/weekly/term/custom range, with summary counts + percentages + visual indicators.
- Export: Excel (.xlsx) and PDF preview/export.
- Blacklist: auto-flag students with >= 16 absences per semester and provide blacklist report.

(Requirements source: Attendance Management PDF)

## Stack & Packages
- Laravel 12
- PHP 8.2
- Laravel Jetstream (auth scaffolding)
- Laravel Sanctum (API auth)
- Vite + TailwindCSS 4 (frontend tooling)

## Core Screens (UI)
Implement these screens to match Figma:
1) Attendance Search/Filter page (date, term, class, teacher + Search button)
2) Attendance Recording page (table students, radio status, comment input, Save)
3) Reports page (filters + preview + export buttons)
4) Blacklist report page (students + absence count)
5) Header navigation: Home + Class Attendance

NOTE: If Figma frames are provided (PNG export or public link), replicate layout, spacing, typography, and component style exactly.

## Domain Rules (Must-Enforce)
### Attendance Marking Constraints
- Attendance can be marked ONLY for valid class sessions (term + class + teacher must form a real assigned session).
- Date validation: attendance_date <= today (no future dates).
- Teacher access: teacher can only mark/view attendance for classes assigned to them.
- Validation: every student must have one selected status before saving.
- Status is one-of: present | absent | permission.
- Store optional comment per student.

### Bulk Save
- Single submit saves all student rows in a transaction.
- If any row invalid → reject entire save with clear message.

### Reporting
- Report types:
  - Daily report
  - Weekly report
  - Term/semester report
  - Custom date range report
- Filters can include: academic year, term, program/sub-program, date or range, class
- Report outputs:
  - Raw attendance rows (for exports)
  - Summary counts: total present/absent/permission
  - Attendance percentage per student and/or per class
  - Visual indicators (UI): color-coded status badges

### Blacklist
- For each semester/term: compute absences per student.
- If absence_count >= 16 → student appears in blacklist.
- Provide blacklist report with absence counts.
- Blacklist should update automatically when new attendance records are saved.

## Suggested Data Model (Adapt to existing DB if already exists)
Tables (typical):
- users
- roles, permissions, model_has_roles, model_has_permissions (Spatie)
- academic_years (id, name, start_date, end_date)
- terms (id, academic_year_id, name, start_date, end_date)
- classes (id, name, start_time, end_time, room, ...)
- teachers (id, user_id, ...)
- students (id, code, name, status, ...)
- enrollments (id, class_id, student_id, enrolled_at)
- class_teacher (id, class_id, teacher_id, assigned_at) OR class_assignments
- class_sessions (id, class_id, term_id, teacher_id, session_date, status) [optional but helpful]
- attendance_records (id, class_id, term_id, teacher_id, student_id, attendance_date, status, comment, recorded_by, recorded_at)

Blacklist can be:
- computed on the fly in queries (preferred), OR
- materialized table: blacklists (id, student_id, term_id, absence_count, flagged_at)

## Authorization Policy
Roles:
- Admin: full access
- Teacher: access only assigned classes for marking/viewing
- Staff/Registrar (optional): can view reports / exports

Implement:
- middleware: auth
- policies/gates:
  - AttendancePolicy@mark(class_id, term_id, teacher_id)
  - AttendancePolicy@view(class_id, term_id)
  - ReportPolicy@export

## API/Route Design (choose Web or API-first)
### Web (Blade)
- GET  /attendance -> search/filter UI
- GET  /attendance/entry?date=&term_id=&class_id=&teacher_id= -> entry table
- POST /attendance/save -> bulk save
- GET  /attendance/reports -> reports UI
- GET  /attendance/reports/export/excel -> excel download
- GET  /attendance/reports/preview/pdf -> pdf preview
- GET  /attendance/blacklist -> blacklist report

### Validation (must)
- date required, <= today
- term_id, class_id, teacher_id required
- students[] required
- status[student_id] required and in(present,absent,permission)

## Implementation Workflow (When asked to build a feature)
When user asks for a feature/page:
1) Create/adjust migrations + models + relationships
2) Create FormRequest classes for validation
3) Create Service class for business logic (AttendanceService, ReportService)
4) Create Controller thin layer
5) Build Blade views matching Figma (components for table, filters, badges)
6) Add policy checks + middleware
7) Add exports (Excel + PDF)
8) Add tests (feature tests for:
   - teacher cannot access unassigned class
   - cannot save future date
   - cannot save if any student missing status
   - blacklist triggers at >= 16 absences)

## Output Format Rules (for Codex)
- Always provide file paths and full file contents.
- Use clean Laravel conventions (PSR-12).
- Prefer transactions for bulk save.
- Avoid N+1 queries (use eager loading).
- Include artisan commands if needed.
- Provide seeders/factories only when requested.

# API Folder Structure

This Laravel API keeps framework defaults and introduces modular route files for readability.

## Routes

- `routes/api.php`: main API entry point (loads route modules)
- `routes/api/auth.php`: authentication routes
- `routes/api/protected/access-control.php`: roles, permissions, user roles/profiles
- `routes/api/protected/school-structure.php`: classes, terms, academic year, sessions, enrollments
- `routes/api/protected/people.php`: students, teachers, blacklists
- `routes/api/protected/attendance.php`: attendance records and export

## Scaling guideline

When adding a new backend domain:

1. Add a dedicated route module under `routes/api/protected`.
2. Register it from `routes/api.php` inside the `auth:sanctum` group.
3. Keep controllers/actions grouped by domain naming convention.

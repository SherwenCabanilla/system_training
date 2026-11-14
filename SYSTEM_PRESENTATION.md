# SegHIS Patient Portal — Presentation Guide

## 1. What this system is

SegHIS Patient Portal is a Laravel web application for two user roles:

- **Patients** can book appointments, view appointment updates, view clinical records and vital signs, and receive notifications.
- **Staff** can assign appointments, propose new schedules, complete visits, add clinical assessments, and optionally record vital signs.

The project is a patient-portal **demo**. The medical entries used during a demonstration should be described as sample/demo data, not real clinical advice.

## 2. Technology used

| Area | Technology | Why it is used |
| --- | --- | --- |
| Backend | PHP 8.5 and Laravel 13 | Routing, authentication, validation, database access, views, transactions |
| Database | MySQL | Stores accounts, appointments, records, vitals, and notifications |
| UI | Blade templates and Tailwind CSS | Server-rendered pages with responsive styling |
| Browser behavior | JavaScript, `fetch()`, Alpine.js | Modals, background refreshes, polling, and interactive forms |
| Asset build | Vite | Bundles JavaScript and CSS for the browser |
| Tests | PHPUnit | Tests the Laravel HTTP/database behavior |
| Development tooling | Laravel Boost, Pint | Boost helps inspect the Laravel app during development; Pint formats PHP |

### Important terminology

- **Laravel Boost** is a development assistant/tooling package. It helped inspect routes, database schema, logs, and Laravel documentation while building the project. It is **not** a patient-facing feature and does not run the portal’s business flow.
- **Bootstrap CSS is not used**. The interface is styled with **Tailwind CSS** utility classes.
- The “real-time” behavior is a **polling simulation**, not WebSockets. The browser checks for changes every 30 seconds.

## 3. Main application flow

```text
Patient books appointment
        ↓
Appointment saved in MySQL
        ↓
Staff assigns / schedules / proposes a time
        ↓
Patient receives a portal notification
        ↓
Staff completes the visit
        ↓
Staff saves clinical assessment
        ↓
Optional: staff turns on “Record vital signs” and enters HR, temperature, BP
        ↓
Patient sees the update in Medical Records and Dashboard
```

## 4. Database design

The MySQL database name is configured in `.env` through `DB_DATABASE` (the local setup used `seghis_patient_portal`). Do not commit database passwords or a populated `.env` file to GitHub.

### Core tables

| Table | Purpose | Key fields |
| --- | --- | --- |
| `users` | Login account and role | `name`, `email`, `password`, `is_staff` |
| `patients` | Patient profile linked to one user | `user_id`, DOB, contact and address fields |
| `doctors` | Available doctors | `name`, `department`, `specialty`, `is_active` |
| `appointments` | Requested, scheduled, completed, missed, or cancelled visits | `patient_id`, `doctor_id`, `scheduled_at`, `doctor_name`, `department`, `reason`, `status` |
| `medical_records` | Doctor's clinical assessment | `patient_id`, `recorded_on`, `record_type`, `provider_name`, `title`, `summary`, `result_summary` |
| `vital_readings` | Measurements taken during a visit | `patient_id`, `appointment_id`, `heart_rate`, `temperature`, `systolic`, `diastolic` |
| `portal_notifications` | Patient updates and unread count | `patient_id`, `type`, `title`, `body`, `read_at`, `target` |
| `sessions` | Database-backed Laravel sessions | `id`, `user_id`, `payload`, `last_activity` |

### Important relationships

```text
users 1 ── 1 patients
patients 1 ── * appointments
patients 1 ── * medical_records
patients 1 ── * vital_readings
patients 1 ── * portal_notifications
doctors 1 ── * appointments
appointments 1 ── * vital_readings
```

Laravel models that represent these tables are in [`app/Models`](app/Models):

- [`User.php`](app/Models/User.php)
- [`Patient.php`](app/Models/Patient.php)
- [`Appointment.php`](app/Models/Appointment.php)
- [`MedicalRecord.php`](app/Models/MedicalRecord.php)
- [`VitalReading.php`](app/Models/VitalReading.php)
- [`PortalNotification.php`](app/Models/PortalNotification.php)

The table definitions are versioned in [`database/migrations`](database/migrations), which is what GitHub will show as the database structure history.

## 5. Backend: Laravel routes and controllers

All main routes are declared in [`routes/web.php`](routes/web.php).

| User action | Route/controller | What happens |
| --- | --- | --- |
| Patient dashboard | `DashboardController` | Loads appointments, latest record, and latest vital signs |
| Book/edit/cancel appointment | `AppointmentController` | Validates input, checks availability, saves appointment, creates notification |
| Staff workspace | `StaffDashboardController` | Manages assignment, completion, records, and vitals |
| Patient medical records | `MedicalRecordController` | Builds the timeline, vital trend data, and AJAX timeline fragment |
| Notifications | `PortalNotificationController` | Returns unread count and notification feed; marks notifications read |
| Schedule proposal response | `AppointmentProposalController` | Accepts/declines staff-proposed appointment times |

### Clinical record and optional vital signs

The staff **Complete visit** form uses [`StoreStaffMedicalRecordRequest.php`](app/Http/Requests/StoreStaffMedicalRecordRequest.php) for validation and [`StaffDashboardController::storeMedicalRecord`](app/Http/Controllers/StaffDashboardController.php) for saving.

The form always saves a clinical assessment. The staff member can turn on **Record vital signs** only when measurements were taken. When that option is off, no vital-sign row is created. When it is on, heart rate, temperature, systolic BP, and diastolic BP are all required together.

## 6. Frontend pages and JavaScript

### Blade views

| Screen | Main Blade file |
| --- | --- |
| Patient dashboard | [`resources/views/dashboard.blade.php`](resources/views/dashboard.blade.php) |
| Appointment management | [`resources/views/appointments/index.blade.php`](resources/views/appointments/index.blade.php) |
| Medical Records | [`resources/views/medical-records/index.blade.php`](resources/views/medical-records/index.blade.php) |
| Medical-record timeline fragment | [`resources/views/medical-records/_timeline.blade.php`](resources/views/medical-records/_timeline.blade.php) |
| Staff dashboard / visit form | [`resources/views/staff`](resources/views/staff) |
| Navigation and badge | [`resources/views/layouts/navigation.blade.php`](resources/views/layouts/navigation.blade.php) |

### JavaScript behavior

| File | Behavior |
| --- | --- |
| [`resources/js/appointments.js`](resources/js/appointments.js) | Appointment form actions, availability lookup, appointment-list refresh, dashboard card refresh |
| [`resources/js/medical-records.js`](resources/js/medical-records.js) | Vital charts, timeline pagination via AJAX, record-detail modal, notification target indicator, background timeline refresh |
| [`resources/js/notifications.js`](resources/js/notifications.js) | Polls the notification feed every 30 seconds, updates unread badge, shows toast, broadcasts an in-browser update event |
| [`resources/js/staff-assignments.js`](resources/js/staff-assignments.js) | Staff assignment availability and request-list updates |
| [`resources/js/app.js`](resources/js/app.js) | Imports the JavaScript modules and starts Alpine.js |

## 7. AJAX / no-full-reload features

The project uses browser `fetch()` calls. This is sometimes called AJAX: the browser requests data or HTML in the background and updates a section of the current page instead of navigating away.

### A. Appointment availability

When staff chooses a doctor/date, JavaScript calls the appointment availability endpoint. Laravel responds with JSON slots; the page updates the available-time choices without a full reload.

### B. Medical-record timeline pagination

When the patient changes timeline pages, [`medical-records.js`](resources/js/medical-records.js) requests the same Medical Records route with `Accept: application/json`. `MedicalRecordController` returns only the rendered timeline HTML, which replaces `#health-activities`.

### C. Notification polling simulation

Every 30 seconds, [`notifications.js`](resources/js/notifications.js) requests:

```text
GET /notifications/feed?after=<last-seen-id>
```

The response contains:

- `unread_count` for the notification badge
- `revision` used to tell pages that data changed
- new notification data for a toast

After polling, JavaScript dispatches a browser event:

```js
window.dispatchEvent(new CustomEvent('patient-notifications-updated', {
    detail: { revision: data.revision },
}));
```

The Medical Records page listens to that event and refreshes its timeline fragment. The Dashboard listens too and refreshes its Upcoming appointments, Health snapshot, and Latest record cards. This is the project’s real-time update simulation without WebSockets and without a full page reload.

### D. Notification target animation

When a notification opens a Medical Records URL with `?record=record-<id>` or `?record=vital-<id>`, [`medical-records.js`](resources/js/medical-records.js) finds that timeline row, scrolls to it, and adds a visible teal outline plus a bouncing **“Your notification”** pointer. The patient then clicks the highlighted row to open the detail dialog.

## 8. Notification rules

| Event | Expected notification behavior |
| --- | --- |
| Patient creates/edits/cancels own appointment | **Current behavior:** an unread audit notification is created, so it contributes to the badge count; it is filtered out of the live toast feed. **Recommended future UX:** do not increase the badge for an action the patient just performed. |
| Staff assigns/confirms/proposes/changes appointment | Increases the patient notification count |
| Staff saves a clinical record | Increases the count and links to Medical Records |
| Staff saves vital signs | Increases the count and links to Medical Records |

The notification target is stored as JSON in `portal_notifications.target`, for example `{"kind":"record","id":13}`. [`PortalNotification::destinationUrl`](app/Models/PortalNotification.php) turns this into the correct patient link.

## 9. Security and validation points to present

- Authentication is required for patient routes.
- Staff routes use both `auth` and `staff` middleware.
- Form Requests validate appointment, record, and vital-sign input.
- Models use Laravel fillable attributes to control mass assignment.
- Controllers use database transactions (`DB::transaction`) when several related writes must succeed together.
- Patients can only access records tied to their own patient account.
- Blade `{{ }}` output escapes HTML by default.

## 10. Suggested live demo script

1. Log in as a patient and open **Dashboard**.
2. Show the notification badge and **Upcoming appointments**.
3. Log in as staff in another browser/session.
4. Open a scheduled visit and click **Complete visit**.
5. Enter a diagnosis/clinical assessment. Turn on **Record vital signs** for one visit and leave it off for another.
6. Save the visit.
7. Return to the patient view. Within the polling interval, show the notification badge, toast, Dashboard refresh, and Medical Records timeline refresh without refreshing the page.
8. Click the notification. Point out the animated **Your notification** marker on the matching record.
9. Open the record and show the doctor notes, follow-up plan, and any recorded vital signs.

## 11. Local setup and useful commands

```powershell
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Create/configure .env, then set MySQL values
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=seghis_patient_portal

# Create database tables
php artisan migrate

# Optional: load project demo seed data
php artisan db:seed

# Run the local application
composer run dev

# Build browser assets for production
npm run build

# Run tests
php artisan test --compact
```

## 12. Current limitations to mention honestly

- Updates are polled every 30 seconds; they are not instant WebSocket updates.
- This is a demo portal and does not replace an Electronic Medical Record system or medical advice.
- Demo health data should never be used for real patient care.
- Production deployment would need role auditing, stronger medical-data governance, backups, encryption strategy, audit trails, and compliance review.

## 13. Where to start when reviewing GitHub

1. Read [`routes/web.php`](routes/web.php) to see the user-facing features.
2. Open [`app/Models`](app/Models) for the data relationships.
3. Open [`database/migrations`](database/migrations) for the schema history.
4. Open [`app/Http/Controllers`](app/Http/Controllers) for business flows.
5. Open [`resources/views`](resources/views) for page layout.
6. Open [`resources/js`](resources/js) for AJAX, polling, and animations.
7. Open [`tests/Feature`](tests/Feature) for tested behavior.

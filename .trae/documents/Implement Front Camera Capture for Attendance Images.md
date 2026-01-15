## Goal
Ensure that when an image is required (login/logout proof) the laptop’s front camera opens, a photo is captured, uploaded, and stored against the correct attendance record, replacing the current dummy image behavior.

## Where to Integrate
1. Admin Dashboard Attendance section (`web_admin/dashboard.html` and `web_admin/js/dashboard.js`).
2. Backend API (`backend/api/admin/`). Attendance table already has `login_image_path` and `logout_image_path` fields.

## Frontend Implementation
1. Add a reusable Camera modal:
   - Modal markup with `<video>` preview, `<canvas>` for snapshot, buttons: Start, Capture, Retake, Save, Close.
   - Error banner and a file input fallback if camera access fails.
2. Camera control functions in `dashboard.js`:
   - `openCameraForAttendance(attendanceId, type)` to start `navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })` and show modal.
   - `capturePhoto()` draws the current frame to canvas, converts to Blob/JPEG.
   - `saveCapturedPhoto(attendanceId, type)` posts Blob via `FormData` to backend; on success, updates the row UI to indicate image present.
   - `stopCamera()` stops all tracks when closing modal.
3. Attendance table actions:
   - Add two buttons per row: `Capture Login Photo`, `Capture Logout Photo` (only show relevant one depending on record state), wired to `openCameraForAttendance`.
   - Optional: add `View Photo` that opens the stored image in a lightbox or new tab.
4. UX details:
   - Prefer the front camera via `facingMode: 'user'`.
   - Handle permission prompts; show clear instructions if blocked.
   - Limit image size (e.g., 1280×720) for performance; compress to ~80% JPEG quality.

## Backend Implementation
1. New endpoint: `backend/api/admin/upload_attendance_image.php` (multipart/form-data):
   - Inputs: `attendance_id`, `type` (`login`|`logout`), `image` file.
   - Validations: admin session, attendance exists, file type `image/jpeg`/`image/png`, size limit.
   - Storage: save under `uploads/attendance/<attendance_id>/<type>-<timestamp>.jpg` (create directories as needed).
   - DB Update: set `attendance.login_image_path` or `attendance.logout_image_path` accordingly; maintain existing business logic.
   - Response: `{ success, path }`.
2. Security & hardening:
   - Sanitize file names; restrict MIME types; limit size (e.g., 2–5 MB).
   - Ensure directories aren’t publicly writable; set proper permissions.

## Wiring & Display
1. In the Attendance list, after upload completes, re-fetch or patch the row to show a camera/check icon indicating an image is stored.
2. Add `viewAttendancePhoto(attendanceId, type)` to open the stored image.

## Compatibility & Fallbacks
- If no camera is available or access is denied:
  - Show a file picker to manually upload an image.
  - Keep behavior graceful without breaking other attendance features.

## Non-Goals
- Payroll logic remains as implemented; this feature does not alter payroll calculations or currency handling.

## Testing
1. Verify camera opens and captures on Chrome/Edge.
2. Confirm upload succeeds and DB fields are updated.
3. Validate fallback file input path.
4. Check stored images load via the viewer.

## Rollout Steps
1. Implement modal and JS functions.
2. Add backend endpoint.
3. Wire buttons in Attendance table and retest.
4. No schema changes required (fields exist).

If this plan looks good, I will implement the modal, JS handlers, the upload API, and wire the Attendance UI accordingly.
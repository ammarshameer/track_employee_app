/**
 * Shared API helper functions for the admin web interface.
 *
 * Deduplicates the repeated fetch-with-credentials, JSON parsing,
 * error handling, and attendance-status-badge logic that previously
 * appeared in multiple places across dashboard.js and index.html.
 */

const API_BASE = '../backend/api';

// ── Fetch wrappers ───────────────────────────────────────────────────

async function apiGet(endpoint) {
    const response = await fetch(`${API_BASE}${endpoint}`, {
        credentials: 'include',
    });
    return response.json();
}

async function apiPost(endpoint, body) {
    const response = await fetch(`${API_BASE}${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(body),
    });
    return response.json();
}

// ── Error handling ───────────────────────────────────────────────────

function handleApiError(error, context) {
    console.error(`Error ${context}:`, error);
    alert(`Error ${context}. Please try again.`);
}

// ── Attendance status badge ──────────────────────────────────────────

/**
 * Determine the Bootstrap badge class and label for an attendance
 * record.  The same logic was duplicated in loadAttendanceData(),
 * viewAttendanceDetails(), and viewAttendanceDetailsFromButton().
 *
 * @param {object} opts
 * @param {boolean} opts.loggedOut  Whether a logout time is present.
 * @param {number}  opts.totalSeconds  Raw seconds worked (used for
 *                                      the "partial" threshold).
 * @returns {{ text: string, badgeClass: string }}
 */
function getAttendanceStatusBadge({ loggedOut, totalSeconds }) {
    if (!loggedOut) {
        return { text: 'Logged In', badgeClass: 'bg-warning' };
    }
    if (totalSeconds < 4 * 3600) {
        return { text: 'Partial', badgeClass: 'bg-info' };
    }
    return { text: 'Present', badgeClass: 'bg-success' };
}

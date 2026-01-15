const API_BASE = '../backend/api';
let map;
let employeeMarkers = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    if (!isAuthenticated()) {
        window.location.href = 'index.html';
        return;
    }
    
    // Load admin data
    loadAdminData();
    
    // Set current date
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString();
    document.getElementById('trackingDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('attendanceDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('hireDate').value = new Date().toISOString().split('T')[0];
    
    // Load initial data
    loadDashboardStats();
    
    // Setup navigation
    setupNavigation();
    
    // Initialize map
    initializeMap();
});

function isAuthenticated() {
    return sessionStorage.getItem('admin_logged_in') === 'true';
}

function loadAdminData() {
    const adminData = JSON.parse(sessionStorage.getItem('admin_data') || '{}');
    document.getElementById('adminName').textContent = adminData.username || 'Admin';
}

function setupNavigation() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active nav
            document.querySelectorAll('.nav-link').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Show section
            const section = this.getAttribute('data-section');
            showSection(section);
        });
    });
}

function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('[id$="Section"]').forEach(section => {
        section.classList.add('d-none');
    });
    
    // Show selected section
    const section = document.getElementById(sectionName + 'Section');
    if (section) {
        section.classList.remove('d-none');
    }
    
    // Update page title
    const titles = {
        'overview': 'Dashboard Overview',
        'employees': 'Employee Management',
        'tracking': 'Live Employee Tracking',
        'attendance': 'Attendance Records',
        'payroll': 'Payroll Management'
    };
    
    document.getElementById('pageTitle').textContent = titles[sectionName] || 'Dashboard';
    
    // Load section-specific data
    switch(sectionName) {
        case 'employees':
            loadEmployees();
            break;
        case 'tracking':
            initializeMap();
            break;
        case 'attendance':
            loadAttendanceData();
            break;
        case 'payroll':
            initializePayrollDefaults();
            loadPayroll();
            break;
    }
}

async function loadDashboardStats() {
    try {
        const response = await fetch(`${API_BASE}/admin/get_dashboard_stats.php`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            document.getElementById('totalEmployees').textContent = stats.total_employees || '0';
            document.getElementById('activeEmployees').textContent = stats.active_employees || '0';
            document.getElementById('trackingUpdates').textContent = stats.tracking_updates || '0';
            document.getElementById('avgWorkHours').textContent = stats.avg_work_hours || '0';
            document.getElementById('presentToday').textContent = stats.present_today || '0';
            document.getElementById('absentToday').textContent = stats.absent_today || '0';
        } else {
            console.error('Failed to load dashboard stats:', data.message);
        }
        
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

async function loadEmployees() {
    try {
        const response = await fetch(`${API_BASE}/admin/get_employees.php`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const employees = data.data;
            const tbody = document.getElementById('employeesTableBody');
            tbody.innerHTML = '';
            
            employees.forEach(employee => {
                const row = document.createElement('tr');
                const statusBadge = employee.is_active ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-danger">Inactive</span>';
                
                row.innerHTML = `
                    <td>${employee.employee_number}</td>
                    <td>${employee.first_name} ${employee.last_name}</td>
                    <td>${employee.department || 'N/A'}</td>
                    <td>${employee.position || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewEmployee(${employee.employee_id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning me-1" onclick="editEmployee(${employee.employee_id})" title="Edit Employee">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(${employee.employee_id}, '${employee.employee_number}')" title="Delete Employee">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Update employee count in overview if visible
            if (!document.getElementById('overviewSection').classList.contains('d-none')) {
                loadDashboardStats();
            }
            
        } else {
            console.error('Failed to load employees:', data.message);
            alert('Failed to load employees: ' + data.message);
        }
        
    } catch (error) {
        console.error('Error loading employees:', error);
        alert('Error loading employees. Please try again.');
    }
}

function initializeMap() {
    if (map) {
        map.remove();
    }
    
    map = L.map('map').setView([40.7128, -74.0060], 10); // Default to New York
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
}

function initializePayrollDefaults() {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const fmt = d => d.toISOString().split('T')[0];
    const startInput = document.getElementById('payrollStartDate');
    const endInput = document.getElementById('payrollEndDate');
    if (startInput && !startInput.value) startInput.value = fmt(start);
    if (endInput && !endInput.value) endInput.value = fmt(end);
}

async function loadPayroll() {
    const start = document.getElementById('payrollStartDate').value;
    const end = document.getElementById('payrollEndDate').value;
    const emp = document.getElementById('payrollEmployeeSearch').value.trim();
    const tbody = document.getElementById('payrollTableBody');
    if (!start || !end) {
        alert('Please select start and end dates');
        return;
    }
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
    try {
        let url = `${API_BASE}/admin/get_payroll.php?start_date=${start}&end_date=${end}`;
        if (emp) url += `&employee_number=${emp}`;
        const response = await fetch(url, { credentials: 'include' });
        const data = await response.json();
        if (data.success) {
            const rows = data.data || [];
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No records for the selected range</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${r.employee_number}</td>
                    <td>${r.name}</td>
                    <td>${r.total_hours}</td>
                    <td>PKR ${parseFloat(r.hourly_rate).toFixed(2)}</td>
                    <td>PKR ${r.amount}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load payroll</td></tr>';
        }
    } catch (err) {
        console.error('Error loading payroll:', err);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Network error</td></tr>';
    }
}

async function searchEmployeeLocation() {
    const employeeId = document.getElementById('employeeSearch').value.trim();
    const date = document.getElementById('trackingDate').value;
    
    if (!employeeId) {
        alert('Please enter an Employee ID');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/admin/get_employee_location.php?employee_number=${employeeId}&date=${date}`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayEmployeeLocation(data.data);
        } else {
            alert(data.message || 'Employee not found');
        }
        
    } catch (error) {
        console.error('Error searching employee location:', error);
        alert('Error searching employee location');
    }
}

function displayEmployeeLocation(data) {
    // Clear existing markers
    employeeMarkers.forEach(marker => map.removeLayer(marker));
    employeeMarkers = [];
    
    const employee = data.employee;
    const attendance = data.attendance;
    const gpsTracking = data.gps_tracking;
    
    // Show employee info
    document.getElementById('locationDetails').innerHTML = `
        <div class="card">
            <div class="card-header">
                <h5>${employee.first_name} ${employee.last_name} (${employee.employee_number})</h5>
                <small class="text-muted">Date: ${data.date}</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Attendance</h6>
                        ${attendance ? `
                            <p><strong>Login:</strong> ${attendance.login_time_formatted || attendance.login_time || 'N/A'}</p>
                            <p><strong>Logout:</strong> ${attendance.logout_time_formatted || attendance.logout_time || 'Still logged in'}</p>
                            <p><strong>Duration:</strong> ${attendance.duration_hms || (parseFloat(attendance.total_hours || 0).toFixed(2) + ' hours')}</p>
                        ` : '<p>No attendance record for this date</p>'}
                    </div>
                    <div class="col-md-6">
                        <h6>GPS Tracking</h6>
                        <p><strong>Total Updates:</strong> ${gpsTracking.length}</p>
                        <p><strong>Last Update:</strong> ${gpsTracking.length > 0 ? gpsTracking[gpsTracking.length - 1].timestamp : 'N/A'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    if (gpsTracking.length > 0) {
        // Add markers for GPS tracking points
        const bounds = [];
        
        gpsTracking.forEach((point, index) => {
            const lat = parseFloat(point.latitude);
            const lng = parseFloat(point.longitude);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng])
                    .bindPopup(`
                        <strong>Time:</strong> ${point.timestamp}<br>
                        <strong>Accuracy:</strong> ${point.accuracy}m<br>
                        <strong>Speed:</strong> ${point.speed || 0} m/s
                    `)
                    .addTo(map);
                
                employeeMarkers.push(marker);
                bounds.push([lat, lng]);
            }
        });
        
        // Add login/logout markers if available
        if (attendance) {
            if (attendance.login_latitude && attendance.login_longitude) {
                const loginMarker = L.marker([
                    parseFloat(attendance.login_latitude), 
                    parseFloat(attendance.login_longitude)
                ], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                })
                .bindPopup(`<strong>Login Location</strong><br>Time: ${attendance.login_time_formatted || attendance.login_time}`)
                .addTo(map);
                
                employeeMarkers.push(loginMarker);
                bounds.push([parseFloat(attendance.login_latitude), parseFloat(attendance.login_longitude)]);
            }
            
            if (attendance.logout_latitude && attendance.logout_longitude) {
                const logoutMarker = L.marker([
                    parseFloat(attendance.logout_latitude), 
                    parseFloat(attendance.logout_longitude)
                ], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                })
                .bindPopup(`<strong>Logout Location</strong><br>Time: ${attendance.logout_time_formatted || attendance.logout_time}`)
                .addTo(map);
                
                employeeMarkers.push(logoutMarker);
                bounds.push([parseFloat(attendance.logout_latitude), parseFloat(attendance.logout_longitude)]);
            }
        }
        
        // Fit map to show all markers
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }
}

async function loadAttendanceData() {
    const date = document.getElementById('attendanceDate').value;
    const employeeNumber = document.getElementById('attendanceEmployeeSearch').value.trim();
    
    try {
        let url = `${API_BASE}/admin/get_attendance.php?date=${date}`;
        if (employeeNumber) {
            url += `&employee_number=${employeeNumber}`;
        }
        
        const response = await fetch(url, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const attendanceData = data.data;
            const tbody = document.getElementById('attendanceTableBody');
            tbody.innerHTML = '';
            
            if (attendanceData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No attendance records found for this date</td></tr>';
                return;
            }
            
            attendanceData.forEach((record, idx) => {
                const row = document.createElement('tr');
                
                // Determine status and badge color
                let statusText = 'Present';
                let badgeClass = 'bg-success';
                
                if (!record.logout_time) {
                    statusText = 'Logged In';
                    badgeClass = 'bg-warning';
                } else if (record.total_hours < 4) {
                    statusText = 'Partial';
                    badgeClass = 'bg-info';
                }
                
                row.innerHTML = `
                    <td>${record.employee_number}</td>
                    <td>${record.name}</td>
                    <td>${record.login_time_formatted}</td>
                    <td>${record.logout_time_formatted}</td>
                    <td>${record.duration_hms || (parseFloat(record.total_hours_formatted || 0).toFixed(2) + ' hours')}</td>
                    <td><span class="badge ${badgeClass}">${statusText}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="viewAttendanceDetailsFromButton(this, '${date}')"
                                title="View Details"
                                data-employee-number="${record.employee_number}"
                                data-name="${record.name}"
                                data-login="${record.login_time_formatted}"
                                data-logout="${record.logout_time_formatted || ''}"
                                data-total-hours="${record.duration_hms || (record.total_hours_formatted + ' hours')}"
                                data-total-hours-raw="${record.total_seconds || 0}"
                                data-logout-present="${record.logout_time ? '1' : '0'}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
            
        } else {
            console.error('Failed to load attendance data:', data.message);
            alert('Failed to load attendance data: ' + data.message);
        }
        
    } catch (error) {
        console.error('Error loading attendance data:', error);
        alert('Error loading attendance data. Please try again.');
    }
}

function showAddEmployeeModal() {
    const modal = new bootstrap.Modal(document.getElementById('addEmployeeModal'));
    modal.show();
}

async function addEmployee() {
    const form = document.getElementById('addEmployeeForm');
    const formData = new FormData(form);
    
    const employeeData = {
        employee_number: document.getElementById('employeeNumber').value.trim(),
        first_name: document.getElementById('firstName').value.trim(),
        last_name: document.getElementById('lastName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        department: document.getElementById('department').value.trim(),
        position: document.getElementById('position').value.trim(),
        salary: parseFloat(document.getElementById('salary').value) || 0,
        hire_date: document.getElementById('hireDate').value,
        address: document.getElementById('address').value.trim()
    };
    
    if (!employeeData.employee_number || !employeeData.first_name || !employeeData.last_name) {
        alert('Please fill in all required fields');
        return;
    }
    
    const spinner = document.getElementById('addEmployeeSpinner');
    spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(`${API_BASE}/admin/add_employee.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(employeeData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Employee added successfully!\nEmployee ID: ${data.data.employee_number}\nDefault Password: ${data.data.default_password}`);
            
            // Close modal and refresh employees list
            bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();
            form.reset();
            
            // Always refresh employees list and dashboard stats
            loadEmployees();
            loadDashboardStats();
        } else {
            alert(data.message || 'Failed to add employee');
        }
        
    } catch (error) {
        console.error('Error adding employee:', error);
        alert('Network error. Please try again.');
    } finally {
        spinner.classList.add('d-none');
    }
}

async function viewEmployee(employeeId) {
    try {
        const response = await fetch(`${API_BASE}/admin/get_employee_details.php?employee_id=${employeeId}`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const employee = data.data.employee;
            const stats = data.data.monthly_stats;
            const attendanceRecords = data.data.attendance_records;
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Personal Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Employee ID:</strong></td><td>${employee.employee_number}</td></tr>
                            <tr><td><strong>Name:</strong></td><td>${employee.first_name} ${employee.last_name}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${employee.email || 'N/A'}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${employee.phone || 'N/A'}</td></tr>
                            <tr><td><strong>Department:</strong></td><td>${employee.department || 'N/A'}</td></tr>
                            <tr><td><strong>Position:</strong></td><td>${employee.position || 'N/A'}</td></tr>
                            <tr><td><strong>Hire Date:</strong></td><td>${employee.hire_date || 'N/A'}</td></tr>
                            <tr><td><strong>Salary:</strong></td><td>PKR ${parseFloat(employee.salary || 0).toFixed(2)}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge ${employee.is_active ? 'bg-success' : 'bg-danger'}">${employee.is_active ? 'Active' : 'Inactive'}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Monthly Statistics</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Working Days:</strong></td><td>${stats.total_days || 0}</td></tr>
                            <tr><td><strong>Total Hours:</strong></td><td>${parseFloat(stats.total_hours || 0).toFixed(1)} hours</td></tr>
                            <tr><td><strong>Average Hours/Day:</strong></td><td>${parseFloat(stats.avg_hours || 0).toFixed(1)} hours</td></tr>
                        </table>
                        
                        <h6 class="mt-3">Recent Attendance</h6>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${attendanceRecords.map(record => `
                                        <tr>
                                            <td>${record.attendance_date}</td>
                                            <td>${parseFloat(record.total_hours || 0).toFixed(1)}h</td>
                                            <td><span class="badge ${record.logout_time ? 'bg-success' : 'bg-warning'}">${record.logout_time ? 'Complete' : 'In Progress'}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ${employee.address ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Address</h6>
                            <p>${employee.address}</p>
                        </div>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('employeeDetailsContent').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('viewEmployeeModal'));
            modal.show();
            
        } else {
            alert('Failed to load employee details: ' + data.message);
        }
        
    } catch (error) {
        console.error('Error loading employee details:', error);
        alert('Error loading employee details. Please try again.');
    }
}

async function editEmployee(employeeId) {
    try {
        const response = await fetch(`${API_BASE}/admin/get_employee_details.php?employee_id=${employeeId}`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const employee = data.data.employee;
            
            // Populate form fields
            document.getElementById('editEmployeeId').value = employee.employee_id;
            document.getElementById('editEmployeeNumber').value = employee.employee_number;
            document.getElementById('editFirstName').value = employee.first_name;
            document.getElementById('editLastName').value = employee.last_name;
            document.getElementById('editEmail').value = employee.email || '';
            document.getElementById('editPhone').value = employee.phone || '';
            document.getElementById('editDepartment').value = employee.department || '';
            document.getElementById('editPosition').value = employee.position || '';
            document.getElementById('editSalary').value = employee.salary || '';
            document.getElementById('editDateOfBirth').value = employee.date_of_birth || '';
            document.getElementById('editIsActive').value = employee.is_active ? '1' : '0';
            document.getElementById('editAddress').value = employee.address || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            modal.show();
            
        } else {
            alert('Failed to load employee details: ' + data.message);
        }
        
    } catch (error) {
        console.error('Error loading employee details:', error);
        alert('Error loading employee details. Please try again.');
    }
}

async function updateEmployee() {
    const form = document.getElementById('editEmployeeForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const employeeData = {
        employee_id: document.getElementById('editEmployeeId').value,
        first_name: document.getElementById('editFirstName').value.trim(),
        last_name: document.getElementById('editLastName').value.trim(),
        email: document.getElementById('editEmail').value.trim(),
        phone: document.getElementById('editPhone').value.trim(),
        department: document.getElementById('editDepartment').value.trim(),
        position: document.getElementById('editPosition').value.trim(),
        salary: parseFloat(document.getElementById('editSalary').value) || 0,
        date_of_birth: document.getElementById('editDateOfBirth').value,
        is_active: parseInt(document.getElementById('editIsActive').value),
        address: document.getElementById('editAddress').value.trim()
    };
    
    const spinner = document.getElementById('updateEmployeeSpinner');
    spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(`${API_BASE}/admin/update_employee.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(employeeData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Employee ${data.data.name} updated successfully!`);
            
            // Close modal and refresh employees list
            bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal')).hide();
            
            // Always refresh employees list and dashboard stats
            loadEmployees();
            loadDashboardStats();
        } else {
            alert(data.message || 'Failed to update employee');
        }
        
    } catch (error) {
        console.error('Error updating employee:', error);
        alert('Network error. Please try again.');
    } finally {
        spinner.classList.add('d-none');
    }
}

async function deleteEmployee(employeeId, employeeNumber) {
    if (confirm(`Are you sure you want to delete employee ${employeeNumber}?\n\nThis action cannot be undone and will remove all associated data including attendance records and GPS tracking data.`)) {
        try {
            const response = await fetch(`${API_BASE}/admin/delete_employee.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    employee_id: employeeId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(`Employee ${data.data.employee_number} (${data.data.name}) has been deleted successfully.`);
                
                // Refresh the employees list
                loadEmployees();
                
                // Update dashboard stats
                loadDashboardStats();
                
            } else {
                alert('Failed to delete employee: ' + data.message);
            }
            
        } catch (error) {
            console.error('Error deleting employee:', error);
            alert('Network error. Please try again.');
        }
    }
}

function viewAttendanceDetailsFromButton(buttonEl, date) {
    const d = buttonEl.dataset;
    const totalHoursRaw = parseFloat(d.totalHoursRaw || '0');
    const logoutPresent = d.logoutPresent === '1';
    let statusText = 'Present';
    let badgeClass = 'bg-success';
    if (!logoutPresent) {
        statusText = 'Logged In';
        badgeClass = 'bg-warning';
    } else if (totalHoursRaw < 4) {
        statusText = 'Partial';
        badgeClass = 'bg-info';
    }

    const content = `
        <table class="table table-sm">
            <tr><td><strong>Date</strong></td><td>${date}</td></tr>
            <tr><td><strong>Employee ID</strong></td><td>${d.employeeNumber}</td></tr>
            <tr><td><strong>Name</strong></td><td>${d.name}</td></tr>
            <tr><td><strong>Login Time</strong></td><td>${d.login}</td></tr>
            <tr><td><strong>Logout Time</strong></td><td>${d.logout || 'Still logged in'}</td></tr>
            <tr><td><strong>Total Hours</strong></td><td>${d.totalHours} hours</td></tr>
            <tr><td><strong>Status</strong></td><td><span class="badge ${badgeClass}">${statusText}</span></td></tr>
        </table>
    `;
    document.getElementById('attendanceDetailsContent').innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('attendanceDetailsModal'));
    modal.show();
}

async function viewAttendanceDetails(employeeNumber, date) {
    try {
        const response = await fetch(`${API_BASE}/admin/get_attendance.php?date=${date}&employee_number=${employeeNumber}`, {
            credentials: 'include'
        });
        const data = await response.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            const record = data.data[0];
            let statusText = 'Present';
            let badgeClass = 'bg-success';
            if (!record.logout_time) {
                statusText = 'Logged In';
                badgeClass = 'bg-warning';
            } else if (record.total_hours < 4) {
                statusText = 'Partial';
                badgeClass = 'bg-info';
            }
            const content = `
                <table class="table table-sm">
                    <tr><td><strong>Date</strong></td><td>${date}</td></tr>
                    <tr><td><strong>Employee ID</strong></td><td>${record.employee_number}</td></tr>
                    <tr><td><strong>Name</strong></td><td>${record.name}</td></tr>
                    <tr><td><strong>Login Time</strong></td><td>${record.login_time_formatted}</td></tr>
                    <tr><td><strong>Logout Time</strong></td><td>${record.logout_time_formatted || 'Still logged in'}</td></tr>
                    <tr><td><strong>Total Hours</strong></td><td>${record.total_hours_formatted} hours</td></tr>
                    <tr><td><strong>Status</strong></td><td><span class="badge ${badgeClass}">${statusText}</span></td></tr>
                </table>
            `;
            document.getElementById('attendanceDetailsContent').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('attendanceDetailsModal'));
            modal.show();
        } else {
            alert('No attendance details found for this entry');
        }
    } catch (error) {
        console.error('Error loading attendance details:', error);
        alert('Error loading attendance details. Please try again.');
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.clear();
        window.location.href = 'index.html';
    }
}

# emp_track_2

# Employee Tracking Application

A comprehensive employee tracking system that combines a Flutter mobile application with a PHP web backend. The system tracks employee attendance, GPS location, and provides admin management capabilities.

## Features

### Mobile Application (Flutter)
- **Employee Login**: Secure login with image capture and GPS location
- **Automatic GPS Tracking**: Location updates every 5 minutes while logged in
- **Background Services**: Continues tracking even when app is minimized
- **Logout with Image**: Captures image and location during logout
- **Real-time Dashboard**: Shows current location, working hours, and status

### Web Admin Interface
- **Admin Dashboard**: Overview of all employees and statistics
- **Employee Management**: Add, view, edit, and manage employees
- **Live GPS Tracking**: View employee locations on interactive maps
- **Attendance Records**: Monitor login/logout times and working hours
- **Location History**: Track employee movement throughout the day

### Backend API (PHP)
- **Authentication APIs**: Secure login/logout for employees and admins
- **GPS Tracking API**: Receives and stores location updates
- **Employee Management**: CRUD operations for employee data
- **Attendance Tracking**: Records and calculates working hours
- **Image Storage**: Handles login/logout image uploads

## Technology Stack

- **Mobile**: Flutter (Dart)
- **Backend**: PHP with MySQL
- **Frontend**: HTML, CSS, Bootstrap, JavaScript
- **Database**: MySQL
- **Maps**: Leaflet.js with OpenStreetMap
- **Server**: XAMPP (Apache, MySQL, PHP)

## Installation & Setup

### Prerequisites
- XAMPP (Apache, MySQL, PHP)
- Flutter SDK
- Android Studio / VS Code
- Git

### Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the database schema:
   ```sql
   -- Run the SQL file located at: database/emp_track_db.sql
   ```

### Backend Setup
1. The backend files are already in the correct XAMPP directory
2. Ensure the database connection settings in `backend/config/database.php` match your MySQL setup:
   ```php
   private $host = "localhost";
   private $db_name = "emp_track_db";
   private $username = "root";
   private $password = "";
   ```

### Web Admin Setup
1. Access the admin interface at: `http://localhost/emp_track_2/web_admin/`
2. Default admin credentials:
   - Username: `admin`
   - Password: `password`

### Mobile App Setup
1. Navigate to the project directory:
   ```bash
   cd c:\xampp\htdocs\emp_track_2
   ```

2. Install Flutter dependencies:
   ```bash
   flutter pub get
   ```

3. Update the API base URL in `lib/services/api_service.dart`:
   ```dart
   static const String baseUrl = 'http://YOUR_IP_ADDRESS/emp_track_2/backend/api';
   ```
   Replace `YOUR_IP_ADDRESS` with your computer's IP address for testing on physical devices.

4. Run the Flutter app:
   ```bash
   flutter run
   ```

## Default Credentials

### Admin Login
- Username: `admin`
- Password: `password`

### Sample Employee Login
- Employee ID: `EMP001`
- Password: `EMP001123`

- Employee ID: `EMP002`
- Password: `EMP002123`

## API Endpoints

### Authentication
- `POST /auth/login.php` - Employee login
- `POST /auth/logout.php` - Employee logout
- `POST /admin/admin_login.php` - Admin login

### GPS Tracking
- `POST /tracking/gps_update.php` - Send GPS location update

### Admin Operations
- `POST /admin/add_employee.php` - Add new employee
- `GET /admin/get_employee_location.php` - Get employee location data

## Database Schema

### Tables
- **admins**: Admin user credentials
- **employees**: Employee information and credentials
- **attendance**: Daily attendance records with login/logout times
- **gps_tracking**: GPS location points (every 5 minutes)
- **payroll**: Payroll calculations and records
- **employee_sessions**: Active employee sessions

## File Structure

```
emp_track_2/
├── android/                 # Android-specific Flutter files
├── lib/                     # Flutter application source
│   ├── screens/            # UI screens
│   ├── services/           # API and utility services
│   └── main.dart           # App entry point
├── backend/                # PHP backend
│   ├── api/               # REST API endpoints
│   └── config/            # Database configuration
├── web_admin/             # Web admin interface
│   ├── js/               # JavaScript files
│   ├── dashboard.html    # Admin dashboard
│   └── index.html        # Admin login
├── database/              # Database schema
└── README.md             # This file
```

## Usage Instructions

### For Employees (Mobile App)
1. Open the Employee Tracking app
2. Enter your Employee ID and password
3. Allow camera and location permissions
4. The app will capture your photo and GPS location on login
5. GPS tracking runs automatically every 5 minutes
6. Tap "Logout" when finished working

### For Admins (Web Interface)
1. Go to `http://localhost/emp_track_2/web_admin/`
2. Login with admin credentials
3. Use the dashboard to:
   - View employee statistics
   - Add new employees
   - Track live employee locations
   - Monitor attendance records
   - Generate reports

## Security Features

- Password hashing using PHP's `password_hash()`
- Session management for both employees and admins
- Input validation and sanitization
- CORS protection for API endpoints
- Secure image upload handling

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `backend/config/database.php`
   - Verify database exists and tables are created

2. **Flutter App Can't Connect to API**
   - Update IP address in `api_service.dart`
   - Ensure Apache is running
   - Check firewall settings

3. **GPS Not Working**
   - Enable location services on device
   - Grant location permissions to the app
   - Test on physical device (GPS doesn't work in emulator)

4. **Camera Not Working**
   - Grant camera permissions
   - Test on physical device
   - Ensure camera hardware is available

## Future Enhancements

- Push notifications for admin alerts
- Geofencing for work area boundaries
- Advanced reporting and analytics
- Mobile admin interface
- Integration with payroll systems
- Offline mode support

## Support

For technical support or questions, please refer to the documentation or contact the development team.

## License

This project is developed for educational and commercial purposes. Please ensure compliance with local privacy and employment laws when implementing employee tracking systems.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

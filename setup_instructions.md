# Quick Setup Guide - Employee Tracking Application

## Step-by-Step Setup Instructions

### 1. Database Setup (5 minutes)
1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services
   - Click "Admin" next to MySQL to open phpMyAdmin

2. **Create Database**
   - In phpMyAdmin, click "New" to create a new database
   - Name it: `emp_track_db`
   - Click "Create"

3. **Import Database Schema**
   - Select the `emp_track_db` database
   - Click "Import" tab
   - Choose file: `database/emp_track_db.sql`
   - Click "Go" to import

### 2. Test Backend APIs (2 minutes)
1. **Test Admin Login**
   - Open browser: `http://localhost/emp_track_2/web_admin/`
   - Login with: `admin` / `password`
   - You should see the admin dashboard

2. **Verify Database Connection**
   - If login works, your backend is properly configured
   - If not, check XAMPP services are running

### 3. Flutter Mobile App Setup (10 minutes)
1. **Install Dependencies**
   ```bash
   cd c:\xampp\htdocs\emp_track_2
   flutter pub get
   ```

2. **Update API Configuration**
   - Open `lib/services/api_service.dart`
   - Find line: `static const String baseUrl = 'http://localhost/emp_track_2/backend/api';`
   - For physical device testing, replace `localhost` with your computer's IP address
   - To find your IP: Run `ipconfig` in Command Prompt, look for IPv4 Address

3. **Run the App**
   ```bash
   flutter run
   ```
   - Choose your target device (emulator or physical device)
   - For GPS testing, use a physical device

### 4. Test Employee Login (3 minutes)
1. **Default Employee Credentials**
   - Employee ID: `EMP001`
   - Password: `EMP001123`

2. **Test Login Flow**
   - Open the Flutter app
   - Enter credentials
   - Allow camera and location permissions
   - Login should capture photo and GPS location

### 5. Test Admin Features (5 minutes)
1. **Add New Employee**
   - Go to admin dashboard
   - Click "Add Employee"
   - Fill in required fields
   - Note the generated password

2. **View Employee Location**
   - Go to "Live Tracking" section
   - Enter employee ID: `EMP001`
   - Select today's date
   - Click search to see location data

## Quick Troubleshooting

### Problem: "Database connection error"
**Solution:** 
- Ensure MySQL is running in XAMPP
- Check if database `emp_track_db` exists
- Verify tables were created (should see 6 tables)

### Problem: "Flutter app can't connect to API"
**Solution:**
- Ensure Apache is running in XAMPP
- Test API directly: `http://localhost/emp_track_2/backend/api/admin/admin_login.php`
- For physical device: Update IP address in `api_service.dart`

### Problem: "GPS not working in app"
**Solution:**
- Use physical device (GPS doesn't work in emulator)
- Enable location services on device
- Grant location permissions to app

### Problem: "Camera not working"
**Solution:**
- Use physical device
- Grant camera permissions
- Ensure device has camera hardware

## Testing Checklist

- [ ] XAMPP Apache and MySQL running
- [ ] Database created and tables imported
- [ ] Admin web interface accessible
- [ ] Admin login working
- [ ] Flutter dependencies installed
- [ ] Flutter app runs without errors
- [ ] Employee login captures photo and GPS
- [ ] Background GPS tracking working
- [ ] Employee logout captures photo
- [ ] Admin can view employee locations
- [ ] Admin can add new employees

## Default Credentials Summary

**Admin Login (Web):**
- URL: `http://localhost/emp_track_2/web_admin/`
- Username: `admin`
- Password: `password`

**Employee Login (Mobile):**
- Employee ID: `EMP001` | Password: `EMP001123`
- Employee ID: `EMP002` | Password: `EMP002123`

## Next Steps After Setup

1. **Customize for Production**
   - Change default admin password
   - Update database credentials
   - Configure proper server environment
   - Set up SSL certificates

2. **Add Real Employees**
   - Use admin interface to add actual employees
   - Provide employees with their credentials
   - Train employees on mobile app usage

3. **Monitor and Maintain**
   - Regularly backup database
   - Monitor GPS tracking accuracy
   - Review attendance reports
   - Update app as needed

## Support

If you encounter issues not covered here:
1. Check the main README.md for detailed documentation
2. Verify all prerequisites are installed
3. Ensure all services are running
4. Test each component individually

**Estimated Total Setup Time: 25 minutes**

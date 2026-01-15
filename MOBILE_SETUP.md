# Mobile Device Setup Guide

## Issue Fixed: Network Connection Error

The mobile app was showing "Network error: ClientException with SocketException: Connection refused" because it was trying to connect to `localhost`, which doesn't work on mobile devices.

## Solution Applied

1. **Updated API Base URL**: Changed from `localhost` to your computer's IP address (`10.234.150.102`)
2. **Location**: `lib/services/api_service.dart` - line 8

## Steps to Test on Mobile Device

### 1. Ensure XAMPP is Running
- Start **Apache** and **MySQL** services in XAMPP Control Panel
- Both should show "Running" status

### 2. Configure Windows Firewall (if needed)
If the mobile app still can't connect, you may need to allow Apache through Windows Firewall:

1. Open **Windows Defender Firewall with Advanced Security**
2. Click **Inbound Rules** → **New Rule**
3. Select **Port** → **TCP** → **Specific local ports: 80**
4. Allow the connection
5. Apply to all profiles
6. Name it "XAMPP Apache"

### 3. Test Connection from Mobile
1. **Rebuild the Flutter app**: Run `flutter build apk --debug`
2. **Install on device**: Connect your phone and run `flutter install`
3. **Test login** with credentials:
   - Employee ID: `EMP001` or `EMP002`
   - Password: `EMP001` or `EMP002`

### 4. Alternative: Test from Browser First
Before testing on mobile, verify the API works from your phone's browser:
- Open browser on your phone
- Go to: `http://10.234.150.102/emp_track_2/web_admin/`
- If this loads, the connection is working

## Troubleshooting

### If IP Address Changes
Your IP address might change if you reconnect to WiFi. If the app stops working:

1. **Check current IP**: Run `ipconfig` in Command Prompt
2. **Update API service**: Change the IP in `lib/services/api_service.dart`
3. **Rebuild app**: Run `flutter build apk --debug`

### Common Issues

1. **Connection Timeout**: 
   - Ensure both devices are on the same WiFi network
   - Check Windows Firewall settings

2. **XAMPP Not Accessible**:
   - Restart Apache service in XAMPP
   - Check if port 80 is being used by another service

3. **Database Connection Issues**:
   - Ensure MySQL is running in XAMPP
   - Run the database setup: `http://10.234.150.102/emp_track_2/backend/setup_database.php`

## Current Configuration

- **Computer IP**: `10.234.150.102`
- **API Base URL**: `http://10.234.150.102/emp_track_2/backend/api`
- **Web Admin**: `http://10.234.150.102/emp_track_2/web_admin/`
- **Database Setup**: `http://10.234.150.102/emp_track_2/backend/setup_database.php`

## Test Credentials

**Admin Login** (Web Interface):
- Username: `admin`
- Password: `password`

**Employee Login** (Mobile App):
- Employee ID: `EMP001`, Password: `EMP001`
- Employee ID: `EMP002`, Password: `EMP002`

---

**Note**: Make sure both your computer and mobile device are connected to the same WiFi network for this to work.

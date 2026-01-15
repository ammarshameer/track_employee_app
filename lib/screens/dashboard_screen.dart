import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:flutter_spinkit/flutter_spinkit.dart';
import 'dart:async';
import '../services/api_service.dart';
import '../services/gps_service.dart';
import '../services/camera_service.dart';
import 'login_screen.dart';

class DashboardScreen extends StatefulWidget {
  final Map<String, dynamic> employeeData;

  const DashboardScreen({
    super.key,
    required this.employeeData,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _isLoading = false;
  Position? _currentLocation;
  DateTime? _loginTime;
  bool _isTrackingActive = false;
  Timer? _gpsTimer;

  @override
  void initState() {
    super.initState();
    _initializeDashboard();
  }

  Future<void> _initializeDashboard() async {
    setState(() {
      _isTrackingActive = true;
      final lt = widget.employeeData['login_time'];
      _loginTime = (lt is String && lt.isNotEmpty)
          ? (DateTime.tryParse(lt) ?? DateTime.now())
          : DateTime.now();
    });

    // Start background GPS tracking
    await _startBackgroundTracking();
    
    // Get initial location
    await _updateCurrentLocation();

    if (_currentLocation == null) {
      int attempts = 0;
      Timer.periodic(const Duration(seconds: 2), (t) async {
        attempts++;
        if (!mounted || attempts >= 8 || _currentLocation != null) {
          t.cancel();
          return;
        }
        await _updateCurrentLocation();
      });
    }
  }

  Future<void> _startBackgroundTracking() async {
    try {
      // Start periodic GPS updates using Timer
      _gpsTimer = Timer.periodic(const Duration(minutes: 5), (timer) async {
        try {
          await GPSService.sendLocationUpdate();
          print('GPS location updated');
        } catch (e) {
          print('GPS update error: $e');
        }
      });

      // Start GPS service
      await GPSService.startGPSTracking();
      
      print('GPS tracking started with 5-minute intervals');
    } catch (e) {
      print('Error starting GPS tracking: $e');
    }
  }

  Future<void> _updateCurrentLocation() async {
    try {
      final position = await GPSService.getCurrentLocationForAuth();
      if (position != null && mounted) {
        setState(() {
          _currentLocation = position;
        });
      }
    } catch (e) {
      print('Error updating location: $e');
    }
  }

  Future<void> _logout() async {
    setState(() {
      _isLoading = true;
    });

    try {
      // Get current location for logout
      Position? position = await GPSService.getCurrentLocationForAuth();
      
      // Capture logout image
      String? logoutImage = await CameraService.captureImage();
      
      if (logoutImage == null) {
        _showErrorDialog('Failed to capture logout image');
        setState(() {
          _isLoading = false;
        });
        return;
      }

      // Prepare logout data
      Map<String, dynamic> logoutData = {
        'session_id': widget.employeeData['session_id'],
        'latitude': position?.latitude,
        'longitude': position?.longitude,
        'logout_image': logoutImage,
        'device_time_iso': DateTime.now().toIso8601String(),
        'timezone_offset_minutes': DateTime.now().timeZoneOffset.inMinutes,
      };

      // Call logout API
      final response = await ApiService.logout(logoutData);
      
      if (response['success']) {
        // Stop background tracking
        await _stopBackgroundTracking();
        
        // Clear session data
        await ApiService.clearSessionData();
        
        // Show success message
        final dt = response['data'];
        final msg = 'Logout Time: ${dt['logout_time'] ?? ''}\nLogin Time: ${dt['login_time'] ?? ''}\nWork Duration: ${dt['duration_hms'] ?? (dt['total_hours']?.toString() ?? '0') + ' hours'}';
        _showSuccessDialog('Logout Successful', msg);
        
      } else {
        _showErrorDialog(response['message'] ?? 'Logout failed');
      }
      
    } catch (e) {
      _showErrorDialog('Network error: ${e.toString()}');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _stopBackgroundTracking() async {
    try {
      // Cancel GPS timer
      _gpsTimer?.cancel();
      _gpsTimer = null;
      
      // Stop GPS service
      await GPSService.stopGPSTracking();
      
      setState(() {
        _isTrackingActive = false;
      });
      
      print('GPS tracking stopped');
    } catch (e) {
      print('Error stopping GPS tracking: $e');
    }
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showSuccessDialog(String title, String message) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _navigateToLogin();
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _navigateToLogin() {
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (context) => const LoginScreen()),
      (route) => false,
    );
  }

  String _formatDuration(DateTime startTime) {
    final duration = DateTime.now().difference(startTime);
    final hours = duration.inHours;
    final minutes = duration.inMinutes % 60;
    final seconds = duration.inSeconds % 60;
    return '${hours.toString().padLeft(2, '0')}:${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  String _formatDurationHMS(DateTime startTime) => _formatDuration(startTime);
  String _formatDateTime(DateTime dt) {
    final y = dt.year.toString().padLeft(4, '0');
    final m = dt.month.toString().padLeft(2, '0');
    final d = dt.day.toString().padLeft(2, '0');
    final h = dt.hour.toString().padLeft(2, '0');
    final mi = dt.minute.toString().padLeft(2, '0');
    final s = dt.second.toString().padLeft(2, '0');
    return '$y-$m-$d $h:$mi:$s';
  }

  @override
  void dispose() {
    _gpsTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        title: const Text('Employee Dashboard'),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _updateCurrentLocation,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Employee Info Card
            Card(
              elevation: 4,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor: Colors.blue,
                      child: Text(
                        widget.employeeData['name']?.substring(0, 1).toUpperCase() ?? 'E',
                        style: const TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      widget.employeeData['name'] ?? 'Employee',
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'ID: ${widget.employeeData['employee_number'] ?? 'N/A'}',
                      style: TextStyle(
                        fontSize: 16,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Status Card
            Card(
              elevation: 4,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: _isTrackingActive ? Colors.green : Colors.red,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _isTrackingActive ? 'Tracking Active' : 'Tracking Inactive',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    if (_loginTime != null) ...[
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Login Time:', style: TextStyle(fontSize: 16)),
                          Text(
                            _formatDateTime(_loginTime!),
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Working Time:', style: TextStyle(fontSize: 16)),
                          Text(
                            _formatDurationHMS(_loginTime!),
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.blue),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Location Card
            Card(
              elevation: 4,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.location_on, color: Colors.red),
                        SizedBox(width: 8),
                        Text(
                          'Current Location',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    if (_currentLocation != null) ...[
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Latitude:', style: TextStyle(fontSize: 14)),
                          Text(
                            _currentLocation!.latitude.toStringAsFixed(6),
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Longitude:', style: TextStyle(fontSize: 14)),
                          Text(
                            _currentLocation!.longitude.toStringAsFixed(6),
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Accuracy:', style: TextStyle(fontSize: 14)),
                          Text(
                            '${_currentLocation!.accuracy.toStringAsFixed(1)}m',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ] else ...[
                      const Center(
                        child: Text(
                          'Getting location...',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Info Card
            Card(
              elevation: 2,
              color: Colors.blue.shade50,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: Colors.blue.shade700,
                      size: 24,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Your location is being tracked every 5 minutes while you are logged in. This helps maintain accurate attendance records.',
                      style: TextStyle(
                        color: Colors.blue.shade700,
                        fontSize: 12,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // Logout Button
            ElevatedButton(
              onPressed: _isLoading ? null : _logout,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: _isLoading
                  ? const SpinKitThreeBounce(
                      color: Colors.white,
                      size: 20,
                    )
                  : const Text(
                      'Logout',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:geolocator/geolocator.dart';
import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class GPSService {
  static const String _lastUpdateKey = 'last_gps_update';
  static const int updateIntervalMinutes = 5;

  // Send location update to server
  static Future<void> sendLocationUpdate() async {
    try {
      // Check if user is logged in
      final sessionData = await ApiService.getSessionData();
      if (sessionData == null) {
        print('No active session for GPS tracking');
        return;
      }

      // Check if enough time has passed since last update
      if (!await _shouldSendUpdate()) {
        print('GPS update not due yet');
        return;
      }

      // Get current location
      Position? position = await _getCurrentLocation();
      if (position == null) {
        print('Could not get current location');
        return;
      }

      // Prepare GPS data
      Map<String, dynamic> gpsData = {
        'session_id': sessionData['session_id'],
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy': position.accuracy,
        'speed': position.speed,
        'altitude': position.altitude,
      };

      // Send to server
      final response = await ApiService.sendGPSUpdate(gpsData);
      
      if (response['success']) {
        await _updateLastUpdateTime();
        print('GPS location updated successfully');
      } else {
        print('GPS update failed: ${response['message']}');
      }

    } catch (e) {
      print('GPS service error: $e');
    }
  }

  // Get current GPS location
  static Future<Position?> _getCurrentLocation() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        print('Location services are disabled');
        await Geolocator.openLocationSettings();
        return null;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          print('Location permissions are denied');
          return null;
        }
      }
      if (permission == LocationPermission.deniedForever) {
        print('Location permissions are permanently denied');
        return null;
      }

      final lastKnown = await Geolocator.getLastKnownPosition();
      if (lastKnown != null) {
        return lastKnown;
      }

      try {
        return await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.best,
          timeLimit: const Duration(seconds: 8),
        );
      } catch (e) {
        if (Platform.isAndroid) {
          try {
            return await Geolocator.getPositionStream(
              locationSettings: AndroidSettings(
                accuracy: LocationAccuracy.best,
                distanceFilter: 0,
                intervalDuration: Duration(seconds: 1),
                forceLocationManager: true,
              ),
            ).first;
          } catch (_) {}
        }
        rethrow;
      }
    } catch (e) {
      print('Location error: $e');
      return null;
    }
  }

  // Check if we should send update based on time interval
  static Future<bool> _shouldSendUpdate() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastUpdateStr = prefs.getString(_lastUpdateKey);
      
      if (lastUpdateStr == null) {
        return true; // First update
      }

      final lastUpdate = DateTime.parse(lastUpdateStr);
      final now = DateTime.now();
      final difference = now.difference(lastUpdate);

      return difference.inMinutes >= updateIntervalMinutes;
    } catch (e) {
      print('Error checking update interval: $e');
      return true; // Default to allowing update
    }
  }

  // Update the last update time
  static Future<void> _updateLastUpdateTime() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastUpdateKey, DateTime.now().toIso8601String());
    } catch (e) {
      print('Error updating last update time: $e');
    }
  }

  // Start continuous GPS tracking
  static Future<void> startGPSTracking() async {
    try {
      // Send initial update
      await sendLocationUpdate();
      
      print('GPS tracking started');
    } catch (e) {
      print('Error starting GPS tracking: $e');
    }
  }

  // Stop GPS tracking
  static Future<void> stopGPSTracking() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_lastUpdateKey);
      
      print('GPS tracking stopped');
    } catch (e) {
      print('Error stopping GPS tracking: $e');
    }
  }

  // Get location for immediate use (like login/logout)
  static Future<Position?> getCurrentLocationForAuth() async {
    return await _getCurrentLocation();
  }
}

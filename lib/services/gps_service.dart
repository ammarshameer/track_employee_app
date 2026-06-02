import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class GPSService {
  static const String _lastUpdateKey = 'last_gps_update';
  static const int updateIntervalMinutes = 5;

  // Send location update to server
  static Future<bool> sendLocationUpdate() async {
    try {
      final sessionData = await ApiService.getSessionData();
      if (sessionData == null) {
        debugPrint('GPSService: No active session for GPS tracking');
        return false;
      }

      if (!await _shouldSendUpdate()) {
        return false;
      }

      Position? position = await _getCurrentLocation();
      if (position == null) {
        debugPrint('GPSService: Could not get current location');
        return false;
      }

      Map<String, dynamic> gpsData = {
        'session_id': sessionData['session_id'],
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy': position.accuracy,
        'speed': position.speed,
        'altitude': position.altitude,
      };

      final response = await ApiService.sendGPSUpdate(gpsData);
      
      if (response['success'] == true) {
        await _updateLastUpdateTime();
        return true;
      } else {
        debugPrint('GPSService: Update failed: ${response['message']}');
        return false;
      }
    } catch (e) {
      debugPrint('GPSService: Error sending location update: $e');
      return false;
    }
  }

  // Get current GPS location
  static Future<Position?> _getCurrentLocation() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        debugPrint('GPSService: Location services are disabled');
        await Geolocator.openLocationSettings();
        return null;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          debugPrint('GPSService: Location permissions are denied');
          return null;
        }
      }
      if (permission == LocationPermission.deniedForever) {
        debugPrint('GPSService: Location permissions are permanently denied');
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
          } catch (streamError) {
            debugPrint('GPSService: Stream fallback also failed: $streamError');
          }
        }
        rethrow;
      }
    } catch (e) {
      debugPrint('GPSService: Location error: $e');
      return null;
    }
  }

  // Check if we should send update based on time interval
  static Future<bool> _shouldSendUpdate() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastUpdateStr = prefs.getString(_lastUpdateKey);
      
      if (lastUpdateStr == null) {
        return true;
      }

      final lastUpdate = DateTime.tryParse(lastUpdateStr);
      if (lastUpdate == null) {
        debugPrint('GPSService: Invalid stored update timestamp, allowing update');
        return true;
      }

      final now = DateTime.now();
      final difference = now.difference(lastUpdate);

      return difference.inMinutes >= updateIntervalMinutes;
    } catch (e) {
      debugPrint('GPSService: Error checking update interval: $e');
      return true;
    }
  }

  static Future<void> _updateLastUpdateTime() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastUpdateKey, DateTime.now().toIso8601String());
    } catch (e) {
      debugPrint('GPSService: Error updating last update time: $e');
    }
  }

  // Start continuous GPS tracking
  static Future<bool> startGPSTracking() async {
    try {
      final result = await sendLocationUpdate();
      debugPrint('GPSService: Tracking started (initial update: $result)');
      return result;
    } catch (e) {
      debugPrint('GPSService: Error starting GPS tracking: $e');
      return false;
    }
  }

  // Stop GPS tracking
  static Future<void> stopGPSTracking() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_lastUpdateKey);
      debugPrint('GPSService: Tracking stopped');
    } catch (e) {
      debugPrint('GPSService: Error stopping GPS tracking: $e');
      rethrow;
    }
  }

  // Get location for immediate use (like login/logout)
  static Future<Position?> getCurrentLocationForAuth() async {
    return await _getCurrentLocation();
  }
}

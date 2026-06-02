import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'location_service.dart';

class GPSService {
  static const String _lastUpdateKey = 'last_gps_update';
  static const int updateIntervalMinutes = 5;

  static Future<void> sendLocationUpdate() async {
    try {
      final sessionData = await ApiService.getSessionData();
      if (sessionData == null) {
        print('No active session for GPS tracking');
        return;
      }

      if (!await _shouldSendUpdate()) {
        print('GPS update not due yet');
        return;
      }

      Position? position = await LocationService.getCurrentLocation(
        openSettingsOnDisabled: true,
      );
      if (position == null) {
        print('Could not get current location');
        return;
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

  static Future<bool> _shouldSendUpdate() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lastUpdateStr = prefs.getString(_lastUpdateKey);
      
      if (lastUpdateStr == null) {
        return true;
      }

      final lastUpdate = DateTime.parse(lastUpdateStr);
      final now = DateTime.now();
      final difference = now.difference(lastUpdate);

      return difference.inMinutes >= updateIntervalMinutes;
    } catch (e) {
      print('Error checking update interval: $e');
      return true;
    }
  }

  static Future<void> _updateLastUpdateTime() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastUpdateKey, DateTime.now().toIso8601String());
    } catch (e) {
      print('Error updating last update time: $e');
    }
  }

  static Future<void> startGPSTracking() async {
    try {
      await sendLocationUpdate();
      print('GPS tracking started');
    } catch (e) {
      print('Error starting GPS tracking: $e');
    }
  }

  static Future<void> stopGPSTracking() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_lastUpdateKey);
      print('GPS tracking stopped');
    } catch (e) {
      print('Error stopping GPS tracking: $e');
    }
  }

  static Future<Position?> getCurrentLocationForAuth() async {
    return await LocationService.getCurrentLocation();
  }
}

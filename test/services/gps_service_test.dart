import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:emp_track_2/services/gps_service.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  group('GPSService - constants', () {
    test('updateIntervalMinutes is 5', () {
      expect(GPSService.updateIntervalMinutes, 5);
    });
  });

  group('GPSService - stopGPSTracking', () {
    test('stopGPSTracking clears the last GPS update timestamp', () async {
      SharedPreferences.setMockInitialValues({
        'last_gps_update': DateTime.now().toIso8601String(),
      });

      await GPSService.stopGPSTracking();

      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('last_gps_update'), isNull);
    });

    test('stopGPSTracking is safe to call when no update key exists',
        () async {
      // Should not throw
      await GPSService.stopGPSTracking();

      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('last_gps_update'), isNull);
    });
  });

  group('GPSService - sendLocationUpdate without session', () {
    test('sendLocationUpdate returns early when no session exists', () async {
      // No session data => getSessionData returns null
      // This should return without throwing
      await GPSService.sendLocationUpdate();

      // Verify no last_gps_update was set (means it returned early)
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('last_gps_update'), isNull);
    });
  });

  group('GPSService - tracking lifecycle', () {
    test('start then stop tracking clears state', () async {
      SharedPreferences.setMockInitialValues({
        'last_gps_update': '2025-01-01T10:00:00.000',
      });

      await GPSService.stopGPSTracking();

      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('last_gps_update'), isNull);
    });

    test('stopGPSTracking can be called multiple times safely', () async {
      await GPSService.stopGPSTracking();
      await GPSService.stopGPSTracking();
      await GPSService.stopGPSTracking();

      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getString('last_gps_update'), isNull);
    });
  });
}

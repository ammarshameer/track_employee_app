import 'package:geolocator/geolocator.dart';
import 'dart:io' show Platform;

/// Shared location helper used by both the login flow and the GPS
/// background service. Consolidates the duplicated permission-check +
/// getCurrentPosition logic that previously lived in both
/// `login_screen.dart` and `gps_service.dart`.
class LocationService {
  /// Obtain the device's current position.
  ///
  /// Returns `null` (instead of throwing) when location services or
  /// permissions are unavailable so callers can degrade gracefully.
  static Future<Position?> getCurrentLocation({
    bool openSettingsOnDisabled = false,
  }) async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        if (openSettingsOnDisabled) {
          await Geolocator.openLocationSettings();
        }
        return null;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return null;
        }
      }
      if (permission == LocationPermission.deniedForever) {
        return null;
      }

      // Try last known position first (fast path).
      final lastKnown = await Geolocator.getLastKnownPosition();
      if (lastKnown != null) {
        return lastKnown;
      }

      // Fall back to a live fix.
      try {
        return await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
          timeLimit: const Duration(seconds: 8),
        );
      } catch (e) {
        // Android-specific fallback via the fused location stream.
        try {
          if (Platform.isAndroid) {
            return await Geolocator.getPositionStream(
              locationSettings: AndroidSettings(
                accuracy: LocationAccuracy.best,
                distanceFilter: 0,
                intervalDuration: const Duration(seconds: 1),
                forceLocationManager: true,
              ),
            ).first;
          }
        } catch (_) {}
        return null;
      }
    } catch (e) {
      return null;
    }
  }
}

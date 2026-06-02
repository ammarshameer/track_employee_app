import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;

/// Lightweight platform-detection helpers shared across the app.
///
/// Previously the same `kIsWeb` / `Platform.isAndroid` checks were
/// duplicated in `login_screen.dart` and `api_service.dart`.
class DeviceUtils {
  /// Human-readable device descriptor (e.g. "Android Device").
  static String getDeviceInfo() {
    if (kIsWeb) return 'Web Device';
    try {
      if (Platform.isAndroid) return 'Android Device';
      if (Platform.isIOS) return 'iOS Device';
    } catch (_) {}
    return 'Unknown Device';
  }

  /// `true` when running on a physical Android device / emulator.
  static bool get isAndroid {
    if (kIsWeb) return false;
    try {
      return Platform.isAndroid;
    } catch (_) {
      return false;
    }
  }
}

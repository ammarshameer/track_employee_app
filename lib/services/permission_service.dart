import 'package:permission_handler/permission_handler.dart' as ph;

class PermissionService {
  static Future<Map<String, bool>> requestAllPermissions() async {
    final location = await _requestLocationPermissions();
    final camera = await _requestCameraPermission();
    final storage = await _requestStoragePermissions();

    return {
      'location': location,
      'camera': camera,
      'storage': storage,
    };
  }

  static Future<bool> _requestLocationPermissions() async {
    Map<ph.Permission, ph.PermissionStatus> statuses = await [
      ph.Permission.location,
      ph.Permission.locationWhenInUse,
      ph.Permission.locationAlways,
    ].request();

    return statuses[ph.Permission.location]?.isGranted == true ||
           statuses[ph.Permission.locationWhenInUse]?.isGranted == true;
  }

  static Future<bool> _requestCameraPermission() async {
    ph.PermissionStatus status = await ph.Permission.camera.request();
    return status.isGranted;
  }

  static Future<bool> _requestStoragePermissions() async {
    Map<ph.Permission, ph.PermissionStatus> statuses = await [
      ph.Permission.storage,
      ph.Permission.photos,
    ].request();

    return statuses[ph.Permission.storage]?.isGranted == true ||
           statuses[ph.Permission.photos]?.isGranted == true;
  }

  static Future<bool> hasLocationPermission() async {
    return await ph.Permission.location.isGranted ||
           await ph.Permission.locationWhenInUse.isGranted;
  }

  static Future<bool> hasCameraPermission() async {
    return await ph.Permission.camera.isGranted;
  }

  static Future<bool> hasStoragePermission() async {
    return await ph.Permission.storage.isGranted ||
           await ph.Permission.photos.isGranted;
  }

  static Future<bool> openSettings() async {
    return await ph.openAppSettings();
  }
}

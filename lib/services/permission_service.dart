import 'package:permission_handler/permission_handler.dart';

class PermissionService {
  static Future<void> requestAllPermissions() async {
    // Request location permissions
    await _requestLocationPermissions();
    
    // Request camera permission
    await _requestCameraPermission();
    
    // Request storage permissions
    await _requestStoragePermissions();
  }

  static Future<bool> _requestLocationPermissions() async {
    Map<Permission, PermissionStatus> statuses = await [
      Permission.location,
      Permission.locationWhenInUse,
      Permission.locationAlways,
    ].request();

    return statuses[Permission.location]?.isGranted == true ||
           statuses[Permission.locationWhenInUse]?.isGranted == true;
  }

  static Future<bool> _requestCameraPermission() async {
    PermissionStatus status = await Permission.camera.request();
    return status.isGranted;
  }

  static Future<bool> _requestStoragePermissions() async {
    Map<Permission, PermissionStatus> statuses = await [
      Permission.storage,
      Permission.photos,
    ].request();

    return statuses[Permission.storage]?.isGranted == true ||
           statuses[Permission.photos]?.isGranted == true;
  }

  static Future<bool> hasLocationPermission() async {
    return await Permission.location.isGranted ||
           await Permission.locationWhenInUse.isGranted;
  }

  static Future<bool> hasCameraPermission() async {
    return await Permission.camera.isGranted;
  }

  static Future<bool> hasStoragePermission() async {
    return await Permission.storage.isGranted ||
           await Permission.photos.isGranted;
  }

  static Future<void> openAppSettings() async {
    await openAppSettings();
  }
}

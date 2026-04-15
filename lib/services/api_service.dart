import 'dart:convert';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String _androidDefault = 'http://192.168.18.193/emp_track_app/backend/api';
  static const String _desktopDefault = 'http://localhost/emp_track_2/backend/api';
  static const String _prefsKey = 'api_base_url';

  static Future<String> _resolveBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null && saved.isNotEmpty) return saved;
    
    // Check for web platform first (Platform is not available on web)
    if (kIsWeb) {
      return _desktopDefault;
    }
    
    // For mobile platforms, check Android
    try {
      if (Platform.isAndroid) return _androidDefault;
    } catch (_) {
      // Platform not supported, use default
    }
    
    return _desktopDefault;
  }

  static Future<void> setBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, url);
  }

  static Future<String?> getSavedBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_prefsKey);
  }
  
  // Login API
  static Future<Map<String, dynamic>> login(Map<String, dynamic> loginData) async {
    try {
      final baseUrl = await _resolveBaseUrl();
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode(loginData),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        final errorData = json.decode(response.body);
        return {
          'success': false,
          'message': errorData['message'] ?? 'Login failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }

  // Logout API
  static Future<Map<String, dynamic>> logout(Map<String, dynamic> logoutData) async {
    try {
      final baseUrl = await _resolveBaseUrl();
      final response = await http.post(
        Uri.parse('$baseUrl/auth/logout.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode(logoutData),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        final errorData = json.decode(response.body);
        return {
          'success': false,
          'message': errorData['message'] ?? 'Logout failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }

  // Send GPS Update
  static Future<Map<String, dynamic>> sendGPSUpdate(Map<String, dynamic> gpsData) async {
    try {
      final baseUrl = await _resolveBaseUrl();
      final response = await http.post(
        Uri.parse('$baseUrl/tracking/gps_update.php'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode(gpsData),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        final errorData = json.decode(response.body);
        return {
          'success': false,
          'message': errorData['message'] ?? 'GPS update failed',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }

  // Save session data locally
  static Future<void> saveSessionData(Map<String, dynamic> sessionData) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('session_id', sessionData['session_id'] ?? '');
    await prefs.setString('employee_id', sessionData['employee_id'].toString());
    await prefs.setString('employee_number', sessionData['employee_number'] ?? '');
    await prefs.setString('employee_name', sessionData['name'] ?? '');
    await prefs.setBool('is_logged_in', true);
  }

  // Get session data
  static Future<Map<String, dynamic>?> getSessionData() async {
    final prefs = await SharedPreferences.getInstance();
    final isLoggedIn = prefs.getBool('is_logged_in') ?? false;
    
    if (!isLoggedIn) return null;
    
    return {
      'session_id': prefs.getString('session_id') ?? '',
      'employee_id': prefs.getString('employee_id') ?? '',
      'employee_number': prefs.getString('employee_number') ?? '',
      'employee_name': prefs.getString('employee_name') ?? '',
    };
  }

  // Clear session data
  static Future<void> clearSessionData() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('session_id');
    await prefs.remove('employee_id');
    await prefs.remove('employee_number');
    await prefs.remove('employee_name');
    await prefs.setBool('is_logged_in', false);
  }

  // Check if user is logged in
  static Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool('is_logged_in') ?? false;
  }
}

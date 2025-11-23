import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/environment.dart';

class ApiService {
  static Future<bool> storeFcmToken({
    required int userId,
    required String fcmToken,
    required String deviceType,
    String? deviceId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${Environment.apiBaseUrl}/mobile/fcm-token'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer ${Environment.bearerToken}',
        },
        body: jsonEncode({
          'user_id': userId,
          'fcm_token': fcmToken,
          'device_type': deviceType,
          'device_id': deviceId,
        }),
      );

      return response.statusCode == 200;
    } catch (e) {
      print('Error storing FCM token: $e');
      return false;
    }
  }

  static Future<bool> removeFcmToken({required int userId}) async {
    try {
      final response = await http.delete(
        Uri.parse('${Environment.apiBaseUrl}/mobile/fcm-token'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer ${Environment.bearerToken}',
        },
        body: jsonEncode({
          'user_id': userId,
        }),
      );

      return response.statusCode == 200;
    } catch (e) {
      print('Error removing FCM token: $e');
      return false;
    }
  }
}

import 'package:flutter/foundation.dart';

class Environment {
  static const String _defaultBaseUrl =
      kReleaseMode ? 'https://iracket.se' : 'https://dev.iracket.se';

  static const String laravelBaseUrl = String.fromEnvironment(
    'LARAVEL_URL',
    defaultValue: _defaultBaseUrl,
  );

  static const String apiBaseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: '$_defaultBaseUrl/api',
  );

  static const String bearerToken = String.fromEnvironment(
    'BEARER_TOKEN',
    defaultValue: '',
  );

  static const bool isProduction = kReleaseMode;
}

class Environment {
  static const String laravelBaseUrl = String.fromEnvironment(
    'LARAVEL_URL',
    defaultValue: 'https://iracket.ddev.site',
  );

  static const String apiBaseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'https://iracket.ddev.site/api',
  );

  static const String bearerToken = String.fromEnvironment(
    'BEARER_TOKEN',
    defaultValue: '',
  );

  static const bool isProduction = String.fromEnvironment(
    'ENV',
    defaultValue: 'development',
  ) == 'production';
}

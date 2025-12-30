class Environment {
  static const String laravelBaseUrl = String.fromEnvironment(
    'LARAVEL_URL',
    defaultValue: 'https://indorsable-prophetically-eugena.ngrok-free.dev',
  );

  static const String apiBaseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'https://indorsable-prophetically-eugena.ngrok-free.dev/api',
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

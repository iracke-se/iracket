// Conditional export based on platform
export 'web_redirect_screen_stub.dart'
  if (dart.library.html) 'web_redirect_screen_web.dart';

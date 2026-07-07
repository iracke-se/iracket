import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import '../config/environment.dart';
import '../services/fcm_service.dart';

const _accentGreen = Color(0xFF34C759);
const _darkSurface = Color(0xFF18181B);

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _isDarkMode = false;
  String? _cachedFcmToken;

  /// User-agent marker appended so the Laravel site can reliably identify the
  /// in-app WebView on EVERY request (initial load and all subsequent
  /// navigations). This is required because the default iPad WKWebView
  /// user-agent reports as desktop "Macintosh … Safari" — it contains neither
  /// "iPhone"/"iPad" nor lacks "Safari" — so heuristic UA sniffing fails there.
  /// The server redirects any request carrying this marker away from the public
  /// marketing home page (which shows a Google Play badge) — App Store 2.3.10.
  /// The site is responsive by viewport width, so the UA string does not affect
  /// layout.
  String get _appUserAgent {
    const marker = 'iRacketApp/1.0';
    if (Platform.isAndroid) {
      return 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 '
          '(KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 $marker';
    }
    return 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) '
        'AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 $marker';
  }

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() {
    // Detect system theme mode
    final brightness = WidgetsBinding.instance.platformDispatcher.platformBrightness;
    _isDarkMode = brightness == Brightness.dark;
    final themeMode = _isDarkMode ? 'dark' : 'light';

    // Build URL with theme + app-source parameters. `source=app` guarantees the
    // Laravel home page redirects the app to /login on the very first load,
    // independent of any user-agent heuristics.
    final uri = Uri.parse(Environment.laravelBaseUrl);
    final urlWithTheme = uri.replace(
      queryParameters: {
        ...uri.queryParameters,
        'app_theme': themeMode,
        'source': 'app',
      },
    ).toString();

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setUserAgent(_appUserAgent)
      ..setBackgroundColor(_isDarkMode ? _darkSurface : Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            if (!mounted) return;
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) async {
            if (!mounted) return;
            setState(() {
              _isLoading = false;
            });

            // Inject FCM token and theme mode into JavaScript context
            await _injectFcmToken();
            await _injectThemeMode(themeMode);
          },
          onNavigationRequest: (NavigationRequest request) {
            final path = Uri.tryParse(request.url)?.path ?? '';

            // Intercept social sign-in. Google (and increasingly Apple) block
            // OAuth inside embedded WebViews, so we hand the flow off to the
            // system's secure browser (ASWebAuthenticationSession) and return
            // to the app via the iracket:// custom scheme.
            if (path == '/auth/google' || path == '/auth/apple') {
              final provider = path == '/auth/google' ? 'google' : 'apple';
              _handleOAuth(provider);
              return NavigationDecision.prevent;
            }

            // Keep our own pages inside the WebView; send everything else
            // (external sites, mailto:, tel:) to the system so links never
            // dead-end with an unresponsive tap.
            if (!request.url.startsWith(Environment.laravelBaseUrl)) {
              _openExternal(request.url);
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
          onWebResourceError: (WebResourceError error) {
            print('WebView error: ${error.description}');
            // A failed main-frame load means onPageFinished never fires,
            // which would otherwise leave the full-screen loading overlay
            // stuck forever.
            if ((error.isForMainFrame ?? true) && mounted) {
              setState(() {
                _isLoading = false;
              });
            }
          },
        ),
      )
      ..addJavaScriptChannel(
        'FlutterChannel',
        onMessageReceived: (JavaScriptMessage message) {
          _handleJavaScriptMessage(message.message);
        },
      );

    // Android's WebViewController defaults useWideViewPort to false, which
    // makes the WebView ignore the page's <meta name="viewport"> tag and
    // render a fixed ~980px desktop layout scaled down to fit — producing
    // tiny, zoomed-out content despite the site's viewport tag being correct.
    // iOS's WKWebView has no such default and needs no equivalent fix.
    if (Platform.isAndroid && _controller.platform is AndroidWebViewController) {
      (_controller.platform as AndroidWebViewController)
          .setUseWideViewPort(true);
    }

    _controller.loadRequest(Uri.parse(urlWithTheme));
  }

  Future<void> _injectFcmToken() async {
    // Cache once a token is actually available, but keep re-checking
    // SharedPreferences on every navigation until then — Firebase/FCM
    // initialization in main.dart runs concurrently and independently, and
    // can easily finish after the WebView's first page load (it waits on an
    // iOS permission prompt first), so caching a null result permanently
    // would silently disable FCM token delivery for the rest of the session.
    _cachedFcmToken ??= await FcmService.getToken();
    final fcmToken = _cachedFcmToken;
    if (fcmToken != null) {
      String deviceType = Platform.isAndroid ? 'android' : 'ios';

      // Re-injected on every navigation regardless of caching: a real page
      // load wipes the JS `window` context, so the JS globals need re-setting
      // even though the cached Dart token value hasn't changed.
      await _controller.runJavaScript('''
        window.flutterFCMToken = "$fcmToken";
        window.flutterDeviceType = "$deviceType";
        window.dispatchEvent(new CustomEvent('fcmTokenReady', {
          detail: {
            token: '$fcmToken',
            deviceType: '$deviceType'
          }
        }));
      ''');
    }
  }

  Future<void> _injectThemeMode(String themeMode) async {
    await _controller.runJavaScript('''
      window.flutterThemeMode = "$themeMode";
      window.dispatchEvent(new CustomEvent('themeModeReady', {
        detail: {
          themeMode: '$themeMode'
        }
      }));
    ''');
  }

  void _handleJavaScriptMessage(String message) {
    // Handle messages from Laravel
    print('Message from Laravel: $message');
  }

  /// Runs the Google/Apple OAuth flow in the system's secure browser and, on
  /// success, loads the server's one-time app-login URL back into the WebView
  /// so the authenticated session lives in the WebView's cookie store.
  ///
  /// Flow: open `/auth/{provider}?flow=app` in the secure browser → provider
  /// login → our server redirects to `iracket://auth-callback?token=…` → we
  /// exchange that token at `/auth/app-login`, which logs the WebView session
  /// in and redirects to the right destination.
  Future<void> _handleOAuth(String provider) async {
    try {
      final authUrl = '${Environment.laravelBaseUrl}/auth/$provider?flow=app';
      final result = await FlutterWebAuth2.authenticate(
        url: authUrl,
        callbackUrlScheme: 'iracket',
      );

      final token = Uri.parse(result).queryParameters['token'];
      if (token != null && token.isNotEmpty) {
        final loginUrl = Uri.parse('${Environment.laravelBaseUrl}/auth/app-login')
            .replace(queryParameters: {'token': token})
            .toString();
        await _controller.loadRequest(Uri.parse(loginUrl));
      }
    } on PlatformException {
      // User cancelled or the flow was dismissed — stay on the login page.
    } catch (e) {
      print('OAuth error: $e');
    }
  }

  /// Open a non-app URL in the system browser (or the relevant app for
  /// mailto:/tel:) instead of leaving it as a dead tap inside the WebView.
  Future<void> _openExternal(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      print('Failed to open external URL: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final backgroundColor = _isDarkMode ? _darkSurface : Colors.white;

    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: WebViewWidget(controller: _controller),
            ),
            Positioned.fill(
              child: IgnorePointer(
                ignoring: !_isLoading,
                child: AnimatedOpacity(
                  opacity: _isLoading ? 1.0 : 0.0,
                  duration: const Duration(milliseconds: 250),
                  curve: Curves.easeInOut,
                  child: Container(
                    color: backgroundColor,
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Image.asset(
                            'assets/images/splash.png',
                            width: 120,
                            height: 120,
                          ),
                          const SizedBox(height: 24),
                          const CircularProgressIndicator(
                            valueColor:
                                AlwaysStoppedAnimation<Color>(_accentGreen),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'dart:io';
import 'package:flutter/material.dart';
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
            // Handle external links
            if (!request.url.startsWith(Environment.laravelBaseUrl)) {
              // Could open in external browser
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

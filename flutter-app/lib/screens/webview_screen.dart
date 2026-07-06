import 'dart:io';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../config/environment.dart';
import '../services/fcm_service.dart';

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

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
    final isDarkMode = brightness == Brightness.dark;
    final themeMode = isDarkMode ? 'dark' : 'light';

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
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) async {
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
          },
        ),
      )
      ..addJavaScriptChannel(
        'FlutterChannel',
        onMessageReceived: (JavaScriptMessage message) {
          _handleJavaScriptMessage(message.message);
        },
      )
      ..loadRequest(Uri.parse(urlWithTheme));
  }

  Future<void> _injectFcmToken() async {
    String? fcmToken = await FcmService.getToken();
    if (fcmToken != null) {
      String deviceType = Platform.isAndroid ? 'android' : 'ios';

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

  Future<void> _onRefresh() async {
    await _controller.reload();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            RefreshIndicator(
              onRefresh: _onRefresh,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: SizedBox(
                  height: MediaQuery.of(context).size.height -
                      MediaQuery.of(context).padding.top,
                  child: WebViewWidget(controller: _controller),
                ),
              ),
            ),
            if (_isLoading)
              LinearProgressIndicator(
                value: _progress,
                backgroundColor: Colors.grey[200],
                valueColor: const AlwaysStoppedAnimation<Color>(Colors.blue),
              ),
          ],
        ),
      ),
    );
  }
}

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';

import 'firebase_options.dart';
import 'screens/splash_screen.dart';
import 'screens/webview_screen.dart';
import 'screens/web_redirect_screen.dart';
import 'services/fcm_service.dart';
import 'services/notification_service.dart';

// Handle background messages
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  print('Handling a background message: ${message.messageId}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  runApp(const IracketApp());
}

class IracketApp extends StatelessWidget {
  const IracketApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'iRacket',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
        useMaterial3: true,
      ),
      home: const AppInitializer(),
    );
  }
}

class AppInitializer extends StatefulWidget {
  const AppInitializer({super.key});

  @override
  State<AppInitializer> createState() => _AppInitializerState();
}

class _AppInitializerState extends State<AppInitializer> {
  bool _isInitialized = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    try {
      // Initialize Firebase with platform-specific options
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      );

      // Try to initialize FCM and notifications (optional for development)
      try {
        // Set up background message handler
        FirebaseMessaging.onBackgroundMessage(
            _firebaseMessagingBackgroundHandler);

        // Initialize FCM
        await FcmService.initialize();

        // Initialize notifications
        await NotificationService.initialize();

        print('FCM initialized successfully');
      } catch (fcmError) {
        // FCM failed (likely APNS not configured), but continue anyway
        print('FCM initialization failed (continuing without notifications): $fcmError');
      }

      setState(() {
        _isInitialized = true;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
      print('Initialization error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(
                  Icons.error_outline,
                  size: 64,
                  color: Colors.red,
                ),
                const SizedBox(height: 16),
                Text(
                  'Initialization Error',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 8),
                Text(
                  _error!,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _error = null;
                    });
                    _initializeApp();
                  },
                  child: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (!_isInitialized) {
      return const SplashScreen();
    }

    // On web, redirect to Laravel app directly
    // On mobile, use WebView
    if (kIsWeb) {
      return const WebRedirectScreen();
    } else {
      return const WebViewScreen();
    }
  }
}

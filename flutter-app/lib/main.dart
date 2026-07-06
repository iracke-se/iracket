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
  bool _isReady = false;

  @override
  void initState() {
    super.initState();
    // Reveal the UI immediately and initialize Firebase/notifications in the
    // background. The web experience does not depend on push notifications, so
    // a slow, hung, or failed Firebase/APNS setup must never leave the app
    // stuck on the splash screen (App Store rejection 2.1a — "unresponsive on
    // launch"). Notably, on iOS `FirebaseMessaging.getToken()` can block
    // indefinitely when APNS is not fully configured.
    _initializeFirebaseInBackground();
    _isReady = true;
  }

  Future<void> _initializeFirebaseInBackground() async {
    try {
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      ).timeout(const Duration(seconds: 10));

      // Set up background message handler
      FirebaseMessaging.onBackgroundMessage(
          _firebaseMessagingBackgroundHandler);

      // Initialize FCM + notifications, each guarded by a timeout so a hung
      // native call can never propagate into a frozen UI.
      await FcmService.initialize().timeout(const Duration(seconds: 10));
      await NotificationService.initialize()
          .timeout(const Duration(seconds: 10));

      print('Firebase/FCM initialized successfully');
    } catch (e) {
      // Push setup is optional — continue without notifications.
      print('Firebase/FCM initialization skipped: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_isReady) {
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

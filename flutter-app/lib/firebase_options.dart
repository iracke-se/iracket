// File generated manually from Firebase configuration files
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// Default [FirebaseOptions] for use with your Firebase apps.
///
/// Example:
/// ```dart
/// import 'firebase_options.dart';
/// // ...
/// await Firebase.initializeApp(
///   options: DefaultFirebaseOptions.currentPlatform,
/// );
/// ```
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      case TargetPlatform.macOS:
        return macos;
      case TargetPlatform.windows:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for windows - '
          'you can reconfigure this by running the FlutterFire CLI again.',
        );
      case TargetPlatform.linux:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for linux - '
          'you can reconfigure this by running the FlutterFire CLI again.',
        );
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyByBDqubVveIYyas9K_9kQ_Ba8Y6_uP4z8',
    appId: '1:473107196258:web:0c16fc9ad690e73188e43b',
    messagingSenderId: '473107196258',
    projectId: 'iracket-se',
    authDomain: 'iracket-se.firebaseapp.com',
    storageBucket: 'iracket-se.firebasestorage.app',
  );

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyByBDqubVveIYyas9K_9kQ_Ba8Y6_uP4z8',
    appId: '1:473107196258:android:ce50323b4e37f7f588e43b',
    messagingSenderId: '473107196258',
    projectId: 'iracket-se',
    storageBucket: 'iracket-se.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyAxvDFGC9n6lJ__ZkN6eDNywNahwovWGZA',
    appId: '1:473107196258:ios:d053401070813c0188e43b',
    messagingSenderId: '473107196258',
    projectId: 'iracket-se',
    storageBucket: 'iracket-se.firebasestorage.app',
    iosBundleId: 'com.iracket.app',
  );

  static const FirebaseOptions macos = FirebaseOptions(
    apiKey: 'AIzaSyAxvDFGC9n6lJ__ZkN6eDNywNahwovWGZA',
    appId: '1:473107196258:ios:d053401070813c0188e43b',
    messagingSenderId: '473107196258',
    projectId: 'iracket-se',
    storageBucket: 'iracket-se.firebasestorage.app',
    iosBundleId: 'com.iracket.app',
  );
}

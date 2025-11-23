# iRacket Mobile App

Flutter mobile application that wraps the iRacket Laravel web app with native push notification support.

## Setup

### Prerequisites
- Flutter SDK 3.x
- Firebase project with FCM enabled
- Android Studio / Xcode

### Firebase Configuration

1. Create a Firebase project at https://console.firebase.google.com
2. Add Android app with package name `com.iracket.app`
3. Add iOS app with bundle ID `com.iracket.app`
4. Download and place configuration files:
   - `android/app/google-services.json`
   - `ios/Runner/GoogleService-Info.plist`

### Environment Variables

Build with environment variables:

```bash
flutter run --dart-define=LARAVEL_URL=https://your-domain.com \
            --dart-define=API_URL=https://your-domain.com/api \
            --dart-define=BEARER_TOKEN=your-token \
            --dart-define=ENV=production
```

### Development

```bash
# Install dependencies
flutter pub get

# Run on device/emulator
flutter run

# Build APK
flutter build apk --release

# Build iOS
flutter build ios --release
```

## Architecture

```
lib/
├── main.dart              # App entry point, Firebase initialization
├── config/
│   └── environment.dart   # Environment configuration
├── services/
│   ├── api_service.dart   # Laravel API client
│   ├── fcm_service.dart   # FCM token management
│   └── notification_service.dart  # Local notifications
└── screens/
    ├── splash_screen.dart # Loading screen
    └── webview_screen.dart # Main WebView
```

## FCM Token Flow

1. App initializes Firebase and requests notification permission
2. FCM token is obtained and stored locally
3. When WebView loads, token is injected into JavaScript context
4. Laravel frontend JavaScript sends token to backend API
5. Token is stored in users table for push notifications

## API Endpoints

The app communicates with these Laravel endpoints:

- `POST /api/mobile/fcm-token` - Store FCM token
- `DELETE /api/mobile/fcm-token` - Remove FCM token

Requires `Authorization: Bearer <token>` header.

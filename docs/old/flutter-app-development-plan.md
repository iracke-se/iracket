# Flutter App Development Plan

## Overview

Create a Flutter mobile application that wraps the iRacket Laravel web app in a WebView while handling FCM push notifications natively. The app will obtain the device's FCM token and communicate it to the Laravel backend for user-specific push notifications.

## Architecture

```
┌─────────────────────────────────────────┐
│           Flutter App                    │
│  ┌─────────────┐  ┌──────────────────┐  │
│  │   WebView   │  │  FCM Service     │  │
│  │  (Laravel)  │  │  (Native)        │  │
│  └──────┬──────┘  └────────┬─────────┘  │
│         │                   │            │
│         └───────┬───────────┘            │
│                 │                        │
│    JavaScript Channel / URL Scheme       │
└─────────────────┬───────────────────────┘
                  │
                  ▼
        ┌─────────────────┐
        │  Laravel API    │
        │  /api/fcm-token │
        └─────────────────┘
```

## Phase 1: Flutter Project Setup

### 1.1 Initialize Flutter Project
- Create Flutter project in `flutter-app/` subdirectory
- Configure for Android and iOS platforms
- Set up project structure following Flutter best practices
- Configure app identifiers:
  - Android: `com.iracket.app`
  - iOS: `com.iracket.app`

### 1.2 Dependencies
Required packages:
- `webview_flutter` - WebView component
- `firebase_core` - Firebase initialization
- `firebase_messaging` - FCM push notifications
- `flutter_local_notifications` - Display notifications when app is in foreground
- `shared_preferences` - Store FCM token locally
- `permission_handler` - Request notification permissions

### 1.3 Firebase Project Setup
- Create Firebase project (or use existing)
- Register Android app with package name
- Register iOS app with bundle ID
- Download and add configuration files:
  - `google-services.json` (Android)
  - `GoogleService-Info.plist` (iOS)

## Phase 2: WebView Implementation

### 2.1 Basic WebView Setup
- Create main WebView widget loading Laravel app URL
- Configure WebView settings:
  - Enable JavaScript
  - Enable DOM storage
  - Handle navigation requests
  - Show loading indicator

### 2.2 JavaScript Communication Channel
Set up bidirectional communication between Flutter and Laravel:

**Flutter → Laravel**: Inject JavaScript to pass FCM token
```dart
webViewController.runJavaScript('''
  window.flutterFCMToken = "$fcmToken";
  window.dispatchEvent(new CustomEvent('fcmTokenReady', { detail: '$fcmToken' }));
''');
```

**Laravel → Flutter**: JavaScript channel for callbacks
```dart
JavaScriptChannel(
  name: 'FlutterChannel',
  onMessageReceived: (message) {
    // Handle messages from Laravel
  },
)
```

### 2.3 WebView Features
- Handle external links (open in system browser)
- Handle file uploads/downloads
- Handle camera/media permissions
- Pull-to-refresh functionality
- Network error handling with retry option
- Deep linking support

## Phase 3: FCM Integration

### 3.1 Android Configuration
- Update `android/app/build.gradle` with Firebase dependencies
- Add internet and notification permissions to `AndroidManifest.xml`
- Configure notification channel for Android 8+
- Handle notification icon and colors

### 3.2 iOS Configuration
- Enable Push Notifications capability in Xcode
- Enable Background Modes (Remote notifications)
- Configure APNs authentication key in Firebase Console
- Request notification permissions from user

### 3.3 FCM Token Management
Token lifecycle handling:
1. Request notification permissions on first launch
2. Get initial FCM token
3. Listen for token refresh events
4. Store token locally
5. Send token to Laravel when:
   - User logs in (detected via WebView URL/cookie)
   - Token refreshes
   - App launches with existing session

### 3.4 Notification Handling
Handle notifications in different app states:
- **Foreground**: Display using flutter_local_notifications
- **Background**: System tray notification
- **Terminated**: System tray notification
- **Tap action**: Open specific screen/URL in WebView

## Phase 4: Flutter-Laravel Communication

### 4.1 Token Transmission Strategy

**Option A: JavaScript Injection (Recommended)**
1. Flutter injects FCM token into WebView JavaScript context
2. Laravel frontend JavaScript detects token and sends to backend via AJAX
3. Works seamlessly with existing Laravel authentication

**Option B: API Endpoint**
1. Flutter calls Laravel API directly with token
2. Requires passing authentication token/session to Flutter
3. More complex but decoupled

**Option C: URL Scheme**
1. Flutter intercepts specific URLs like `iracket://set-token?token=xxx`
2. Laravel triggers navigation to this URL
3. Simple but less elegant

### 4.2 User Session Detection
Detect when user is logged in:
- Monitor WebView URL changes for dashboard/authenticated routes
- Check for authentication cookies
- Listen for JavaScript events from Laravel

### 4.3 API Endpoint for Token Storage
Laravel endpoint to receive FCM token:
```
POST /api/user/fcm-token
{
  "fcm_token": "string",
  "device_type": "android|ios",
  "device_id": "string (optional)"
}
```

## Phase 5: Laravel Backend Changes

### 5.1 Database Migration
Add FCM token storage to users table or create separate table:
```php
// Option A: Add to users table
$table->string('fcm_token')->nullable();
$table->string('device_type')->nullable();
$table->timestamp('fcm_token_updated_at')->nullable();

// Option B: Separate table (supports multiple devices)
// user_devices: id, user_id, fcm_token, device_type, device_id, created_at, updated_at
```

### 5.2 API Endpoint
Create authenticated endpoint to store FCM token:
- Route: `POST /api/user/fcm-token`
- Middleware: `auth:sanctum` or session-based
- Validate token format
- Update or create device record

### 5.3 JavaScript for Token Reception
Add to Laravel frontend (in authenticated layout):
```javascript
window.addEventListener('fcmTokenReady', function(e) {
  const token = e.detail;
  fetch('/api/user/fcm-token', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ fcm_token: token, device_type: 'mobile' })
  });
});
```

### 5.4 Push Notification Service
Create service to send notifications via Firebase:
- Use `kreait/laravel-firebase` package
- Create `FirebaseNotificationService` class
- Methods for sending to single user, multiple users, topics
- Queue notifications for performance

### 5.5 Notification Triggers
Integrate push notifications with existing features:
- New match created/updated
- Match result submitted
- Ranking changes
- Club invitations
- System announcements

## Phase 6: Testing Plan

### 6.1 Flutter App Testing
- Unit tests for FCM token management
- Widget tests for WebView loading states
- Integration tests for JavaScript communication
- Test on physical devices (FCM requires real device)

### 6.2 Laravel Backend Testing
- API endpoint tests for token storage
- Notification service tests (mock Firebase)
- Test token refresh scenarios

### 6.3 End-to-End Testing
- Full flow: app launch → login → token sent → notification received
- Test notification tap opens correct screen
- Test token refresh updates backend
- Test multiple devices per user

## Phase 7: Deployment

### 7.1 Android
- Generate signed APK/AAB
- Create Google Play Console listing
- Configure Firebase for production
- Set up release signing keys

### 7.2 iOS
- Configure App Store Connect
- Set up provisioning profiles
- Configure APNs production certificate
- TestFlight beta testing

### 7.3 Laravel
- Add Firebase credentials to production environment
- Deploy API endpoints
- Set up notification queues
- Monitor notification delivery

## File Structure

```
flutter-app/
├── android/
│   └── app/
│       ├── google-services.json
│       └── src/main/AndroidManifest.xml
├── ios/
│   └── Runner/
│       ├── GoogleService-Info.plist
│       └── Info.plist
├── lib/
│   ├── main.dart
│   ├── app.dart
│   ├── config/
│   │   └── environment.dart
│   ├── services/
│   │   ├── fcm_service.dart
│   │   ├── notification_service.dart
│   │   └── storage_service.dart
│   ├── screens/
│   │   ├── webview_screen.dart
│   │   └── splash_screen.dart
│   └── widgets/
│       ├── webview_widget.dart
│       └── loading_indicator.dart
├── pubspec.yaml
└── README.md
```

## Environment Configuration

### Flutter
```dart
class Environment {
  static const String laravelBaseUrl = String.fromEnvironment(
    'LARAVEL_URL',
    defaultValue: 'http://localhost:8000',
  );

  static const bool isProduction = String.fromEnvironment(
    'ENV',
    defaultValue: 'development',
  ) == 'production';
}
```

### Laravel (.env additions)
```env
# Firebase
FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id

# Mobile App
MOBILE_APP_SCHEME=iracket
```

## Security Considerations

1. **Token Validation**: Validate FCM token format before storing
2. **Authentication**: Ensure token endpoint requires authentication
3. **HTTPS**: Always use HTTPS for WebView URL in production
4. **Token Rotation**: Handle token refresh to prevent stale tokens
5. **Rate Limiting**: Rate limit token update endpoint
6. **Device Verification**: Consider device attestation for sensitive apps

## Timeline Estimate

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Phase 1: Flutter Setup | 1-2 days | Firebase project |
| Phase 2: WebView | 2-3 days | Phase 1 |
| Phase 3: FCM Integration | 2-3 days | Phase 1 |
| Phase 4: Communication | 1-2 days | Phase 2, 3 |
| Phase 5: Laravel Backend | 2-3 days | None |
| Phase 6: Testing | 2-3 days | All phases |
| Phase 7: Deployment | 2-3 days | All phases |

**Total Estimated Time: 12-19 days**

## Open Questions

1. **Multiple Devices**: Should users receive notifications on all logged-in devices or just the most recent?
2. **Notification Preferences**: Should users be able to toggle notification types?
3. **Offline Support**: Should the app cache any content for offline viewing?
4. **Deep Links**: What specific screens should be deep-linkable from notifications?
5. **Analytics**: Should we track notification open rates?

## Next Steps

1. Create Firebase project and obtain configuration files
2. Decide on single vs. multiple device token storage
3. Define notification types and their payloads
4. Review and approve this plan
5. Begin Phase 1 implementation

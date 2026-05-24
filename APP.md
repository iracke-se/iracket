# iRacket Mobile App

The iRacket mobile app is a thin **Flutter WebView wrapper** around the iRacket Laravel web application, with native Firebase push notification (FCM) support layered on top. Deployment is handled through **Codemagic** CI/CD.

The app lives at [flutter-app/](flutter-app/) in this repository.

**Last updated:** 2026-05-25

---

## Table of contents

1. [What this app is (and isn't)](#what-this-app-is-and-isnt)
2. [Architecture](#architecture)
3. [How the WebView wrapping works](#how-the-webview-wrapping-works)
4. [Push notification flow](#push-notification-flow)
5. [Configuration](#configuration)
6. [Local development](#local-development)
7. [Backend integration](#backend-integration)
8. [Deployment via Codemagic](#deployment-via-codemagic)
9. [Versioning and release process](#versioning-and-release-process)
10. [Known constraints](#known-constraints)

---

## What this app is (and isn't)

**It is**:
- A native shell (iOS + Android) that loads the iRacket Laravel web app inside a WebView
- A bridge for FCM push notifications — the native layer handles registration and delivery; the WebView shows the in-app UI
- A way to ship a "real app" to the App Store and Play Store without rebuilding all our screens in Dart

**It is not**:
- A native rewrite of the iRacket UI
- A separate codebase with its own business logic — all the actual app screens (matches, rankings, players, admin) come from the Laravel web app loaded over the network
- A platform-specific feature host — the only meaningful native code is Firebase init and FCM token handoff

Changes to "the app" almost always mean changes to the Laravel/Livewire code, not the Flutter project. The Flutter project changes only for: native config (icons, splash, permissions), FCM/notification logic, version bumps, or Flutter SDK upgrades.

---

## Architecture

```
flutter-app/
├── pubspec.yaml              Dependencies and version
├── lib/
│   ├── main.dart             App entry; Firebase init; routes to splash → webview
│   ├── firebase_options.dart Generated; platform-specific Firebase config
│   ├── config/
│   │   └── environment.dart  Reads --dart-define vars (LARAVEL_URL, API_URL, etc.)
│   ├── services/
│   │   ├── api_service.dart       HTTP client → Laravel mobile API endpoints
│   │   ├── fcm_service.dart       FCM token request, refresh, storage
│   │   └── notification_service.dart  Local notification display (foreground messages)
│   └── screens/
│       ├── splash_screen.dart            Loading + permission prompts
│       ├── webview_screen.dart           Main screen — loads LARAVEL_URL
│       ├── web_redirect_screen.dart      Platform-conditional shim
│       ├── web_redirect_screen_web.dart    (web platform implementation)
│       └── web_redirect_screen_stub.dart   (non-web stub)
├── android/                  Native Android project
├── ios/                      Native iOS project
├── assets/images/            Icon, splash image
└── (build/, web/, linux/, macos/, windows/ — Flutter scaffolding, mostly unused)
```

**Key dependencies** ([flutter-app/pubspec.yaml](flutter-app/pubspec.yaml)):

| Package | Purpose |
|---|---|
| `webview_flutter` ^4.10.0 | The WebView that renders the Laravel app |
| `firebase_core` ^3.8.1 | Firebase SDK initialization |
| `firebase_messaging` ^15.1.6 | FCM token + remote message handling |
| `flutter_local_notifications` ^18.0.1 | Displays notifications when the app is in foreground |
| `shared_preferences` ^2.3.4 | Local key/value storage (FCM token cache, user prefs) |
| `http` ^1.2.2 | REST client for `POST /api/mobile/fcm-token` etc. |
| `flutter_launcher_icons` ^0.14.2 | Generates platform-specific app icons (dev only) |
| `flutter_native_splash` ^2.4.3 | Generates native launch screens for both platforms (dev only) |

**Bundle identifier**: `com.iracket.app` — same on Android (`applicationId` in [android/app/build.gradle.kts](flutter-app/android/app/build.gradle.kts)) and iOS (`PRODUCT_BUNDLE_IDENTIFIER` in [ios/Runner.xcodeproj/project.pbxproj](flutter-app/ios/Runner.xcodeproj/project.pbxproj)). Display name is `iRacket` on both platforms.

---

## How the WebView wrapping works

1. App launches → [main.dart](flutter-app/lib/main.dart) initializes Firebase and runs `AppInitializer`
2. `AppInitializer` shows the splash screen while requesting notification permissions and fetching the FCM token
3. Once ready, navigates to `WebViewScreen`
4. `WebViewScreen` loads `Environment.laravelBaseUrl` (from `--dart-define=LARAVEL_URL=...` at build time) in a `WebViewController`
5. JavaScript channel exposes the FCM token to the page via injection — Laravel-side JS picks it up and POSTs it to `/api/mobile/fcm-token` to register the device
6. All subsequent navigation (login, matches, admin, etc.) happens inside the WebView — no native screens are involved

Because the actual app surface is the same Laravel HTML/CSS/Livewire, **anything that works in a desktop browser works in the app**, with two exceptions covered natively:
- Push notifications (FCM)
- Storage of the FCM token across sessions (via `shared_preferences`)

---

## Push notification flow

```
User launches app
    ↓
main.dart: Firebase.initializeApp()
    ↓
fcm_service.dart: request notification permission
    ↓
FirebaseMessaging.getToken() → FCM token (string)
    ↓
shared_preferences: cache token locally
    ↓
WebViewScreen: inject token into JS context
    ↓
Laravel page JS: POST /api/mobile/fcm-token
    Authorization: Bearer <MOBILE_APP_BEARER_TOKEN>
    Body: { token, user_id }
    ↓
Laravel: store fcm_token on users table
    ↓
[Later] Laravel sends a notification
    ↓
Firebase → device
    ↓
If app is in background → system tray notification
If app is in foreground → flutter_local_notifications shows it
    ↓
User taps notification → app opens → WebView navigates to relevant URL
```

**Endpoints used** (Laravel side):

- `POST /api/mobile/fcm-token` — register or refresh a device token
- `DELETE /api/mobile/fcm-token` — unregister (called on logout)

All mobile API calls require the `MOBILE_APP_BEARER_TOKEN` env var (set on the Laravel server, baked into the Flutter app at build time via `--dart-define=BEARER_TOKEN=...`). Handled by the `mobile_app` middleware in [bootstrap/app.php](bootstrap/app.php).

---

## Configuration

### Environment variables (build-time, via `--dart-define`)

The Flutter app reads four compile-time constants from [lib/config/environment.dart](flutter-app/lib/config/environment.dart):

| Var | Purpose | Example |
|---|---|---|
| `LARAVEL_URL` | Origin loaded in WebView | `https://iracket.se` |
| `API_URL` | Base URL for REST calls | `https://iracket.se/api` |
| `BEARER_TOKEN` | Shared secret for mobile API | `<long random string>` |
| `ENV` | Environment label | `production` or `development` |

These are **compile-time** — changing them requires a rebuild. They're injected via `--dart-define=KEY=VALUE` flags (or Codemagic build env vars — see below).

The default `LARAVEL_URL` in `environment.dart` resolves based on Flutter's `kReleaseMode` — `https://iracket.se` for release builds, `https://dev.iracket.se` for debug builds. `--dart-define=LARAVEL_URL=...` overrides this for ad-hoc builds (e.g. local Laravel via `http://10.0.2.2:8000`).

### Firebase configuration files

Firebase config files are **not committed** to the repository. They must be placed in:

- `flutter-app/android/app/google-services.json` (Android)
- `flutter-app/ios/Runner/GoogleService-Info.plist` (iOS)

Download both from the Firebase Console (project: iRacket) before building. In Codemagic, these are uploaded as encrypted environment files.

### `firebase_options.dart`

This file ([lib/firebase_options.dart](flutter-app/lib/firebase_options.dart)) contains the platform-specific Firebase configuration (API keys, project IDs). It was hand-generated from the Firebase config files. If Firebase project settings change (new API key, new sender ID), this file needs to be regenerated using the FlutterFire CLI:

```bash
dart pub global activate flutterfire_cli
flutterfire configure
```

---

## Local development

**Prerequisites:**
- Flutter SDK matching `environment: sdk: ^3.9.2` in `pubspec.yaml`
- Android Studio + Android SDK (for Android builds)
- Xcode (macOS, for iOS builds)
- A Firebase project access (to download config files)

**Setup:**

```bash
cd flutter-app

# Install dependencies
flutter pub get

# Drop the Firebase config files in place
# (download from Firebase Console)
#   android/app/google-services.json
#   ios/Runner/GoogleService-Info.plist
```

**Running against a local Laravel dev server:**

```bash
# In iracket/ — start Laravel
composer dev

# In flutter-app/ — run the app against local Laravel
flutter run \
  --dart-define=LARAVEL_URL=http://10.0.2.2:8000 \
  --dart-define=API_URL=http://10.0.2.2:8000/api \
  --dart-define=BEARER_TOKEN=<value from .env: MOBILE_APP_BEARER_TOKEN> \
  --dart-define=ENV=development
```

(Use `10.0.2.2` for the Android emulator, `localhost` for the iOS simulator.)

**Building release artifacts locally** (rarely needed — Codemagic does this for production):

```bash
flutter build apk --release \
  --dart-define=LARAVEL_URL=https://iracket.se \
  --dart-define=API_URL=https://iracket.se/api \
  --dart-define=BEARER_TOKEN=<prod token> \
  --dart-define=ENV=production

flutter build ios --release \
  --dart-define=LARAVEL_URL=https://iracket.se \
  --dart-define=API_URL=https://iracket.se/api \
  --dart-define=BEARER_TOKEN=<prod token> \
  --dart-define=ENV=production
```

---

## Backend integration

**Mobile API routes** are defined in [routes/api.php](routes/api.php) under the `mobile_app` middleware group. The middleware ([app/Http/Middleware/MobileAppAuth.php](app/Http/Middleware/MobileAppAuth.php)) validates the `Authorization: Bearer <token>` header against `config('app.mobile_app_bearer_token')` (sourced from `MOBILE_APP_BEARER_TOKEN` in `.env`).

**Required Laravel env vars** for mobile support:

```env
# Mobile App
MOBILE_APP_BEARER_TOKEN=<same value used in --dart-define=BEARER_TOKEN>

# Firebase (for sending push notifications from Laravel)
FIREBASE_CREDENTIALS=/path/to/firebase-service-account.json
```

The `kreait/laravel-firebase` package handles sending pushes from the Laravel side.

---

## Deployment via Codemagic

Build pipelines are defined in [codemagic.yaml](codemagic.yaml) at the repo root. The file is the source of truth — the Codemagic Workflow Editor (UI builder) is automatically disabled whenever `codemagic.yaml` is present on the branch being built.

### Workflows

Three workflows are declared in [codemagic.yaml](codemagic.yaml). The Flutter SDK is pinned to `3.35.5` for reproducible builds, and all three use the same pre-build script (`flutter pub get`, then regenerate launcher icons and native splash).

| Workflow ID | UI name | Builds | Use it when |
|---|---|---|---|
| `android-workflow` | Android Workflow | AAB only | Quick Android-only iteration |
| `ios-workflow` | iOS Workflow | IPA only | Quick iOS-only iteration |
| `combined-workflow` | Android + iOS Workflow | AAB + IPA | Cutting a paired release for both stores |

Build number strategy: Codemagic's `$BUILD_NUMBER` is used as both the AAB version code and (`+1` offset) the iOS `CFBundleVersion`, so paired builds don't collide if uploaded to TestFlight + Play from the same run.

### Signing — Android

**Upload keystore** was generated on 2026-05-25:

- File: `~/Documents/iracket-keystore/upload-keystore.jks` (outside this repo)
- Credentials TXT: `~/Documents/iracket-keystore/iracket-android-keystore.txt` (passphrase, alias, SHA-1/SHA-256 fingerprints, expiry, recovery instructions)
- Validity: 10000 days (until 2053-10-10)

The repo is wired to use it: [build.gradle.kts](flutter-app/android/app/build.gradle.kts) reads `android/key.properties` for release signing when present, falls back to debug signing if missing (so fresh clones still build). [key.properties.example](flutter-app/android/key.properties.example) documents the expected format. `key.properties`, `*.jks`, and `*.keystore` are all gitignored.

**Setup checklist:**

- [x] Generate upload keystore (done — see TXT file for credentials)
- [x] Wire `build.gradle.kts` to read `key.properties`
- [x] Gitignore signing secrets
- [ ] Upload `.jks` to Codemagic → Teams → Code signing identities → Android keystores, reference name `iracket_upload`
- [ ] Add `android_signing:` block to all three workflows in `codemagic.yaml`, referencing `iracket_upload`

### Signing — iOS

**Apple Developer Program**: account is active and paid.

iOS signing uses Codemagic's App Store Connect API integration — Codemagic auto-creates certs and provisioning profiles on first build using the API key, no manual cert generation needed. The yaml already declares `integrations: app_store_connect: codemagic` and `ios_signing: distribution_type: app_store, bundle_identifier: com.iracket.app`.

**Setup checklist:**

- [x] Apple Developer Program enrollment (paid)
- [ ] Register App ID `com.iracket.app` in Apple Developer portal → Identifiers (enable Push Notifications capability since we use Firebase Messaging)
- [ ] Create the app in App Store Connect with bundle ID `com.iracket.app`, note the numeric Apple ID for the listing
- [ ] App Store Connect → Users and Access → Integrations → Generate App Store Connect API key (App Manager role), download `.p8`
- [ ] Save `.p8` to `~/Documents/iracket-keystore/AuthKey_XXXX.p8`
- [ ] Codemagic → Teams → Integrations → Developer Portal → Add App Store Connect API key, reference name `codemagic` (matches yaml), paste Issuer ID + Key ID + `.p8`
- [ ] Replace `APP_STORE_APPLE_ID: 0000000000` in [codemagic.yaml](codemagic.yaml) with the real numeric Apple ID
- [ ] Add any required `NS*UsageDescription` strings to [Info.plist](flutter-app/ios/Runner/Info.plist) for permissions the app uses (camera, photos, mic, tracking — store review will reject missing strings)

### Deployment — Google Play

**Google Play Console**: account is active and paid.

**Setup checklist:**

- [x] Google Play Console developer registration (paid)
- [ ] Create the app in Play Console: name `iRacket`, default language English, free, declarations accepted
- [ ] Create Internal testing track, manually upload first AAB (required before service-account uploads work)
- [ ] Enable Play App Signing — upload the first AAB, Google generates the app signing key
- [ ] Google Cloud Console → enable Google Play Android Developer API
- [ ] Google Cloud → IAM → Service accounts → create `codemagic-uploader`, generate JSON key
- [ ] Save JSON to `~/Documents/iracket-keystore/iracket-play-service-account.json`
- [ ] Play Console → Setup → API access → link Cloud project → invite the service account → grant Release Manager role for iRacket
- [ ] Codemagic → environment variables → group `google_play` (already referenced in yaml) → add `GCLOUD_SERVICE_ACCOUNT_CREDENTIALS` as a Secure variable containing the JSON contents
- [ ] Add `google_play:` publishing block to Android + combined workflows in [codemagic.yaml](codemagic.yaml), targeting `internal` track for the first runs

### Deployment — App Store / TestFlight

The same App Store Connect API key from iOS signing also handles deployment.

**Setup checklist:**

- [x] App Store Connect API key uploaded to Codemagic (covered by iOS signing checklist above)
- [ ] Decide initial target: TestFlight (recommended — no review delay, internal testers install via TestFlight app) vs. straight to App Store review (24-48h review per upload)
- [ ] Add `app_store_connect:` publishing block to iOS + combined workflows in [codemagic.yaml](codemagic.yaml), default to TestFlight with `submit_to_app_store: false` until the build is ready to ship

### Secrets folder layout

All cloud-deployment secrets live in `~/Documents/iracket-keystore/` (folder mode `700`, files mode `600`, outside this repo):

```
~/Documents/iracket-keystore/
├── upload-keystore.jks                       Android upload key
├── iracket-android-keystore.txt              Android credentials + instructions
├── AuthKey_XXXX.p8                           iOS App Store Connect API key (when generated)
└── iracket-play-service-account.json         Play API service account (when generated)
```

Back the entire folder up to a password manager AND a second location (encrypted external drive or iCloud Drive). Losing the Android upload key requires a Google support ticket to reset; losing the App Store Connect API key just requires regenerating a new one in App Store Connect.

### What lives where

| Concern | Where |
|---|---|
| Build pipeline definition | [codemagic.yaml](codemagic.yaml) (repo root) |
| Android keystore credentials | `~/Documents/iracket-keystore/` (NOT in repo) + Codemagic encrypted storage |
| iOS signing certs/profiles | Auto-generated by Codemagic via App Store Connect API key |
| `google-services.json`, `GoogleService-Info.plist` | Codemagic encrypted files (NOT in repo) |
| Play service account JSON | Codemagic environment variable `GCLOUD_SERVICE_ACCOUNT_CREDENTIALS` (group `google_play`) |
| App version + build number | [flutter-app/pubspec.yaml](flutter-app/pubspec.yaml) `version:` + Codemagic's `$BUILD_NUMBER` |
| App icon, splash assets | [flutter-app/assets/images/](flutter-app/assets/images/) — regenerated by `flutter_launcher_icons` + `flutter_native_splash` in pre-build |
| Bundle ID | [build.gradle.kts](flutter-app/android/app/build.gradle.kts) + [project.pbxproj](flutter-app/ios/Runner.xcodeproj/project.pbxproj) — currently `com.iracket.app` |

### Triggering a release

1. Bump `version:` in [flutter-app/pubspec.yaml](flutter-app/pubspec.yaml) (e.g. `1.0.1+2` — the part after `+` is the local fallback build number; Codemagic overrides it with `$BUILD_NUMBER`)
2. Commit + push to `main`
3. Trigger the appropriate workflow from the Codemagic UI (Android, iOS, or combined). Auto-trigger on push is not configured by default — add `triggering:` to the workflow in [codemagic.yaml](codemagic.yaml) if you want push-to-deploy
4. Once green, the publishing blocks (once added per checklists above) push the artifact to Internal track / TestFlight automatically; promote from there to production via the respective store console

---

## Versioning and release process

`pubspec.yaml`'s `version: 1.0.0+1` follows the Flutter convention:

- `1.0.0` — **version name** (shown in App Store, Play Store, About screen)
- `+1` — **build number** (must strictly increase on every store upload, even for the same version name)

**Conventions:**
- Patch releases (bugfix): bump version name `1.0.0` → `1.0.1`
- Minor releases (new feature in the web app that needs an app rebuild): `1.0.x` → `1.1.0`
- Major releases (breaking change, Flutter SDK upgrade, native dependency change): `1.x.y` → `2.0.0`
- Always bump the build number — Play Store and TestFlight reject duplicates

Most Laravel-side feature work does NOT need an app version bump — the WebView just loads the new pages on next launch.

App version bump is required when:
- Native dependencies change (`pubspec.yaml`)
- Firebase or notification logic changes
- WebView config changes (e.g. JS bridge surface)
- Native permissions change (`AndroidManifest.xml`, `Info.plist`)

---

## Known constraints

- **Default `LARAVEL_URL` is environment-aware** (per [environment.dart](flutter-app/lib/config/environment.dart)) — release builds default to `https://iracket.se`, debug builds default to `https://dev.iracket.se`. Codemagic env vars override these explicitly for clarity.
- **Firebase config files (`google-services.json` and `GoogleService-Info.plist`) are not in git** — they must be downloaded from Firebase Console or restored from Codemagic encrypted storage before any local build.
- **`firebase_options.dart` is hand-generated**, not auto-regenerated on each build. If Firebase project settings change, regenerate via `flutterfire configure`.
- **iOS App Store review**: a WebView-only app needs to demonstrate "added native value" to avoid Apple's 4.2 guideline rejection. The FCM push notification integration is what satisfies this — be careful not to remove it.
- **WebView limitations**: file pickers, OS share sheets, biometric auth, and some payment flows require explicit native bridging. None are wired up today. If a future Laravel feature needs these, the WebView screen needs JavaScript channel handlers added.
- **There is no offline mode** — the entire app surface is the live Laravel site. If the server is down, the app is down.
- **WebView session is independent of Safari/Chrome** — users won't be logged in via the app just because they're logged in in the browser, and vice versa. Each platform has its own cookie jar.

---

## Pointers

- [flutter-app/README.md](flutter-app/README.md) — original short-form README inside the Flutter project (most details are superseded by this doc)
- [codemagic.yaml](codemagic.yaml) — CI/CD pipeline definitions
- `~/Documents/iracket-keystore/iracket-android-keystore.txt` — Android upload key credentials and recovery instructions (not in repo)
- [SCRAPER.md](SCRAPER.md) — scraper system docs (the web app's data ingestion side)
- [README.md](README.md) — top-level project README
- [routes/api.php](routes/api.php) — mobile API endpoints
- [app/Http/Middleware/MobileAppAuth.php](app/Http/Middleware/MobileAppAuth.php) — bearer-token auth for mobile endpoints

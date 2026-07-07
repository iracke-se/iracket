# Google Play — connecting the service account for automated releases

This doc explains how to connect a Google Play service account so Codemagic can
upload each new AAB to Google Play automatically.

The build pipeline is already wired up: both the **Android Workflow** and the
**Android + iOS Workflow** in [`codemagic.yaml`](../codemagic.yaml) contain a
`publishing` **script** that uploads the built AAB to the **`production`** track
with `fastlane supply`, reading credentials from the
`GCLOUD_SERVICE_ACCOUNT_CREDENTIALS` environment variable.

```yaml
    publishing:
      scripts:
        - name: Publish to Google Play (only if a service account is configured)
          ignore_failure: true
          script: |
            if [ -z "$GCLOUD_SERVICE_ACCOUNT_CREDENTIALS" ]; then
              echo "GCLOUD_SERVICE_ACCOUNT_CREDENTIALS not set — skipping Google Play publish."
              exit 0
            fi
            AAB=$(find "$CM_BUILD_DIR/flutter-app/build" -name "*.aab" | head -1)
            echo "$GCLOUD_SERVICE_ACCOUNT_CREDENTIALS" > "$CM_BUILD_DIR/gcloud_sa.json"
            fastlane supply --package_name "$PACKAGE_NAME" --aab "$AAB" \
              --track production --json_key "$CM_BUILD_DIR/gcloud_sa.json" \
              --skip_upload_metadata true --skip_upload_images true --skip_upload_screenshots true
```

This is a script (not the declarative `google_play:` integration) on purpose, so
that a missing/unconfigured service account **does not fail the build** — it just
skips the upload. Once the steps below are complete and that variable exists in
Codemagic, the next successful Android build publishes itself. **No further yaml
changes are needed.**

---

## Prerequisites (must be true, or Play rejects the release)

- [ ] The **first AAB was uploaded manually** to a track at least once (done — internal track). This is required before API uploads work.
- [ ] **Play App Signing is enabled** (Google prompts for this on the first upload — accept it).
- [ ] The **store listing is complete**: app name, description, graphics, content rating, data safety form, target audience, privacy policy — all filled in and passing review. Production is gated until these are done.
- [ ] **Closed-testing requirement met (new personal accounts only):** Google requires a closed test with **≥12 testers for 14 continuous days** before a *personal* developer account can publish to production. Organization accounts are exempt. If your account is personal and hasn't met this, change `track: production` to `track: internal` (or `closed`) in [`codemagic.yaml`](../codemagic.yaml) until the period is complete.
- [ ] Each new build's **`versionCode` is higher** than anything already uploaded. Codemagic uses `$PROJECT_BUILD_NUMBER` (a project-wide counter shared across all workflows) as the version code, so this is automatic as long as you don't manually upload a higher code in between.

---

## Step 1 — Enable the Play Developer API in Google Cloud

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Select (or create) a project to associate with Play — e.g. `iracket`.
3. **APIs & Services → Library** → search **Google Play Android Developer API** → **Enable**.

## Step 2 — Create the service account + JSON key

1. **Google Cloud → IAM & Admin → Service Accounts → Create service account**.
2. Name: `codemagic-uploader`. No Google Cloud IAM roles are needed here (permissions are granted in Play Console instead). Click **Done**.
3. Open the new service account → **Keys → Add key → Create new key → JSON** → download it.
4. Save the JSON to `~/Documents/iracket-keystore/iracket-play-service-account.json` and back it up. **This file is a secret — never commit it.**

## Step 3 — Link the project and grant access in Play Console

1. **Play Console → Setup → API access**.
2. **Link** the Google Cloud project from Step 1 (if not already linked).
3. Find the `codemagic-uploader` service account in the list → **Manage Play Console permissions** (or **Grant access**).
4. Grant it, at minimum:
   - **Release** → **Release to production, exclude devices, and use Play App Signing** (a.k.a. **Release Manager**), OR **Admin (all permissions)** for simplicity.
5. Apply the invitation. It can take a few minutes to propagate.

## Step 4 — Add the credentials to Codemagic

1. **Codemagic → your app → Environment variables**.
2. Add a variable:
   - **Name:** `GCLOUD_SERVICE_ACCOUNT_CREDENTIALS`
   - **Value:** the entire contents of the JSON file from Step 2
   - **Secure:** ✅ (checked)
   - **Group:** any group that both Android workflows already import — e.g. `app_env`. (The workflows import `firebase` and `app_env`.)

   To copy the JSON on macOS:
   ```bash
   pbcopy < ~/Documents/iracket-keystore/iracket-play-service-account.json
   ```

## Step 5 — Trigger a build

1. Bump `version:` in [`flutter-app/pubspec.yaml`](../flutter-app/pubspec.yaml) if needed, commit, push.
2. Run the **Android Workflow** (or **Android + iOS Workflow**) in Codemagic.
3. On success, the AAB is uploaded to the **production** track automatically. Google still runs its own review before the release goes live.

---

## Choosing a track

Change the `--track` value in the publishing script in both Android workflows in
[`codemagic.yaml`](../codemagic.yaml):

| Value | Effect |
|---|---|
| `internal` | Internal testing — instant, no review gate. Safest for first automated runs. |
| `alpha` | Closed testing track. |
| `beta` | Open testing track. |
| `production` | Live to all users (after Google review). **Currently set.** |

### Optional: staged rollout to production

Add `--rollout` to the `fastlane supply` command to release to a fraction of
users first:

```bash
fastlane supply ... --track production --rollout 0.2   # 20% of users
```

### Optional: upload without releasing

Add `--release_status draft` to `fastlane supply` to upload the AAB but leave it
as a draft you publish manually from the Play Console. Useful while you're still
confirming the pipeline (and required for the very first production release —
see the prerequisites).

---

## Troubleshooting

| Error | Cause / fix |
|---|---|
| `The caller does not have permission` | Service account not granted access in Play Console (Step 3), or propagation delay — wait a few minutes. |
| `APK/AAB signed in debug mode` | The release keystore wasn't applied — see the `Set up Android key.properties` step in the yaml; not a Play-API issue. |
| `Version code N has already been used` | `$PROJECT_BUILD_NUMBER` isn't higher than an existing upload — bump it or run a fresh Codemagic build. |
| `Track 'production' ... not allowed` / release blocked | Store listing incomplete or closed-testing period not met — finish listing tasks or drop to `internal`/`closed` temporarily. |
| `Only releases with status draft may be created on ... first release` | The very first production release must be started manually in the Play Console, or add `--release_status draft` to `fastlane supply` for the first run. |

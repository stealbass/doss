# Push Notifications Setup (FCM HTTP v1)

This project now sends push notifications through Firebase Cloud Messaging HTTP v1 (OAuth service account).

## Why this change

Firebase legacy HTTP API keys are deprecated and can be disabled in Firebase projects.
If legacy is disabled, server key based push delivery will fail.

## Backend configuration

Set these environment variables in production:

- `FIREBASE_PROJECT_ID=dossy-chat-ia`
- `FIREBASE_SERVICE_ACCOUNT_PATH=/absolute/path/to/firebase-service-account.json`

Alternative:

- `FIREBASE_SERVICE_ACCOUNT_JSON={...full json...}`

Then clear cache:

```bash
php artisan optimize:clear
```

## Service account JSON requirements

Create it from Google Cloud Console:

1. IAM & Admin
2. Service Accounts
3. Select account or create a new one
4. Add role: Firebase Admin SDK Administrator (or Firebase Cloud Messaging Admin)
5. Create key -> JSON
6. Put file on server and reference with `FIREBASE_SERVICE_ACCOUNT_PATH`

## Mobile requirements

- Android package must match Firebase app package
- `google-services.json` must be up-to-date
- Notification permission must be accepted on Android 13+
- App subscribes to `user_{id}` topic automatically (fallback path)

## Validation checklist

1. Login in mobile app
2. Send admin push to that user
3. Check logs for:
   - `FCM configured with HTTP v1`
   - `FCM Token saved`
4. Check admin send result has `fcm_success_count > 0`

## Common mistakes

- Putting service account key ID in `firebase_server_key`
  - This is not a valid FCM credential.
- Using only Sender ID
  - Sender ID is not enough to send.
- Keeping legacy-only configuration while legacy API is disabled.

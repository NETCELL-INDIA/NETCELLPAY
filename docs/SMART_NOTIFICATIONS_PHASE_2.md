# Smart Notifications Phase 2 — Consent

Local implementation only. Production was not migrated and no live FCM or SMS was sent.

## 1. Database

Existing column kept: `users.promotional_push_consent` tinyint, default 0. Existing users are not opted in.

Added by `2026_11_26_190000_add_notification_consent_audit.php` (user app and admin copy):

- `users.promotional_push_consent_at` nullable timestamp
- `users.promotional_push_consent_source` varchar(40) nullable
- `notification_consent_logs`: user_id, channel, consent_type, previous_value, new_value, action, source, created_at

No foreign keys. Logs are insert-only. `down()` drops the log table and the two new user columns. It does not drop `promotional_push_consent` or message rows.

Phase 1 `messages.read_at` is unchanged. Delivery status 0 pending, 1 sent, 2 no token, 3 failed. Consent skip uses status 4 with `delivery_error = consent_revoked`.

## 2. API

Authenticated with the existing `AppUserCheck` login key and user id. The saved user is the user who owns the login key. A different user id in the body is rejected by that middleware. `target_user_id` is ignored.

`GET /api/user/notification-preferences`

```json
{
  "type": "success",
  "success": true,
  "message": "Fetch Successfully",
  "data": {
    "promotional_push_consent": false,
    "promotional_push_consent_at": null
  }
}
```

`POST /api/user/notification-preferences`

```json
{
  "login_key": "<session login key>",
  "user_id": 123,
  "promotional_push_consent": true
}
```

```json
{
  "type": "success",
  "success": true,
  "message": "Notification preferences updated successfully",
  "data": {
    "promotional_push_consent": true,
    "promotional_push_consent_at": "2026-11-26 17:50:00"
  }
}
```

Only boolean `true`/`false` or integer `0`/`1` is accepted. The same value twice does not write another log. Source stored is `app`.

## 3. Consent workflow

Default is off. The user turns promotional push on or off from the app API. Grant and revoke write one history row each. Admins can read that history. Admins cannot change it.

## 4. Admin panel

The campaign composer offers only General, Offers, and Announcements. Wallet, Recharge, Refund, Security, and Maintenance are rejected.

Preview shows total users, consent approved, consent not approved, no device token, and estimated recipients (approved users who also have a token). The confirm screen states that only users who explicitly enabled promotional notifications receive the campaign. There is no bypass control.

Campaign detail shows sent, failed, no-token, and consent-skipped counts. Recipient rows do not show device tokens.

Consent History is at `admin/extras/notification-consent`, menu key `extras.notification_consent`. It lists user id, name, type, previous value, new value, action, source, and time. Filters are user id, name, and dates.

## 5. Flutter

No Flutter project is in this workspace. The app still needs a Notification Preferences screen:

- Load `GET /api/user/notification-preferences` with the same login key and user id used by the other app APIs.
- Toggle off by default until the server says otherwise.
- Turning on asks: "Do you agree to receive promotional notifications from NETCELL PAY?" Save only after Agree, with `promotional_push_consent: true`.
- Turning off saves `false` immediately.
- Show `promotional_push_consent_at` as the last update.
- On a network error, put the toggle back to the last server value.
- Do not treat the local toggle as permission to send. The server checks consent again before each campaign send.
- Keep the existing FCM token registration. Promotional off does not turn off recharge or wallet event pushes.

Suggested files when the app repo is available: `lib/screens/notification_preferences_page.dart` and a small API client next to the existing notification API.

## 6. Eligibility

A bulk campaign recipient must be active, not deleted, not role 1 or 2, inside the selected audience, and `promotional_push_consent = 1`. Unknown categories also require consent. Consent is read again immediately before FCM. If it is no longer 1, the row becomes status 4 and is not sent. Those rows are hidden from the app inbox.

## 7. Transactional versus promotional

Bulk campaigns cannot be labeled as wallet, recharge, refund, security, or maintenance to skip consent. Recharge success still uses `helpers::pushNotifyUser()` from `ProcessRecharge`. That path does not read promotional consent.

## 8. Security

Login key identifies the user. Cross-user ids are rejected. Invalid consent values are rejected. History is admin-menu protected and has no update action. Logs do not store login keys or device tokens. Campaign pages still omit tokens.

## 9. Local tests

Fake FCM only.

- `NotificationConsentTest`: 4 tests, 34 assertions, OK
- `NotificationReadStatusTest`: 2 tests, 25 assertions, OK
- `NotificationCampaignSenderTest`: 22 tests, 102 assertions, OK
- `SmartNotificationTest`: 14 tests, 65 assertions, OK

## 10. Production checklist

Do not run these until a deploy is requested.

```bash
cd ~/domains/netcellpay.in/public_html
git pull
composer dump-autoload -o
php artisan migrate --force --path=database/migrations/2026_11_26_180000_add_read_at_to_messages.php
php artisan migrate --force --path=database/migrations/2026_11_26_190000_add_notification_consent_audit.php
cd admin
composer dump-autoload -o
php artisan view:clear
php artisan route:clear
```

The consent migration does not change wallet, recharge, or existing consent values. `QUEUE_CONNECTION` was not changed. A phone will not show the new preference page until the Flutter app calls these APIs.

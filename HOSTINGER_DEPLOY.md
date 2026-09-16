# Hostinger deploy — Netcell Pay (netcellpay.in)

## 1) Upload files
Upload ALL contents of this project into Hostinger:

```
domains/netcellpay.in/public_html/
```

You should see:
- `admin/`
- `app/`
- `public/`
- `vendor/`
- `.htaccess`
- `artisan`
- ...

Do NOT upload into the parent folder that has `DO_NOT_UPLOAD_HERE`.

## 2) URLs after upload
- User site: `https://netcellpay.in/`
- Admin: `https://netcellpay.in/admin/`

Root `.htaccess` sends normal traffic to `/public`.
`/admin` traffic goes to `/admin/public`.

## 3) Database (hPanel)
1. Create MySQL database + user
2. Import your SQL dump in phpMyAdmin
3. Note: DB name, user, password, host (`localhost`)

## 4) Create `.env` files (do not commit secrets)
Copy examples and edit in File Manager:

```
public_html/.env            ← from .env.example
public_html/admin/.env      ← from admin/.env.example
```

Set:
- `APP_URL=https://netcellpay.in`
- Admin `APP_URL=https://netcellpay.in/admin` (required for correct admin asset URLs)
- Optional admin override: `ASSET_URL=https://netcellpay.in/admin`
- DB credentials
- `APP_DEBUG=false`

## 5) SSH commands (required for admin)

**Important:** the user app `vendor/` folder is in git, but **`admin/vendor/` is not**.  
If `/admin/` shows HTTP 500 after git deploy, install admin dependencies first:

```bash
cd ~/domains/netcellpay.in/public_html/admin
composer install --no-dev --optimize-autoloader
```

Then run app setup:

```bash
cd ~/domains/netcellpay.in/public_html

# User app
php artisan key:generate
php artisan storage:link
php artisan config:cache

# Admin app
cd admin
php artisan key:generate
php artisan storage:link
php artisan config:cache
```

After changing `.env` or deploying CSRF/session fixes, clear cached config first:
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

If `vendor` is missing:
```bash
composer install --no-dev --optimize-autoloader
cd admin && composer install --no-dev --optimize-autoloader
```

## 6) Permissions
Make writable:
- `storage/`
- `bootstrap/cache/`
- `admin/storage/`
- `admin/bootstrap/cache/`

## 7) SSL
Enable Free SSL for `netcellpay.in` in Hostinger.

## 8) companies.domain
If login uses domain check, set `companies.domain` to:
- `netcellpay.in`
- or `www.netcellpay.in`

## 9) Do NOT use Hostinger Vite/Node deploy
This is PHP/Laravel. Use File Manager / Git PHP hosting, not Vite build.

## 10) Firebase HTTP v1 (Android push) — required

Do **not** enable Cloud Messaging API (Legacy). Do **not** paste a Server key into admin.

1. Firebase Console → project **netcellpay-fe31a** → Project settings → Service accounts → Generate new private key.
2. On Hostinger upload that JSON with File Manager (not Git) to **both**:
   - `public_html/storage/app/firebase/service-account.json`
   - `public_html/admin/storage/app/firebase/service-account.json`
3. Permissions: PHP-readable only (e.g. `640`). Must be under `storage/`, never under `public/`.
4. In `public_html/.env`:
```
FIREBASE_PROJECT_ID=netcellpay-fe31a
FIREBASE_CREDENTIALS=/home/USER/domains/netcellpay.in/public_html/storage/app/firebase/service-account.json
FCM_ANDROID_CHANNEL_ID=high_importance_channel
```
5. In `public_html/admin/.env`:
```
FIREBASE_PROJECT_ID=netcellpay-fe31a
FIREBASE_CREDENTIALS=/home/USER/domains/netcellpay.in/public_html/admin/storage/app/firebase/service-account.json
FCM_ANDROID_CHANNEL_ID=high_importance_channel
```
Replace `USER` with the Hostinger account name. Empty `FIREBASE_CREDENTIALS` falls back to `storage/app/firebase/service-account.json` of that Laravel root.
6. SSH:
```bash
cd ~/domains/netcellpay.in/public_html
php artisan config:clear && php artisan cache:clear
cd admin
php artisan config:clear && php artisan cache:clear
```
7. Admin → System settings → Pusher / FCM push should show **Firebase HTTP v1 is configured**.
8. Retailer latest APK → Logout → Login, then admin-send to **that user only**. Expect Phone push: 1.


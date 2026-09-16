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

Do **not** enable Cloud Messaging API (Legacy). Do **not** use a Server key.  
Do **not** place the service-account JSON under `public/`, `public_html/`, `admin/public/`, or any web-accessible folder.  
Do **not** commit the JSON to Git.

### A) Detect your real Hostinger home (do not invent `/home/USER`)

SSH into Hostinger, then run:

```bash
echo "$HOME"
# example output: /home/u123456789
pwd
whoami
```

Use the printed `$HOME` value in the steps below.

### B) Store the JSON outside the web root

```bash
mkdir -p "$HOME/private/firebase"
chmod 700 "$HOME/private/firebase"
```

Upload the Firebase service account JSON (project **netcellpay-fe31a**) via SFTP/File Manager to:

```text
$HOME/private/firebase/service-account.json
```

Example after detecting home:

```bash
# If echo $HOME printed /home/u123456789 then the file is:
# /home/u123456789/private/firebase/service-account.json
chmod 600 "$HOME/private/firebase/service-account.json"
```

Never put this file under `~/domains/netcellpay.in/public_html/`.

### C) Point both Laravel apps at that private file

In **API** `.env` (site root Laravel) and **admin** `.env`:

```env
FIREBASE_PROJECT_ID=netcellpay-fe31a
FIREBASE_CREDENTIALS=/ABS/PATH/FROM/HOME/private/firebase/service-account.json
FCM_ANDROID_CHANNEL_ID=high_importance_channel
```

Replace `/ABS/PATH/FROM/HOME/...` with the real path from `echo "$HOME"` (e.g. `$HOME/private/firebase/service-account.json` expanded).

Empty `FIREBASE_CREDENTIALS` falls back to `storage/app/firebase/service-account.json`, which is **rejected** if that path sits under `public_html`. Prefer the private path above.

### D) Clear config cache + verify (no secrets printed)

```bash
cd ~/domains/netcellpay.in/public_html
php artisan config:clear && php artisan cache:clear
php artisan fcm:status
php artisan fcm:status --auth

cd ~/domains/netcellpay.in/public_html/admin
php artisan config:clear && php artisan cache:clear
php artisan fcm:status
php artisan fcm:status --auth
```

Expect `configured: yes`, `safe_path: yes`, and with `--auth` → `oauth: ok`.

### E) Admin UI + test push

1. Admin → System settings → Pusher / FCM → green “Firebase HTTP v1 ready…”.
2. Retailer latest APK → Logout → Login (saves `android_fcm_token`).
3. Admin send notification to **that user only** → Phone push: 1.


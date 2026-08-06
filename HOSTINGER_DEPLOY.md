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
- Admin `APP_URL=https://netcellpay.in/admin`
- DB credentials
- `APP_DEBUG=false`

## 5) SSH commands (recommended)
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

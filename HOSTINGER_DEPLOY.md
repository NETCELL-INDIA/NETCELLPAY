Hostinger deployment checklist for NETCELL PAY (admin)

1. Prepare Hostinger account
   - Ensure SSH access enabled (recommended).
   - Create domain and subdomain `admin.netcell.net` (if using).

2. Database
   - In Hostinger hPanel -> Databases, create a new MySQL database and user.
   - Note DB name, user, password and host (usually `localhost`).
   - Import the SQL dump via phpMyAdmin or using `mysql` over SSH:
     mysql -u DB_USER -p DB_NAME < dump.sql

3. Files & Document root
   - Upload project files to Hostinger. Two common approaches:
     A) Single site with subfolder: place admin app under `/home/USER/domains/example.com/public_html/admin` and set subdomain document root to that `admin` folder's `public` directory.
     B) Separate domain: point `admin.domain` subdomain to `/home/USER/domains/domain.com/public_html` after moving `admin/public` contents there.
   - Ensure the `public` folder is the web root. If you must place project above `public_html`, update server vhost or move content accordingly.

4. `.env` configuration
   - Update `.env` with Hostinger DB credentials and `APP_URL` = `https://admin.yourdomain.com` (or appropriate URL).
   - Update mail settings for sending emails.

5. Composer & Artisan (via SSH)
   - SSH into host, cd to project root and run:
     composer install --no-dev --optimize-autoloader
     php artisan key:generate
     php artisan migrate --force
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     php artisan storage:link

   - If SSH not available: run `composer install` locally, zip and upload vendor and project, then run migrations via phpMyAdmin or contact host support.

6. Permissions
   - Ensure `storage` and `bootstrap/cache` are writable by web server (chmod 755/775 as allowed by Hostinger).

7. SSL & DNS
   - In Hostinger DNS, create A record for `admin` pointing to your server IP.
   - Enable SSL via hPanel (Let’s Encrypt) for the subdomain.

8. Testing
   - Visit `https://admin.yourdomain.com` and check logs at `storage/logs/laravel.log` for errors.
   - If company row lookup uses `$_SERVER['HTTP_HOST']`, ensure the `companies.domain` column matches the subdomain (e.g. `admin.yourdomain.com`).

9. Tips
   - If using shared hosting, avoid running long CLI commands; prefer running locally then uploading the built app.
   - Keep backups of DB and `.env` before changes.

Contact me if you want me to produce a precise `deploy.sh` or `.htaccess` adjustments for Hostinger based on how you want to map `admin` subdomain.
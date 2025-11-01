# Sprintalyze

A Laravel-based application for monitoring and analyzing Jira Epic issues, users, and instances with webhook integration.

## About Sprintalyze

Sprintalyze is a comprehensive Jira integration platform that allows you to:

- **Monitor Jira Instances** - Connect to multiple Jira Cloud instances and manage them centrally
- **Track Epic Issues** - Monitor specific Epic issues and receive real-time updates via webhooks
- **Manage Users** - Track activity of specific Jira users across your projects
- **Webhook Integration** - Receive and log Jira webhook events for automated monitoring
- **OAuth 2.0 Authentication** - Secure authentication using Jira's OAuth 2.0 flow

## Technology Stack

- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** MySQL/PostgreSQL/SQLite
- **Frontend:** Blade templates, jQuery, DataTables, Bootstrap
- **Authentication:** Laravel Breeze + Jira OAuth 2.0

## Features

### 1. Jira Instance Management
- Connect multiple Jira Cloud instances
- Automatic OAuth token refresh
- Monitor and manage instance status

### 2. Epic Issue Tracking
- View available Epic issues from Jira (last 30 days)
- Add/remove Epic issues to/from monitoring
- Toggle active/inactive status
- Server-side DataTables with search and pagination

### 3. User Monitoring
- Fetch users from Jira instances
- Monitor specific users across projects
- Track user activity and status

### 4. Webhook Integration
- Endpoint: `POST /webhooks/jira`
- Logs all incoming Jira events
- Stores complete payload for analysis
- Automatic event type detection

## Server Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM (for asset compilation)
- MySQL >= 8.0 or PostgreSQL >= 13 or SQLite 3
- Web server (Apache/Nginx)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url> sprintalyze
cd sprintalyze
```

### 2. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Install Node Dependencies and Build Assets

**Option A: If Node.js/NPM is installed on the server**

```bash
npm install
npm run build
```

**Option B: If Node.js/NPM is NOT installed on the server**

Build assets on your local development machine, then deploy them:

```bash
# On your LOCAL machine (with Node.js installed):
npm install
npm run build

# This creates compiled assets in public/build/
# Commit these files to git, or copy them to the server
```

Then on the server, ensure the `public/build` directory exists with the compiled assets.

If you're using git deployment:
```bash
# Add the build directory to git (remove from .gitignore if needed)
git add public/build -f
git commit -m "Add compiled assets"
git push
```

Or manually copy the build directory:
```bash
# Copy from local to server
scp -r public/build user@your-server:/var/www/sprintalyze/public/
```

### 4. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and configure:

```env
APP_NAME=Sprintalyze
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sprintalyze
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Jira OAuth Credentials (from Atlassian Developer Console)
JIRA_CLIENT_ID=your_client_id
JIRA_CLIENT_SECRET=your_client_secret
JIRA_REDIRECT_URI=https://your-domain.com/jira/callback
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Run Database Migrations

```bash
php artisan migrate --force
```

### 7. Create Storage Link

```bash
php artisan storage:link
```

### 8. Set File Permissions

```bash
# For Linux/Unix servers
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Configure Web Server

#### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/sprintalyze/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration Example

Ensure `.htaccess` file exists in the `public` directory and `mod_rewrite` is enabled.

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 10. Configure Task Scheduler (Optional)

If you plan to use Laravel's task scheduling, add to crontab:

```bash
* * * * * cd /var/www/sprintalyze && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Configure Queue Worker (Optional)

For background job processing:

```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/sprintalyze.conf
```

Add:

```ini
[program:sprintalyze-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sprintalyze/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sprintalyze/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sprintalyze-worker:*
```

## Jira OAuth Setup

### 1. Create OAuth 2.0 App in Atlassian

1. Go to [Atlassian Developer Console](https://developer.atlassian.com/console/myapps/)
2. Click "Create" → "OAuth 2.0 integration"
3. Name your app (e.g., "Sprintalyze")
4. Add permissions:
   - Jira API: `read:jira-user`, `read:jira-work`, `write:jira-work`
5. Set Authorization callback URL: `https://your-domain.com/jira/callback`
6. Copy Client ID and Client Secret to `.env`

### 2. Configure Webhooks in Jira

1. Go to Jira Settings → System → WebHooks
2. Create a new webhook
3. Set URL: `https://your-domain.com/webhooks/jira`
4. Select events to monitor:
   - Issue created
   - Issue updated
   - Issue deleted
5. Optional: Set JQL filter to monitor specific issues

## Database Schema

### Core Tables

- **users** - Application users
- **jira_connections** - OAuth connections to Jira instances
- **jira_instances** - Monitored Jira instances
- **monitored_users** - Tracked Jira users
- **monitored_issues** - Tracked Epic issues
- **webhook_logs** - Incoming Jira webhook events

## Application Routes

### Public Routes
- `GET /` - Welcome page
- `GET /jira/authorize` - Initiate Jira OAuth
- `GET /jira/callback` - OAuth callback
- `POST /webhooks/jira` - Jira webhook receiver

### Authenticated Routes
- `GET /dashboard` - Main dashboard
- `GET /manage/instances` - Manage Jira instances
- `GET /manage/users` - Manage monitored users
- `GET /manage/issues` - Manage monitored Epic issues

### API Endpoints (DataTables)
- `GET /datatable/available-instances.json`
- `GET /datatable/monitored-instances.json`
- `GET /datatable/available-users.json`
- `GET /datatable/monitored-users.json`
- `GET /datatable/available-issues.json`
- `GET /datatable/monitored-issues.json`

## Security Considerations

### Production Deployment

1. **HTTPS Required**: Always use HTTPS in production
   ```bash
   # Install Certbot for Let's Encrypt
   sudo apt-get install certbot python3-certbot-nginx
   sudo certbot --nginx -d your-domain.com
   ```

2. **Disable Debug Mode**: Ensure `APP_DEBUG=false` in `.env`

3. **Secure Database Credentials**: Use strong passwords

4. **File Permissions**: Restrict permissions on sensitive files
   ```bash
   chmod 600 .env
   ```

5. **Webhook Security**: Consider adding IP whitelisting for webhook endpoint

## Troubleshooting

### Common Issues

**1. 500 Error**
- Check `storage/logs/laravel.log`
- Verify file permissions on `storage` and `bootstrap/cache`

**2. Database Connection Error**
- Verify database credentials in `.env`
- Ensure database exists: `CREATE DATABASE sprintalyze;`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

**3. Jira OAuth Not Working**
- Verify `JIRA_REDIRECT_URI` matches Atlassian app settings exactly
- Check client ID and secret are correct
- Ensure callback URL is accessible

**4. Webhook Not Receiving Events**
- Verify webhook URL is accessible publicly
- Check Jira webhook configuration
- Review `webhook_logs` table for received events
- Check `storage/logs/laravel.log` for errors

**5. Token Expiration Issues**
- Automatic token refresh is built-in
- If refresh fails, re-authenticate via OAuth

## Maintenance

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Update Application

**If Node.js/NPM is installed:**
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**If Node.js/NPM is NOT installed:**
```bash
# Build assets locally first, then:
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Or copy the compiled assets from local:
# scp -r public/build user@your-server:/var/www/sprintalyze/public/
```

### Backup Database
```bash
# MySQL
mysqldump -u username -p sprintalyze > backup_$(date +%Y%m%d).sql

# PostgreSQL
pg_dump -U username sprintalyze > backup_$(date +%Y%m%d).sql
```

## Support

For issues and questions:
- Check logs: `storage/logs/laravel.log`
- Review Laravel documentation: https://laravel.com/docs
- Check Jira API documentation: https://developer.atlassian.com/cloud/jira/platform/rest/v3/

## License

This project is proprietary software. All rights reserved.

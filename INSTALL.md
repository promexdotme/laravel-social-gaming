# ⚡ 1-Click Casino Installation & Setup Guide

Deploy your full-featured iGaming platform (Slots, Live Sportsbook, Prediction Markets, and Liteback Admin) in minutes.

---

## Server Requirements
- **OS**: Ubuntu 22.04 LTS or 24.04 LTS
- **Web Server**: Nginx
- **PHP**: PHP 8.2 or 8.4 with extensions (`php-fpm`, `php-mysql`, `php-redis`, `php-curl`, `php-mbstring`, `php-xml`, `php-zip`, `php-bcmath`)
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **In-Memory Cache**: Redis (`redis-server`)

---

## Prepacked Apache installer

On Apache 2.4, enable `.htaccess` overrides and `mod_rewrite`, then open `/install`. After a successful installation writes `installed.lock`, the final step removes `install.sql`, `database_backup.sql`, `promex-gaming-suite-v2.0-cpanel.zip` (if present), and `install.php`. Configuration, the installation lock, and runtime license certificates remain intact.

If filesystem permissions prevent cleanup, the locked installer displays the remaining filenames and a cleanup retry button. Correct their permissions and retry. The development checkout retains its installer source so future packages can be built.

The root `.htaccess` denies direct access to SQL dumps, ZIP archives, backups, keys, certificates, internal Laravel directories, and development tools. These rules require Apache; Nginx does not read `.htaccess`. The Nginx example below uses Laravel's `public` directory as its document root rather than the prepacked repository root.

## 1. Quick Automated Setup

Run the following commands on your server:

```bash
# 1. Clone the repository
git clone https://github.com/promexdotme/laravel-social-gaming.git /var/www/casino
cd /var/www/casino/casino

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure Database in .env
# Edit DB_DATABASE, DB_USERNAME, DB_PASSWORD to match your MySQL credentials
nano .env

# 5. Run migrations and seed data
php artisan migrate --seed

# 6. Set directory permissions
chown -R www-data:www-data /var/www/casino
chmod -R 775 storage bootstrap/cache
```

---

## 2. Nginx Web Server Configuration

Create `/etc/nginx/sites-available/casino`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    root /var/www/casino/casino/public;
    index index.php index.html;

    # SSL Certificates (Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Mixed Content Upgrade Header
    add_header Content-Security-Policy "upgrade-insecure-requests" always;

    # Games CDN Reverse Proxy (Zero local disk space required)
    location /games/ {
        proxy_pass https://clients.377.live/games/;
        proxy_set_header Host clients.377.live;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_ssl_server_name on;
        proxy_buffering off;
    }

    # Main application routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock; # Adjust PHP version as needed
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/casino /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 3. Activate Your SaaS License

1. Purchase a plan on [promex.me/platforms/promex-gaming-suite/](https://promex.me/platforms/promex-gaming-suite/) or receive your key via email.
2. Log in to your administration panel: `https://yourdomain.com/liteback`
3. Click on **Store & License** (`/liteback/store`).
4. Paste your License Key (`PROMEX-XXXX-XXXX-XXXX`) into the box.
5. Click **Save & Activate**.

The license must be provisioned for your domain. The features available depend on your purchased entitlements:
- 🎰 **Hosted Games CDN**: High-speed slots and provably fair games
- ⚽ **Central Odds Feed**: Real-time sports events and automated bet settlements
- 📦 **Store Add-ons**: 1-click modular extensions and game pack downloads
- ☁️ **GitHub Cloud Live Updates**: Verified core upgrades and database migrations
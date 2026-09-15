# 🎰 Promex Gaming Suite v2.0

### Next-Gen Turnkey Social Gaming, Sportsbook & Prediction Platform
**Built with Laravel 12 & PHP 8.2+**

[![Laravel 12](https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%20%7C%208.4-blue?style=for-the-badge&logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)
[![Release](https://img.shields.io/badge/Release-v2.0-orange?style=for-the-badge)](https://github.com/promexdotme/laravel-social-gaming/releases/tag/v2.0)
[![Live Demo](https://img.shields.io/badge/Demo-casinoduliban.com-blueviolet?style=for-the-badge)](https://casinoduliban.com)

---

## 🌐 Live Interactive Demo

Experience the full frontend player experience, sportsbook lobby, and provably fair games:
👉 **[https://casinoduliban.com](https://casinoduliban.com)**

---

## 📖 Overview

**Promex Gaming Suite v2.0** is an enterprise-grade, modular social gaming engine built on **Laravel 12**. Re-architected from the ground up for extreme performance and effortless deployment, v2.0 shifts heavy assets to high-speed cloud infrastructure while giving you total control over user management, virtual ledger economies, sports odds feeds, and game logic.

### 💡 Open-Source Philosophy: 100% Free Core Engine
The platform engine is **completely free and open-source**. 
* **Self-Host & Build**: You can download, deploy, self-host, and inspect 100% of the core backend and frontend code.
* **Your Own Games**: Add your own custom HTML5 games, build bespoke RNG slots, or integrate third-party game providers with **zero licensing fees or platform lock-in**.

---

## 💎 Cloud Ecosystem & Pricing Plans

While the core platform is free for your own games and custom development, we provide managed high-speed cloud infrastructure so you can launch a production-ready casino in minutes without managing 40GB+ of local storage or real-time sports feed parsers.

👉 **Get Your License & Cloud Access:** **[promex.me/platforms/promex-gaming-suite/](https://promex.me/platforms/promex-gaming-suite/)**

| Plan | Price | What's Included |
| :--- | :--- | :--- |
| **Free / Open Source** | **$0** | Complete Laravel 12 source code, Liteback admin console, virtual economy, user management, and ability to add unlimited custom games. |
| **Cloud CDN Monthly** | **$10 / mo** | Instant access to our hosted Games CDN (hundreds of top-tier slots streamed on demand), automated live sports odds feeds, central licensor sync, and ongoing cloud patches. |
| **Cloud CDN Annual** | **$100 / yr** | All Cloud CDN features with 2 months free ($20 savings), priority cloud node routing, and store add-on compatibility. |
| **Full Studio Lifetime Access** | **$499 one-time** | **Total Ownership:** Complete direct Google Drive download access to the entire **40GB+ raw offline game asset pack**, offline websocket server source code, legacy editions, and **all future releases & updates included forever**. |

> 🛠️ **Custom Development Available:**  
> Need a bespoke frontend theme, proprietary custom mini-games, specialized crypto gateway integrations, or custom sports/prediction market feeds? We offer custom development and white-label turnkey deployments upon request. Inquire at [promex.me](https://promex.me).

---

## 🚀 Key Features in v2.0

### 1. ⚡ Laravel 12 Modernized Engine
* **Clean Architecture:** Upgraded to **Laravel 12** on PHP 8.2+ / 8.4 with streamlined migrations and strict typing.
* **Single Tenant Simplicity:** Focused shop-first architecture (`shop_id = 1`) eliminating legacy multi-tier distributor/agent bloat for blistering fast SQL query execution.
* **Dark-Mode Liteback Admin:** Modern, responsive operator console at `/liteback` with live dashboard analytics, game activation toggles, user balance adjustment tools, and audit logs.

### 2. ☁️ Hosted Games CDN (Zero Local Storage Required)
* **No 40GB Downloads:** Heavy game binaries, sound stems, and sprite sheets are streamed instantly from our global CDN or reverse-proxied seamlessly via your web server (`/games/`).
* **Instant Deployments:** Launch your entire platform on a low-cost VPS or standard server in under 5 minutes without exhausting disk space.

### 3. ⚽ Real-Time Sportsbook & Prediction Markets
* **Live Odds Feeds:** Automated fixture sync and real-time odds parsing powered by Redis caching.
* **Dynamic Odds Formatting:** Automatic American to Decimal odds conversion with automatic kickoff-time match expirations.
* **Single & Multi-Selection Betslips:** Interactive floating betslip drawer with real-time payout calculators and automated settlement engines.
* **Polymarket Prediction Markets:** Integrated real-world event predictions, crypto milestones, and political outcome markets.

### 4. 🎲 Provably Fair Mini-Games
* Built-in instant arcade games powered by provably fair cryptographic RNG algorithms:
  * **Plinko**, **Crash**, **Mines**, **Dice**, and **Wheel of Fortune**.
  * Players can verify seed hashes directly in their client for complete transparency.

### 5. 💳 Web3 & Virtual Economy Gateways
* Native integrations with **CryptoGateway**, **BTCPay Server**, **Stripe Checkout**, and **PayPal**.
* Multi-chain cryptocurrency support (USDT, USDC, BTC, ETH, SOL, MATIC, TRX).
* Manual bank transfer receipts review queue with instant operator balance credit.

---

## 🔮 Roadmap: Upcoming Features

* 📈 **Simulated Stocks & Equities Trading:** Virtual stock market sandbox with real-time candlestick charts and order executions.
* 🪙 **Crypto Spot & Futures Trading:** Live crypto pair trading engine with virtual leverage, stop-loss, and simulated order books.
* 📱 **PWA 2.0 Mobile Experience:** Enhanced installable Progressive Web App with haptic feedback and offline caching.

---

## 📦 Quick Installation

For full production deployment and Nginx reverse proxy instructions, see [INSTALL.md](INSTALL.md) and [REVERSE_PROXY_GAMES.md](REVERSE_PROXY_GAMES.md).

### 1. Clone & Install
```bash
# Clone the repository
git clone https://github.com/promexdotme/laravel-social-gaming.git /var/www/casino
cd /var/www/casino/casino

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Environment configuration
cp .env.example .env
php artisan key:generate
```

### 2. Database & Setup
Configure your MySQL database in `casino/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casino
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
APP_URL=https://yourdomain.com
```

Run database migrations:
```bash
php artisan migrate --seed
```

### 3. Nginx Reverse Proxy for CDN Games
Add the following block to your Nginx site configuration so your players load cloud games seamlessly from your own domain:
```nginx
location /games/ {
    proxy_pass https://clients.377.live/games/;
    proxy_set_header Host clients.377.live;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_ssl_server_name on;
    proxy_buffering off;
}
```

### 4. Activate License Key
1. Obtain your key from [promex.me/platforms/promex-gaming-suite/](https://promex.me/platforms/promex-gaming-suite/).
2. Navigate to your operator console: `https://yourdomain.com/liteback`.
3. Go to **Store & License** (`/liteback/store`), input your key (`PROMEX-XXXX-XXXX-XXXX`), and click **Activate**.

---

## 🏛️ Legacy Versions & Lite 13
Need access to the legacy standalone Lite 13 release or earlier v10 monolithic distributions?
* You can switch to the archived branch:
  ```bash
  git checkout lite-13
  ```
* Or download the [Lite 13 Release](https://github.com/promexdotme/laravel-social-gaming/releases/tag/lite-13).

---

## ⚠️ Compliance & Legal Disclaimer

This software is designed strictly for **Social Gaming**, **Virtual Currency**, and **Amusement** purposes.
* It does not process real-money wagering natively.
* It is provided as-is for educational, amusement, and sandbox platform development under the MIT License.
* Operators are solely responsible for ensuring compliance with all local laws and regulations in their respective jurisdictions.

---

<div align="center">
  <sub>Developed & Maintained by <a href="https://promex.me">Promex.me</a> • Built with ❤️ for the global gaming community</sub>
</div>
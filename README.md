# 🕹️ Laravel Social Gaming Engine (Lite 12)
### The Lightweight Open Source RNG Platform (Laravel 11 / PHP 8.4)

![Laravel 11](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel) ![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue?style=for-the-badge&logo=php) ![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge) ![Status](https://img.shields.io/badge/Status-Active_Dev-brightgreen?style=for-the-badge)

> **FORMERLY KNOWN AS:** *opensource-casino-v10*. This repository has been rebranded to focus on Social Gaming Architecture and RNG Logic.

## 📖 About This Version (Lite 12)
This is the **Lite Edition** of the GDM Gaming Engine. It is a re-architected version of the legacy platform, stripped down to the essentials for developers building **Social Arcade** and **Amusement** platforms. [DEMO](https://one.377.live)

## 💎 Unlock the Full Potential
The Lite version is free for testing. To run a live production site with support and high-speed assets, choose a Sponsorship Tier.

<div align="center">
  <h3>
    <a href="https://github.com/sponsors/promexdotme">
      🚀 Click Here to Sponsor & Upgrade
    </a>
  </h3>
</div>

| Feature | 🆓 Free / Public | 🥉 $10 Supporter | 🥈 $25 Builder | 🥇 $99 Enterprise |
| :--- | :---: | :---: | :---: | :---: |
| **Discord Access** | Read-Only | **Chat & Support** | Priority Support | **Direct Access** |
| **Infrastructure** | Public (Rate Limited) | Public (Rate Limited) | **CDN Whitelist** ✅ | **Self-Hosted** (You own it) |
| **Websockets** | Shared | Shared | **Private Repo Access** | **Source Code** |
| **Game Assets** | Cloud Only | Cloud Only | Cloud Only | **40GB Download (Drive)** |
| **Versions** | Lite-12 Only | Lite-12 Only | Lite-12 Only | **Full + Legacy Packaged** |

### ⚡ Tier Breakdown
*   **$10 (Supporter):** Unlocks the **Discord Chat**. Get help, report bugs, and talk with other devs (Role: `@Ad-Free Supporter`).
*   **$25 (Builder):** **CDN Whitelist.** We authorize your domain on our High-Speed Game CDN & Cloud Sockets. Plus, get access to the **Private Socket Repo** to see how it works.
*   **$99 (SaaS Studio):** **Total Ownership.** Get the Google Drive link to download all **40GB of Assets** to your own server. Includes Legacy v10 versions, AI Scripts, and a 1-on-1 Setup Consultation.

  
**Major Upgrade: Hybrid Cloud Architecture**
Unlike previous versions, Lite 12 is designed to run on **Shared Hosting (CPanel)** without root access.
*   **Websockets:** Hosted on a dedicated external instance (No local Node/PM2 required).
*   **Game Assets:** Served via CDN (Saves you 40GB+ of storage space).
*   **Proxying:** The included `.htaccess` handles the traffic seamlessly.


## 🚀 Architecture Changes (The "Minify" Plan)

### 1. Zero-Config Deployment (Shared Hosting Ready)
We have removed the requirement for VPS root access.
*   **No PM2/Node.js Required:** The client connects to our public socket instance by default.
*   **No Huge Downloads:** The `/games/` directory is served remotely via CDN, making this repo lightweight and fast to deploy.
*   **Easy Overrides:** If you prefer to self-host everything, you can simply point `socket_config.json` and `.htaccess` to your own infrastructure.

### 2. Database & Role Simplification
*   **Single Tenant:** Multi-shop tables (`w_shops_countries`, etc.) dropped. Enforces `shop_id=1` via `ForceShopOne` middleware.
*   **Roles:** Reduced to `Admin` and `User` only (No complex Agent trees).
*   **Clean Code:** Removed legacy bloat (SMS, Pincodes, HappyHours) to focus on performance.


## 🛠️ Key Features
### 🎮 Game Management (Liteback)
*   **Active/Inactive Logic:** Seamlessly move games between active and inactive states.
*   **RNG Engine:** Certifiable logic for game outcomes.
*   **Visual Management:** Auto-generates icons from `/frontend/Default/ico/{name}.jpg`.

### 💳 Virtual Economy & Payments
*   **Crypto Top-up:** Integrated BTC Pay Server flow.
    *   **Config:** `config/payments.php`
    *   **Flow:** Generates invoice $\rightarrow$ Webhook listener $\rightarrow$ Credits user balance $\rightarrow$ Logs to `w_transactions`.
*   **Extensible:** Implement `VanguardLTE\Services\Payments\PaymentDriverInterface` to add new gateways.

### 🎨 Customizable Hero Section
The frontend features a dynamic hero banner configurable via filesystem:
*   **Location:** `/public/minimal/hero/`
*   **Desktop Video:** `hero.mp4` (1920x1080, <10MB, Muted Loop).
*   **Images:** `hero-desktop.jpg` and `hero-mobile.jpg`.
*   *Note:* Enable by setting `$showHero = true` in `resources/views/frontend/Minimal/games/list.blade.php`.

---

## 📦 Installation
1. **Clone the repo:**
   ```bash
   git clone -b lite-12 https://github.com/gamingdotme/laravel-social-gaming.git
   ```
2. **Install Dependencies:**
   ```bash
   composer install
   npm install && npm run build
   ```
3. **Environment Setup (Important):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *   **Configure DB:** Set your MySQL credentials.
   *   **Configure Domain:** You **MUST** set your app domain in `.env`:
       ```env
       APP_URL=https://your-domain.com
       ```
4. **Migrate:**
   ```bash
   php artisan migrate
   ```

### ☁️ How the Hybrid Connection Works
You do not need to install a socket server. The system uses:
1.  **`socket_config.json`**: Defines the connection to the external websocket instance.
2.  **`.htaccess`**: Acts as a reverse proxy to route traffic correctly on shared hosting.
3.  **CDN**: Loads heavy game assets from our cloud, saving your disk space.

---

## 💎 Premium Assets & Self-Hosting
This Lite version relies on cloud assets to keep your deployment simple and cheap.

**Want to self-host everything?**
If you have your own dedicated server (VPS) and want the full **40GB Game Asset Pack** + **Local Websocket Source Code**:

*   **[Subscribe for Shared Hosting Access / Google Drive Link]** - Get the full asset dump and premium support.
*   **[Join our Discord Community](https://discord.gg/nYHGyQ5q)** - Hang out with other developers and get setup help.

---

## ⚠️ Compliance & Disclaimer
This software is designed for **Social Gaming** and **Amusement** purposes using virtual credits.
*   **No Real Money:** This engine is not a gambling product. It processes virtual currency for entertainment.
*   **Open Source:** Provided as-is for educational and development purposes.
```

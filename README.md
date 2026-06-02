# 🕹️ Laravel Social Gaming Engine (Lite 13)

### The Lightweight Open Source RNG Platform (Laravel 11 / PHP 8.4)

![Laravel 11](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge\&logo=laravel) ![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue?style=for-the-badge\&logo=php) ![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge) ![Status](https://img.shields.io/badge/Status-Active_Dev-brightgreen?style=for-the-badge)

> **FORMERLY KNOWN AS:** *opensource-casino-v10*. This repository has been rebranded to focus on Social Gaming Architecture and RNG Logic.

## 📖 About This Version (Lite 13)

This is the **Lite Edition** of the GDM Gaming Engine. It is a re-architected version of the legacy platform, stripped down to the essentials for developers building **Social Arcade** and **Amusement** platforms. It now includes a fully integrated Sportsbook engine and robust payment gateways infrastructure. [DEMO](https://one.377.live)

> ⚠️ **Support Policy**
>
> * No support is included with free use **or** any sponsorship/purchase.
> * Discord is community-only: no guaranteed replies, no DMs, no SLA.
> * If you need 1:1 help, **paid hourly consulting may be available** only if I have availability and after a quote is agreed.

## 💎 Optional Access Perks (Sponsorship)

The Lite version is free for testing and self-hosting. Sponsorship tiers only provide **access perks** (CDN/whitelisting/private downloads/private repos) — **no support is included**.

# SPONSORS AUTOMATICALLY GET A PRIVATE REPO WITH A PREPACKED -ANYHOST- ONE PAGE INSTALL SCRIPT.

<div align="center">
  <h3>
    <a href="https://github.com/sponsors/promexdotme">
      🚀 Click Here to Sponsor & Upgrade
    </a>
  </h3>
</div>

| Feature            |    🆓 Free / Public   |     🥉 $10 Backer     |      🥈 $25 Builder     |       🥇 $99 Enterprise      |
| :----------------- | :-------------------: | :-------------------: | :---------------------: | :--------------------------: |
| **Discord Access** |  🆓 Community/Public  |  🆓 Community/Public  |   🆓 Community/Public   |      🆓 Community/Public     |
| **Infrastructure** | Public (Rate Limited) | Public (Rate Limited) |   **CDN Whitelist** ✅   | **Self-Hosted** (You own it) |
| **Websockets**     |         Shared        |         Shared        | **Private Repo Access** |        **Source Code**       |
| **Game Assets**    |       Cloud Only      |       Cloud Only      |        Cloud Only       |   **40GB Download (Drive)**  |
| **Versions**       |      Lite-13 Only     |      Lite-13 Only     |       Lite-13 Only      |  **Full + Legacy Packaged**  |

### ⚡ Tier Breakdown (Perks Only — No Support Included)

* **$10 (Backer):** Helps fund infrastructure. Optional access to community chat (community discussion only; no guarantees).
* **$25 (Builder):** **CDN Whitelist.** We authorize your domain on our High-Speed Game CDN & Cloud Sockets. Plus, private repo access (reference/automation).
* **$99 (SaaS Studio):** **Total Ownership.** Google Drive link to download all **40GB of Assets** to your own server. Includes Legacy v10 versions, AI scripts.

**Major Upgrade: Hybrid Cloud Architecture**
Unlike previous versions, Lite 13 is designed to run on **Shared Hosting (CPanel)** without root access.

* **Websockets:** Hosted on a dedicated external instance (No local Node/PM2 required).
* **Game Assets:** Served via CDN (Saves you 40GB+ of storage space).
* **Proxying:** The included `.htaccess` handles the traffic seamlessly.

## 🚀 Architecture Changes (The "Minify" Plan)

### 1. Zero-Config Deployment (Shared Hosting Ready)

We have removed the requirement for VPS root access.

* **No PM2/Node.js Required:** The client connects to our public socket instance by default.
* **No Huge Downloads:** The `/games/` directory is served remotely via CDN, making this repo lightweight and fast to deploy.
* **Easy Overrides:** If you prefer to self-host everything, you can simply point `socket_config.json` and `.htaccess` to your own infrastructure.

### 2. Database & Role Simplification

* **Single Tenant:** Multi-shop tables (`w_shops_countries`, etc.) dropped. Enforces `shop_id=1` via `ForceShopOne` middleware.
* **Roles:** Reduced to `Admin` and `User` only (No complex Agent trees).
* **Clean Code:** Removed legacy bloat (SMS, Pincodes, HappyHours) to focus on performance.

## 🛠️ Key Features

### ⚽ Sportsbook Integration *(New in Lite 13)*

* **Dynamic Sync Engine**: Native integration with The Odds API to fetch upcoming matches, in-play fixtures, and markets.
* **American to Decimal Conversion**: Auto-converts American odds formats (e.g. -110, +250) to standardized decimal odds (e.g. 1.91, 3.50) dynamically.
* **Single & Parlay Bet Logic**: Full betslip logic supporting both single and multi-selection (parlay) bets with auto-calculators.
* **Ops Control Console**: Admin dashboard panel to toggle categories/leagues/games status, run manual sync triggers, and declare winning outcomes (settlement engine) with auto-ledger adjustments.

### 💳 Virtual Economy & Payment Gateways *(New in Lite 13)*

* **Crypto & Fiat Gateways**: Support for multi-gateways payments (Stripe Checkout sessions, PayPal Orders v2, and BTCPay Server crypto).
* **Manual Bank Transfers**: User submission form to upload transaction proofs and receipts.
* **Review Queue**: Dedicated Liteback Admin Deposits Review panel for validating and crediting player balances.
* **Extensibility**: Implement `VanguardLTE\\Services\\Payments\\PaymentDriverInterface` to add new payment gateways.

### 📱 Responsive Sportsbook UI *(New in Lite 13)*

* **Sticky Sidebar Filters**: Scroll-locked search input and compact categories list.
* **Adaptive Mobile Elements**: Links dynamically convert to custom dark-themed selects on mobile viewports to prevent cut-off scroll pill layouts.
* **Floating Betslip Drawer**: Sliding bet drawer with backdrop blurs, active selection counts, and clear-all actions.

### 🎮 Game Management (Liteback)

* **Active/Inactive Logic:** Seamlessly move games between active and inactive states.
* **RNG Engine:** Certifiable logic for game outcomes.
* **Visual Management:** Auto-generates icons from `/frontend/Default/ico/{name}.jpg`.

### 🎨 Customizable Hero Section

The frontend features a dynamic hero banner configurable via filesystem:

* **Location:** `/public/minimal/hero/`
* **Desktop Video:** `hero.mp4` (1920x1080, <10MB, Muted Loop).
* **Images:** `hero-desktop.jpg` and `hero-mobile.jpg`.
* *Note:* Enable by setting `$showHero = true` in `resources/views/frontend/Minimal/games/list.blade.php`.

---

## 📦 Installation

1. **Clone the repo:**

   ```bash
   git clone -b lite-13 https://github.com/gamingdotme/laravel-social-gaming.git
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

   * **Configure DB:** Set your MySQL credentials.
   * **Configure Domain:** You **MUST** set your app domain in `.env`:

     ```env
     APP_URL=https://your-domain.com
     ```
4. **Migrate & Seed:**

   ```bash
   php artisan migrate
   ```

### ☁️ How the Hybrid Connection Works

You do not need to install a socket server. The system uses:

1. **`socket_config.json`**: Defines the connection to the external websocket instance.
2. **`.htaccess`**: Acts as a reverse proxy to route traffic correctly on shared hosting.
3. **CDN**: Loads heavy game assets from our cloud, saving your disk space.

---

## 💎 Premium Assets & Self-Hosting

This Lite version relies on cloud assets to keep your deployment simple and cheap.

**Want to self-host everything?**
If you have your own dedicated server (VPS) and want the full **40GB Game Asset Pack** + **Local Websocket Source Code**:

* **[Subscribe for Shared Hosting Access / Google Drive Link]** - Get the full asset dump + access perks (no support included).
* **[Join our Discord Community](https://discord.gg/nYHGyQ5q)** - Community discussion (no guaranteed replies).

---

## ⚠️ Compliance & Disclaimer

This software is designed for **Social Gaming** and **Amusement** purposes using virtual credits.

* **No Real Money:** This engine is not a gambling product. It processes virtual currency for entertainment.
* **Open Source:** Provided as-is for educational and development purposes.

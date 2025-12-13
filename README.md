# 🛑 Legacy Gaming Engine (v10 / v11 Architecture)
### [DISCONTINUED] Multi-Shop, Agent & Distributor System

> **⚠️ STATUS: END OF LIFE.**
> This branch (`legacy-11`) is **no longer maintained**. It contains the complex "Shop/Agent" architecture used in previous versions.
>
> **👉 FOR NEW PROJECTS, USE THE MAIN BRANCH (LITE-12):**
> **[Click here to view the new Laravel Social Gaming Engine](https://github.com/promexdotme/laravel-social-gaming)**

---

## 💎 Need these files packaged?
If you are looking for a clean, packaged zip of this Legacy version (along with the 40GB assets that go with it), these are available exclusively to **Enterprise Sponsors**.

<div align="center">
  <h3>
    <a href="https://github.com/sponsors/promexdotme">
      🚀 Sponsor ($99 Tier) to Download Legacy Packs
    </a>
  </h3>
</div>

---

## 📖 About This Legacy Version
This is the heavy, multi-tenant version of the platform. Unlike the new "SaaS" version, this architecture relies on a strict hierarchy of roles.

**Tech Stack:**
*   **Backend:** Laravel 8/9/10 (Requires older PHP versions)
*   **Frontend:** Blade + JQuery (Legacy)
*   **Structure:** Multi-Shop System

### 🏛️ Hierarchy System (Old Model)
This version includes the complex user tree logic:
1.  **Admin:** Root control.
2.  **Agent:** Manages distributors.
3.  **Distributor:** Manages shops.
4.  **Shop Manager:** Manages cashiers.
5.  **Cashier:** Handles user deposits.
6.  **User:** The player.

### 🛠️ Legacy Features (Not in Lite)
*   **SMS Mailings:** (Requires 3rd party gateways).
*   **Pincode System:** Voucher generation.
*   **Happy Hours:** Time-based bonus triggers.
*   **Progress Bonuses:** Activity-based rewards.
*   **Geo-Blocking:** `w_shops_countries` tables.

## ⚠️ Installation Warning
**We do not provide free support for this branch.**
Setting up this architecture is complex and requires:
1.  Specific PHP extensions (GMP, BCMath).
2.  Root VPS access to run the legacy Node.js socket server.
3.  Manual database seeding for the hierarchy to work.

**If you want a modern, easy setup:**
[Switch to the Lite-12 Branch](https://github.com/promexdotme/laravel-social-gaming)

---

## 📦 How to Access Assets
The game assets (40GB) for this version are **NOT** hosted on CDN in this branch. You must self-host them.

| Feature | 🆓 Free / Public | 🥇 $99 Enterprise Sponsor |
| :--- | :---: | :---: |
| **Source Code** | Viewable on GitHub | **Packaged Zip Download** |
| **Game Assets** | Not Included | **40GB Drive Download** |
| **Support** | None | **Email/Meet Consultation** |

[Unlock the Full Legacy Pack](https://github.com/sponsors/promexdotme) OR 
[Purchase one-time 30days access to Games Drive](https://discordapp.com/channels/982859564795957268/1103430463357452428)

# 🛠️ V12 Developer Notes & Minify Plan

## Context
This version (Lite-12) is a strict refactor of the legacy v10 engine. We have moved to a Single Tenant architecture (Shop ID = 1) and removed complex agent hierarchies.

## 📋 Changelog / Clean Up
### Database Changes
*   **Tables Dropped:** `w_shops_countries`, `w_shops_devices`, `w_shops_os`, `w_shops_user`, `w_shop_categories`, `w_quick_shops`.
*   **Consolidation:** Migration `2025_12_11_000001_force_shop_one.php` forces all users to Shop ID 1.
*   **New Tables:** 
    *   `w_transactions`: Simplified transaction logging.
    *   `w_games_inactive`: Optimization for unused games.

### Backend (Liteback)
*   **Removed:** Agent/Distributor roles, SMS Mailings, Pincodes, HappyHours, Progress Bonuses.
*   **Added:** "Liteback" (AdminLTE) - A lightweight admin focusing only on Users, Games, and Settings.
*   **Routes:** API keys and legacy bonus routes return 404 or are blocked by `DisableLegacyFeatures` middleware.

### Frontend & Assets
*   **Hero Media:** Defaulted to off. See `/public/minimal/hero/README.txt` for customization.
*   **Websockets:** Now configured via `socket_config.json` to point to external/cloud instance.

## ⚠️ Migration Notes
If migrating from v10 Legacy:
1.  Run the "Shop Consolidation" migration first.
2.  Run the "Drop Shop Tables" migration second.
3.  Ensure your `.env` has the correct `APP_URL` defined for the new socket proxying to work.
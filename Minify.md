# Minify Plan (Lite Backend + Single Shop)

# Minify Plan (Lite Backend + Single Shop)

**Stack note:** Laravel 11 on PHP 8.4 with AdminLTE (Liteback) for the lightweight admin, Minimal frontend for the games lobby, and custom payment/top-up integrations (BTC Pay). Legacy backend and assets removed in this branch.

## Context
- Laravel app with DB prefix `w_`.
- Only `shop_id=1` is kept; multi-shop tables dropped.
- Roles reduced to `admin` and `user`; distributor/agent/cashier/shop flows removed.
- Tables dropped: `w_shops_countries`, `w_shops_devices`, `w_shops_os`, `w_shops_user`, `w_shop_categories`, `w_quick_shops` (if present).
- Middleware `ForceShopOne` forces `shop_id=1` for authenticated users.
- Liteback admin exists under `/liteback` (AdminLTE UI) with Users + Games modules.
- Ignore edits in backend `/backend` views since they will be removed after the new liteback is finished.
- Focus on cleaning frontend code, routes, controllers, etc.

## Quick Migration Notes
- [ ] Migrations guarded to skip existing core tables.
- [ ] Shop consolidation migration (`2025_12_11_000001_force_shop_one.php`) updates `w_*` tables `shop_id` to 1 and collapses `w_shops` to id=1.
- [ ] Drop migration (`2025_12_11_000002_drop_shop_related_tables.php`) removes shop satellite tables.
- [ ] Create inactive games table clone (`2025_12_12_000110_create_games_inactive_table.php`) — uses prefixed `w_games` if present.
- [ ] Create transactions log table (`2025_12_12_000100_create_transactions_table.php`).
- [ ] Backend role/permission routes removed; backend `RolesController`/`PermissionsController` deleted.
- [ ] Bonus-related routes (happyhours/progress/invite/welcome bonuses/sms bonuses/wheelfortune) removed; controllers not present. Legacy feature blocker middleware still in place.
- [ ] `Checker` middleware simplified (no shop country/os/device lookups).
- [ ] `ForceShopOne` middleware in web group; `DisableLegacyFeatures` middleware blocks legacy route names.

## Minimal Menu Targets (backend)
- [ ] Keep: Dashboard
- [x] Keep: Users (list/add/edit/delete; balance adjust)
- [x]  Keep: Games (list/manage)
- [ ] Keep: Transactions/Stats (single entry if possible)
- [x] Keep: Settings (General/Payment only if needed)
- [ ] Optional: Support
- [ ] Optional: Activity Log
- [x] Drop: Role/permission management (beyond admin/user)
- [x] Drop: Bonuses (happyhours/progress/invite/welcome/sms/wheelfortune)
- [x] Drop: Pincodes/ATM
- [x] Drop: SMS Mailings
- [x] Drop: Pages (info/articles/rules/faq) if unused
- [x] Drop: API keys if unused
- [x] Drop: Old backend sidebars still contain links; routes now 404 for removed features.

## Pending Cleanup (Code)
- [x] Simplify `resources/views/backend/partials/sidebar.blade.php` to the minimal menu.
- [x] Remove shop- and role-layer conditionals in views/controllers that expect distributor/agent/cashier/manager.
- [x] Ensure any remaining queries to dropped tables are removed (shop relations on User/Shop models).
- [x] Optionally remove unused controllers/routes related to dropped features.
- [x] Optionally drop unused tables for bonuses/pincodes/pages/API/SMS mailings if no data needed.

## Liteback Notes (current)
- Users:
  - Routes: `/liteback` (list), POST `/liteback/users` (create), POST `/liteback/users/{user}/balance` (adjust), DELETE `/liteback/users/{user}` (delete).
  - Controller: `app/Http/Controllers/Web/Liteback/UserController.php` (manual balance adjust logs to `w_transactions`, optional note).
  - View: `resources/views/liteback/users/index.blade.php`.
- Games:
  - Routes: `/liteback/games` (active list), `/liteback/games/inactive` (inactive list), POST `/liteback/games/{game}/deactivate`, POST `/liteback/games/{game}/activate`.
  - Controller: `app/Http/Controllers/Web/Liteback/GameController.php` (moves rows between `w_games` and `w_games_inactive`; deletes use name for `w_stat_game` cleanup).
  - View: `resources/views/liteback/games/index.blade.php` (shows icon `/frontend/Default/ico/{name}.jpg`).
- Layout: `resources/views/liteback/layout.blade.php`.
- Uses DB prefix automatically (e.g., `users` -> `w_users`, `games` -> `w_games`).

## Project Structure (key bits)
- Laravel app under `casino/` (routes in `routes/web.php`, controllers in `app/Http/Controllers/...`, middleware in `app/Http/Middleware`).
- Frontend games: blades under `resources/views/frontend/games/list/` and static assets under `games/`.
- Backend legacy views: `resources/views/backend/...` (menus not yet pruned).
- Liteback (new admin): `resources/views/liteback/` and routes/controller as above.
- Logs: `storage/logs/laravel.log`.

## Done
- [x] Removed shop switcher, block/unblock, and shop creation links from legacy backend UI and related settings actions.
- [x] Dropped SMS mailings, pages (info/articles/rules/faq), and API key routes/menus.
- [x] Added Liteback transactions table + logging for manual balance adjustments (with optional note).
- [x] Added Liteback games module with active/inactive lists, move between `w_games` and `w_games_inactive`, and icons.
- [x] Hero media in Minimal frontend is off by default; set `$showHero = true` inside `resources/views/frontend/Minimal/games/list.blade.php` to enable the MP4/JPG hero block (assets under `/minimal/hero/`). 
- [x] BTC Pay Server top-up flow added:
  - Config: `config/payments.php` (`BTCPAY_ENABLED`, `BTCPAY_HOST`, `BTCPAY_STORE_ID`, `BTCPAY_API_KEY`, `BTCPAY_WEBHOOK_SECRET`, `APP_CURRENCY`).
  - Routes: `POST /topup/create` (auth) to start invoice, `POST /payment/webhook/btcpay` (no auth) for settlement.
  - Storage: `payment_intents` table tracks driver/external id/status; credits balance and logs to `transactions` on webhook.
  - Frontend: Member modal “Top up” tab posts amount + driver, opens checkout URL.
- [ ] To add another gateway: implement `VanguardLTE\Services\Payments\PaymentDriverInterface`, register driver config in `config/payments.php`, add webhook route/controller handler that marks `payment_intents` paid and inserts a `transactions` row, and expose the driver in the Top up dropdown.
- [x] Liteback: added Admin Password page/link (navbar) to change the current user's password at `/liteback/profile/password`.


hero section instructions ::

Drop a video at /minimal/hero/hero.mp4 or images at /minimal/hero/hero-desktop.jpg and /minimal/hero/hero-mobile.jpg.

Featured
Customizable hero

Swap media files in /minimal/hero/ for desktop/mobile or MP4 video.
Desktop image: ~1600–1920px wide, 16:9 or 2:1, <400 KB (web-optimized JPG/WebP).
Mobile image: ~900–1200px tall (e.g., 1080×1440), 3:4 or 4:5, <300 KB.
Video: 1920×1080 or 1280×720 MP4 (H.264), 10–15s loop, muted, <8–10 MB; keep motion gentle for mobile.


admin bonxai12

# Sportsbook Operator Playbook

Guides for managing, syncing, and troubleshooting the integrated native Sportsbook module.

---

## 1. Initial Sync Sequence

When deploying the sportsbook for the first time, execute the following bootstrap sequence:

1. **Set API Configuration:**
   Log into the Liteback Admin panel, navigate to **Sportsbook &raquo; Settings**, and enter a valid key for **The Odds API** under API Secret Key. Configure desired regions (e.g., `us,eu`) and markets (e.g., `h2h,spreads,totals`).
   Alternatively, configure via Artisan:
   ```bash
   php artisan tinker --execute="settings()->set('ods_api_key', 'YOUR_KEY_HERE'); settings()->save();"
   ```

2. **Sync Sports & Leagues:**
   Run the initial leagues fetcher to populate the categories and leagues list:
   ```bash
   php artisan sports:sync:leagues
   ```
   *Note: Newly imported leagues are **Disabled** by default. Navigate to **Sportsbook &raquo; Categories / Leagues** in the Admin panel to enable the specific leagues you wish to run.*

3. **Sync Games & Events:**
   Fetch pre-match games for the enabled running leagues:
   ```bash
   php artisan sports:sync:games
   ```

4. **Sync Odds & Markets:**
   Synchronize initial odds and betting markets:
   ```bash
   php artisan sports:sync:odds
   ```

---

## 2. Scheduler Configuration

The following cron intervals are configured in the Console Kernel (`Kernel.php`) and should run automatically via the standard Laravel scheduler (`* * * * * php artisan schedule:run`):

| Command | Recommended Interval | Purpose |
|---|---|---|
| `sports:sync:odds-inplay` | Every Minute | Fetch live live odds updates for active matches |
| `sports:games:open` | Every Minute | Open matches for betting when start window is reached |
| `sports:sync:odds` | Every 5 Minutes | Fetch pre-match odds updates for upcoming matches |
| `sports:events:cleanup` | Hourly | Auto-end stale games, lock active markets, and purge unlinked outcomes |
| `sports:sync:games` | Hourly | Sync newly created pre-match events |
| `sports:sync:leagues` | Daily | Sync newly supported sport keys |

---

## 3. Recovery Sequence

### If Sync is Failing:
1. Navigate to **Sportsbook &raquo; Dashboard** and check the **Recent Sync Logs** to inspect error messages.
2. Confirm the Odds API key is valid and has remaining request quota.
3. Verify server network connectivity by running sync manually in the console:
   ```bash
   php artisan sports:sync:odds --v
   ```

### If Matches/Bets are Stuck:
- If a finished match does not have results declared automatically, navigate to the **Settlements** page in the Admin panel.
- Locate the game and click **Declare Winner** next to the winning outcome. This will automatically update all linked bet selections, calculate parlay multipliers, and credit payouts into player wallets.

---

## 4. Reset & Reseed
If you need to wipe all sports data and reseed settings for testing:
```bash
php artisan sports:reset --force
```

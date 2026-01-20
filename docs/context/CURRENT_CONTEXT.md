# Current Context

## Recently Completed: Global Analytics Settings

### What Was Built
A global settings system that filters all analytics data by:
- **Scope Year** - Circuits filtered by work_order prefix (e.g., "2026-")
- **Cycle Types** - Optional filter for specific cycle types
- **Contractors** - Filter planners by ws_username prefix (e.g., "ASPLUNDH\\")

### Key Files

| File | Purpose |
|------|---------|
| `app/Models/AnalyticsSetting.php` | Singleton model with caching |
| `app/Livewire/Admin/AnalyticsSettings.php` | Admin UI (sudo_admin only) |
| `app/Models/Circuit.php` | Added `forAnalytics()`, `forScopeYear()` scopes |
| `app/Models/User.php` | Added `contractor` accessor, `withAllowedContractors()` scope |

### How It Works
```php
// All analytics queries now use:
Circuit::forAnalytics()->...  // Applies scope year + cycle type filters
User::withAllowedContractors()->...  // Applies contractor filter
```

### Access
- Route: `/admin/analytics-settings`
- Permission: `sudo_admin` role only
- Settings persist globally for all users

---

## Next Up: Enhanced Sync Controls

See `docs/prompt01.md` for the planned feature:
- Manual sync triggers for planned units & aggregates
- Progress feedback (real-time progress bar, live logs)
- Granular controls per circuit

## Quick Commands
```bash
sail up -d                    # Start containers
sail artisan test --compact   # Run tests
sail pint --dirty             # Format changed files
```

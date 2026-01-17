# CLI Commands Reference

## Overview

WS-Tracker provides several custom Artisan commands for managing synchronization with the WorkStudio API and debugging data issues.

## Custom Commands

### workstudio:sync

Manually trigger WorkStudio sync operations.

#### Signature

```bash
sail artisan workstudio:sync
    {type? : Type of sync: circuits, aggregates, snapshots, or all}
    {--status=* : API statuses to sync (ACTIV, QC, REWRK, CLOSE)}
    {--force : Force overwrite user modifications}
    {--queue : Queue the job instead of running synchronously}
    {--preview : Preview what would be synced without making changes}
```

#### Arguments

| Argument | Required | Description |
|----------|----------|-------------|
| `type` | No | Type of sync to perform. If omitted, interactive prompt appears |

**Available Types:**
- `circuits` - Sync circuit list from WorkStudio API
- `aggregates` - Sync planned units and compute aggregates
- `snapshots` - Create daily snapshots for all circuits
- `all` - Run all sync operations in sequence

#### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--status=*` | `ACTIV` | API statuses to sync. Can be specified multiple times |
| `--force` | `false` | Force overwrite user-modified fields |
| `--queue` | `false` | Queue the job instead of running synchronously |
| `--preview` | `false` | Show preview of changes without executing |

#### Examples

```bash
# Interactive mode (prompts for options)
sail artisan workstudio:sync

# Sync active circuits
sail artisan workstudio:sync circuits --status=ACTIV

# Sync multiple statuses
sail artisan workstudio:sync circuits --status=ACTIV --status=QC --status=REWRK

# Preview sync without making changes
sail artisan workstudio:sync circuits --preview

# Force sync (overwrites user modifications)
sail artisan workstudio:sync circuits --force

# Queue the sync job
sail artisan workstudio:sync circuits --queue

# Sync all types for active circuits
sail artisan workstudio:sync all --status=ACTIV

# Full sync with all statuses (queued)
sail artisan workstudio:sync all --status=ACTIV --status=QC --status=REWRK --status=CLOSE --queue
```

#### Output

The command displays a table with sync settings before execution:

```
 Starting WorkStudio Sync

+-----------------+------------------------+
| Setting         | Value                  |
+-----------------+------------------------+
| Type            | Sync circuit list from API |
| Statuses        | ACTIV, QC              |
| Force Overwrite | No                     |
| Execution       | Synchronous            |
+-----------------+------------------------+
```

---

### ws:dump

Fetch and display WorkStudio API data for verification and debugging.

#### Signature

```bash
sail artisan ws:dump
    {type=circuits : Data type to fetch (circuits, units, health, raw)}
    {--status=ACTIV : Circuit status filter (ACTIV, QC, CLOSED)}
    {--work-order= : Work order number for units (e.g., 2025-1234)}
    {--limit=0 : Limit number of records (0 = no limit)}
    {--raw : Show raw DDOTable data before transformation}
    {--json : Output as JSON instead of table}
    {--output= : Save output to file (path)}
    {--pretty : Pretty print JSON output}
```

#### Arguments

| Argument | Default | Description |
|----------|---------|-------------|
| `type` | `circuits` | Type of data to fetch |

**Available Types:**
- `health` - Check API connectivity and credentials
- `circuits` - Fetch circuit data by status
- `units` - Fetch planned units for a work order
- `raw` - Fetch raw data from any view GUID

#### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--status` | `ACTIV` | Filter circuits by status |
| `--work-order` | - | Work order number (required for `units` type) |
| `--limit` | `0` | Limit number of records (0 = no limit) |
| `--raw` | `false` | Show raw DDOTable format |
| `--json` | `false` | Output as JSON |
| `--output` | - | Save to file path |
| `--pretty` | `false` | Pretty print JSON |

#### Examples

```bash
# Check API health and credentials
sail artisan ws:dump health

# Fetch active circuits (table format)
sail artisan ws:dump circuits --status=ACTIV

# Fetch first 10 circuits
sail artisan ws:dump circuits --limit=10

# Fetch raw API response (before transformation)
sail artisan ws:dump circuits --raw

# Output as JSON
sail artisan ws:dump circuits --json --pretty

# Save circuits to file
sail artisan ws:dump circuits --json --pretty --output=circuits.json

# Fetch planned units for a work order
sail artisan ws:dump units --work-order=2025-1930

# Fetch units with aggregate summary
sail artisan ws:dump units --work-order=2025-1930 --limit=50

# Interactive raw view fetch
sail artisan ws:dump raw
```

#### Health Check Output

```
Checking WorkStudio API health...

+-----------------+--------------------------------------------------+
| Setting         | Value                                            |
+-----------------+--------------------------------------------------+
| Base URL        | https://ppl02.geodigital.com:8372/DDOProtocol/GETVIEWDATA |
| Credential Type | system                                           |
| Username        | system_user                                      |
+-----------------+--------------------------------------------------+

✓ API is reachable
```

#### Circuits Output (Table)

```
Fetching circuits with status: ACTIV
This may take a moment...

Retrieved 47 circuit(s)

+------------+-----------+--------------------------------+---------+-------------+--------------+------------------+
| work_order | extension | title                          | api_status | total_miles | miles_planned | percent_complete |
+------------+-----------+--------------------------------+---------+-------------+--------------+------------------+
| 2025-1930  | @         | HATFIELD 69/12 KV 20-01 LINE   | ACTIV   | 14.93       | 4.38          | 35.00            |
| 2025-2077  | @         | EAST PETERSBURG 69/12 KV...    | ACTIV   | 8.56        | 8.56          | 100.00           |
+------------+-----------+--------------------------------+---------+-------------+--------------+------------------+

Tip: Use --json for full data or --output=file.json to save
```

#### Units Aggregate Summary

```
Fetching planned units for work order: 2025-1930
This may take a moment...

Retrieved 127 unit(s)

=== Aggregate Summary ===
+-----------------+-----------+
| Metric          | Value     |
+-----------------+-----------+
| Total Units     | 127       |
| Total Linear Ft | 12,880.50 |
| Total Acres     | 1.4200    |
| Total Trees     | 45        |
| Units Approved  | 89        |
| Units Refused   | 6         |
| Units Pending   | 32        |
+-----------------+-----------+

=== Unit Counts by Type ===
+------+-------+
| Type | Count |
+------+-------+
| SPM  | 50    |
| HCB  | 25    |
| MPM  | 30    |
| SBM  | 22    |
+------+-------+

=== Planner Distribution ===
+------------------+-------+-----------+--------+
| Planner          | Units | Linear Ft | Acres  |
+------------------+-------+-----------+--------+
| Derek Cinicola   | 75    | 8,200.00  | 0.8500 |
| Paul Longenecker | 52    | 4,680.50  | 0.5700 |
+------------------+-------+-----------+--------+
```

---

## Laravel Built-in Commands

### Useful for Administration

```bash
# View application info
sail artisan about

# Clear caches
sail artisan cache:clear
sail artisan config:clear
sail artisan view:clear
sail artisan route:clear

# Optimize for production
sail artisan optimize

# Database operations
sail artisan migrate:status
sail artisan migrate
sail artisan migrate:rollback

# Queue management
sail artisan queue:work
sail artisan queue:failed
sail artisan queue:retry all
sail artisan queue:flush

# Scheduler
sail artisan schedule:list
sail artisan schedule:run
sail artisan schedule:work

# Interactive shell
sail artisan tinker
```

### Health Checks

```bash
# Run all health checks
sail artisan health:check

# List available checks
sail artisan health:list
```

### Permissions

```bash
# Show roles and permissions
sail artisan permission:show

# Create a role
sail artisan permission:create-role admin

# Create a permission
sail artisan permission:create-permission "manage circuits"

# Assign role to user
sail artisan permission:assign-role admin user@example.com
```

### Activity Log

```bash
# Clean old activity logs
sail artisan activitylog:clean --days=30
```

---

## Command Usage in Scripts

### Automated Sync Script

```bash
#!/bin/bash
# sync-all.sh - Run complete sync operation

set -e

cd /path/to/ws-tracker

# Sync circuits for all statuses
php artisan workstudio:sync circuits --status=ACTIV --status=QC --status=REWRK

# Wait for API rate limit
sleep 60

# Sync aggregates
php artisan workstudio:sync aggregates --status=ACTIV --status=QC

# Create daily snapshots
php artisan workstudio:sync snapshots

echo "Sync completed at $(date)"
```

### Export Data Script

```bash
#!/bin/bash
# export-data.sh - Export circuit data for reporting

EXPORT_DIR="/path/to/exports"
DATE=$(date +%Y%m%d)

cd /path/to/ws-tracker

# Export active circuits
php artisan ws:dump circuits --status=ACTIV --json --pretty \
    --output="${EXPORT_DIR}/circuits_activ_${DATE}.json"

# Export QC circuits
php artisan ws:dump circuits --status=QC --json --pretty \
    --output="${EXPORT_DIR}/circuits_qc_${DATE}.json"

echo "Export completed: ${EXPORT_DIR}"
```

### Monitoring Script

```bash
#!/bin/bash
# check-sync.sh - Verify recent syncs completed successfully

cd /path/to/ws-tracker

# Check for failed syncs in last 24 hours
FAILED=$(php artisan tinker --execute="
    echo App\Models\SyncLog::failed()
        ->recent(24)
        ->count();
")

if [ "$FAILED" -gt 0 ]; then
    echo "WARNING: $FAILED failed syncs in last 24 hours"
    exit 1
fi

echo "All syncs successful"
exit 0
```

---

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | Failure (general error) |

Commands follow standard Unix conventions for exit codes, making them suitable for use in scripts and CI/CD pipelines.

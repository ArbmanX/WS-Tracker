# Administrator Guide

## Overview

This guide is intended for system administrators who need to deploy, configure, and maintain the WS-Tracker application.

## Prerequisites

- Docker and Docker Compose (for Sail)
- PHP 8.4+ (if not using Sail)
- PostgreSQL 15+
- Node.js 20+ and npm
- Composer 2.x

## Installation

### Using Laravel Sail (Recommended)

```bash
# Clone the repository
git clone <repository-url> ws-tracker
cd ws-tracker

# Copy environment file
cp .env.example .env

# Install PHP dependencies
composer install

# Start Sail containers
./vendor/bin/sail up -d

# Generate application key
sail artisan key:generate

# Run migrations
sail artisan migrate

# Seed the database
sail artisan db:seed

# Install Node dependencies and build assets
sail npm install
sail npm run build
```

### Without Sail

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start the application
php artisan serve
```

## Environment Configuration

### Required Variables

```env
# Application
APP_NAME="WS-Tracker"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=ws_tracker
DB_USERNAME=sail
DB_PASSWORD=password

# WorkStudio API
WORKSTUDIO_BASE_URL=https://ppl02.geodigital.com:8372/DDOProtocol/
WORKSTUDIO_SYSTEM_USERNAME=your_system_username
WORKSTUDIO_SYSTEM_PASSWORD=your_system_password

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=database

# Session
SESSION_DRIVER=database
```

### WorkStudio API Configuration

The `config/workstudio.php` file contains API-specific settings:

```php
return [
    'base_url' => env('WORKSTUDIO_BASE_URL'),

    // View GUIDs for data fetching
    'views' => [
        'vegetation_assessments' => '{view-guid-for-circuits}',
        'planned_units' => '{view-guid-for-units}',
    ],

    // API status configurations
    'statuses' => [
        ['value' => 'ACTIV', 'caption' => 'In Progress'],
        ['value' => 'QC', 'caption' => 'Quality Control'],
        ['value' => 'REWRK', 'caption' => 'Rework'],
        ['value' => 'CLOSE', 'caption' => 'Closed'],
    ],

    // Sync settings
    'sync' => [
        'calls_before_delay' => 5,
        'rate_limit_delay' => 500000, // microseconds
    ],

    'max_retries' => 5,
];
```

## Queue Management

### Starting the Queue Worker

```bash
# Using Sail
sail artisan queue:work --queue=default

# Without Sail
php artisan queue:work --queue=default
```

For production, use a process manager like Supervisor:

```ini
[program:ws-tracker-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=default --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/ws-tracker-worker.log
```

### Monitoring Queues

```bash
# Check queue status
sail artisan queue:monitor

# View failed jobs
sail artisan queue:failed

# Retry failed jobs
sail artisan queue:retry all

# Clear failed jobs
sail artisan queue:flush
```

## Scheduler Setup

The application uses Laravel's scheduler for automated syncs. Set up a cron job:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or with Sail:
```bash
# Start the schedule worker (for development)
sail artisan schedule:work
```

### Scheduled Tasks

| Time (ET) | Days | Task | Description |
|-----------|------|------|-------------|
| 4:30 AM | Weekdays | `SyncCircuitsJob (ACTIV)` | Morning sync of active circuits |
| 4:30 PM | Weekdays | `SyncCircuitsJob (ACTIV)` | Afternoon sync of active circuits |
| 4:30 AM | Monday | `SyncCircuitsJob (QC,REWRK,CLOSE)` | Weekly sync of other statuses |
| 5:00 AM | Weekdays | `CreateDailySnapshotsJob` | Daily snapshots |
| 5:30 AM | Weekdays | `SyncCircuitAggregatesJob` | Morning aggregates |
| 5:30 PM | Weekdays | `SyncCircuitAggregatesJob` | Afternoon aggregates |

### Viewing Scheduled Tasks

```bash
sail artisan schedule:list
```

## Manual Sync Operations

### Sync Circuits

```bash
# Interactive mode
sail artisan workstudio:sync

# Sync specific status
sail artisan workstudio:sync circuits --status=ACTIV

# Sync multiple statuses
sail artisan workstudio:sync circuits --status=ACTIV --status=QC

# Force overwrite user modifications
sail artisan workstudio:sync circuits --force

# Preview changes without syncing
sail artisan workstudio:sync circuits --preview

# Queue the job instead of running synchronously
sail artisan workstudio:sync circuits --queue
```

### Sync Aggregates

```bash
sail artisan workstudio:sync aggregates --status=ACTIV
```

### Create Snapshots

```bash
sail artisan workstudio:sync snapshots
```

### Run All Sync Operations

```bash
sail artisan workstudio:sync all --status=ACTIV
```

## Debugging & Troubleshooting

### API Connection Testing

```bash
# Check API health
sail artisan ws:dump health

# Fetch circuits with verbose output
sail artisan ws:dump circuits --status=ACTIV -v

# Fetch raw API response
sail artisan ws:dump circuits --status=ACTIV --raw

# Fetch planned units for a work order
sail artisan ws:dump units --work-order=2025-1930

# Output as JSON
sail artisan ws:dump circuits --json --pretty
```

### Viewing Logs

```bash
# Application logs
sail artisan pail

# Or directly
tail -f storage/logs/laravel.log
```

### Checking Sync History

Query the `sync_logs` table to see sync history:

```sql
SELECT
    sync_type,
    sync_status,
    sync_trigger,
    started_at,
    duration_seconds,
    circuits_processed,
    circuits_created,
    circuits_updated,
    error_message
FROM sync_logs
ORDER BY started_at DESC
LIMIT 20;
```

### Common Issues

#### 1. API Authentication Failures

**Symptom**: Sync fails with 401 errors

**Solution**:
- Verify `WORKSTUDIO_SYSTEM_USERNAME` and `WORKSTUDIO_SYSTEM_PASSWORD` in `.env`
- Check user credentials in `user_ws_credentials` table
- Run `sail artisan ws:dump health` to test connectivity

#### 2. Sync Taking Too Long

**Symptom**: Sync jobs timeout or run for extended periods

**Solution**:
- Check rate limiting settings in `config/workstudio.php`
- Use queued sync: `sail artisan workstudio:sync --queue`
- Review `sync_logs` for circuits with errors

#### 3. Missing Aggregates

**Symptom**: Circuit aggregates not appearing

**Solution**:
- Ensure `planned_units_sync_enabled` is true for the circuit
- Check if circuit is excluded (`is_excluded = true`)
- Run aggregate sync manually: `sail artisan workstudio:sync aggregates`

#### 4. User Modifications Being Overwritten

**Symptom**: User changes are lost after sync

**Solution**:
- Do NOT use `--force` flag for regular syncs
- Check `user_modified_fields` column for tracking
- Review sync logs for "user_preserved_fields" entries

## Database Maintenance

### Backup

```bash
# Using Sail
sail pg_dump ws_tracker > backup_$(date +%Y%m%d).sql

# Without Sail
pg_dump -U username ws_tracker > backup_$(date +%Y%m%d).sql
```

### Restore

```bash
# Using Sail
sail psql ws_tracker < backup_20260116.sql

# Without Sail
psql -U username ws_tracker < backup_20260116.sql
```

### Cleanup Old Data

```bash
# Clear old activity logs (older than 30 days)
sail artisan activitylog:clean --days=30

# Prune old sync logs
sail artisan tinker
>>> App\Models\SyncLog::where('started_at', '<', now()->subDays(90))->delete();
```

## Security Best Practices

### 1. Environment Security

- Never commit `.env` file to version control
- Use strong passwords for database and API credentials
- Rotate WorkStudio API credentials periodically

### 2. Application Security

```bash
# Optimize for production
sail artisan config:cache
sail artisan route:cache
sail artisan view:cache
sail artisan optimize
```

### 3. Access Control

- Assign appropriate roles to users via Spatie Permission
- Enable two-factor authentication for admin accounts
- Review `activity_log` table for suspicious activity

### 4. SSL/TLS

Ensure all connections use HTTPS:
- Application: Use SSL certificate
- Database: Enable SSL connections
- WorkStudio API: Already uses HTTPS

## Monitoring

### Health Checks

The application includes health checks via the `spatie/laravel-health` package:

```bash
# Run all health checks
sail artisan health:check

# List available checks
sail artisan health:list
```

### Key Metrics to Monitor

1. **Queue Length**: Jobs waiting in queue
2. **Failed Jobs**: Count of failed queue jobs
3. **Sync Duration**: Time taken for sync operations
4. **API Response Time**: WorkStudio API latency
5. **Database Size**: Monitor aggregate tables growth

### Alerting

Configure alerting for:
- Sync failures (check `sync_logs` where `sync_status = 'failed'`)
- Queue backup (jobs not processing)
- API unavailability (health check failures)

## Scaling Considerations

### Multiple Queue Workers

For larger deployments, run multiple queue workers:

```bash
# Run 3 workers
sail artisan queue:work --queue=default &
sail artisan queue:work --queue=default &
sail artisan queue:work --queue=default &
```

### Database Optimization

- Add indexes for frequently queried columns
- Consider partitioning aggregate tables by date
- Use connection pooling (PgBouncer)

### Caching

Enable caching for frequently accessed data:
- Region list
- API status configurations
- User permissions

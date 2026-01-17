# WS-Tracker Application Overview

## Introduction

WS-Tracker is a Laravel-based vegetation assessment circuit tracking application. It integrates with the WorkStudio API (GeoDigital) to synchronize circuit data, planned units, and aggregate metrics for vegetation management operations.

## Purpose

The application serves as a central dashboard and tracking system for:

- **Circuit Management**: Track vegetation assessment circuits (work orders) across multiple regions
- **Planner Assignment**: Link planners (users) to circuits and track their work
- **Aggregate Metrics**: Calculate and store daily aggregates for units, linear feet, acres, and trees
- **Progress Tracking**: Monitor completion percentages, miles planned, and workflow stages
- **Historical Snapshots**: Capture point-in-time snapshots for historical analysis

## Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Backend Framework | Laravel | 12.46.0 |
| PHP Version | PHP | 8.4.16 |
| Frontend | Livewire | 3.7.3 |
| UI Components | Flux UI (Free) | 2.10.2 |
| CSS Framework | Tailwind CSS | 4.1.11 |
| Database | PostgreSQL | pgsql |
| Testing | Pest | 4.3.1 |
| Authentication | Laravel Fortify | 1.33.0 |
| Queue System | Laravel Queues | Database driver |
| Container | Docker via Laravel Sail | 1.52.0 |

## Key Features

### 1. Circuit Synchronization
- Automatic sync from WorkStudio API on a configurable schedule
- User-priority sync pattern that preserves manual modifications
- Support for multiple API statuses: ACTIV, QC, REWRK, CLOSE

### 2. Planned Units Aggregation
- Fetches individual planned units from WorkStudio
- Computes aggregates by circuit: total units, linear ft, acres, trees
- Tracks permission statuses: approved, pending, refused
- Stores detailed breakdowns by unit type and planner

### 3. Workflow Management
- Five workflow stages: Active, Pending Permissions, QC, Rework, Closed
- Kanban-style dashboard for visual circuit tracking
- Drag-and-drop stage transitions (UI in development)

### 4. Historical Snapshots
- Daily snapshots of circuit state
- Status change snapshots when circuits transition
- Milestone snapshots for significant progress points (25%, 50%, 75%, 100%)

### 5. Multi-Region Support
- Circuits organized by region
- Regional daily and weekly aggregates
- Region-scoped filters and reporting

### 6. User Management
- Role-based access control (Spatie Permissions)
- Two-factor authentication support
- WorkStudio credential linking for API access

## Application Structure

```
WS-Tracker/
├── app/
│   ├── Console/Commands/WorkStudio/    # CLI commands for sync
│   ├── Enums/                          # Application enums
│   ├── Jobs/                           # Queue jobs for sync operations
│   ├── Livewire/                       # Livewire components
│   ├── Models/                         # Eloquent models
│   └── Services/WorkStudio/            # API integration services
├── config/
│   └── workstudio.php                  # WorkStudio API configuration
├── database/
│   ├── migrations/                     # Database schema
│   └── factories/                      # Model factories for testing
├── resources/views/
│   ├── components/                     # Blade components
│   └── livewire/                       # Livewire views
├── routes/
│   ├── web.php                         # Web routes
│   └── console.php                     # Scheduled tasks
└── tests/
    ├── Feature/                        # Feature tests
    └── Unit/                           # Unit tests
```

## Core Domain Concepts

### Circuit
A vegetation assessment work order from WorkStudio. Each circuit represents a power line that needs vegetation assessment and management.

- **work_order**: Unique identifier (e.g., "2025-1930")
- **extension**: Split indicator ("@" for main, "A", "B", "C" for splits)
- **job_guid**: WorkStudio unique identifier
- **api_status**: Current WorkStudio status (ACTIV, QC, REWRK, CLOSE)

### Planned Unit
An individual vegetation management unit within a circuit. Units can be trees, brush clearing areas, or linear right-of-way sections.

### Circuit Aggregate
Daily summary of planned units for a circuit, including:
- Total counts (units, linear ft, acres, trees)
- Permission status breakdown
- Unit type distribution
- Planner distribution

### Workflow Stage
Internal tracking stage separate from API status:
- **active**: Currently being worked
- **pending_permissions**: Waiting for landowner permissions
- **qc**: In quality control review
- **rework**: Needs corrections
- **closed**: Completed and closed

## Authentication Flow

1. User logs in via Laravel Fortify
2. Optional two-factor authentication
3. Session-based authentication for web access
4. WorkStudio credentials stored encrypted for API access

## Configuration

Key configuration files:
- `config/workstudio.php` - API endpoints, view GUIDs, sync settings
- `.env` - Environment variables for credentials and URLs

Environment variables:
```env
WORKSTUDIO_BASE_URL=https://ppl02.geodigital.com:8372/DDOProtocol/
WORKSTUDIO_SYSTEM_USERNAME=system_user
WORKSTUDIO_SYSTEM_PASSWORD=system_password
```

## Running the Application

### Development
```bash
# Start Sail containers
sail up -d

# Run migrations
sail artisan migrate

# Seed database
sail artisan db:seed

# Start Vite dev server
npm run dev
```

### Production
```bash
# Build assets
npm run build

# Run queues
php artisan queue:work

# Start scheduler
php artisan schedule:work
```

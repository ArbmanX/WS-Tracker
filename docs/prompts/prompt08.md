# Weekly Progress Tracking Automation

## Optimized Prompt

**Implement automated weekly progress tracking system for planner mileage with Friday snapshots and delta-based reporting.**

### Objective:
Create the automated system that captures planner circuit data every **Friday end-of-day**, enabling week-over-week delta calculations for supervisor reporting.

### Business Requirements:
- **Snapshot Timing:** Friday end-of-day (matching current manual process)
- **Calculation Method:** This Friday's completed miles - Last Friday's completed miles = Weekly progress
- **Multi-Circuit Support:** Sum deltas across all circuits assigned to a planner
- **Target Validation:** Compare total delta to 6.5 mile weekly quota
- **Data Quality:** Track assessment last modified dates to flag stale data

### How It Mirrors the Manual Process:
1. **Current Manual:** Copy all circuits + miles every Friday EOD
2. **Compare:** Subtract previous Friday's copy from current
3. **Aggregate:** Sum deltas for planners with multiple circuits
4. **Automated:** Same logic, scheduled job captures the "Friday copy"

### Tasks:

#### 1. Scheduled Snapshot Job:
- Create `CaptureFridayCircuitSnapshot` job
- Schedule to run **Friday at 5:00 PM** (or configurable EOD time)
- Capture **per circuit** (not per planner):
  - circuit_id
  - completed_miles (current value)
  - planner_identifier
  - assessment_last_modified (for freshness validation)
  - snapshot_date (Friday date)

#### 2. Snapshot Storage:
- Create `circuit_mile_snapshots` migration:
  ```
  - id
  - circuit_id (foreign key)
  - snapshot_date (date - the Friday)
  - completed_miles (decimal)
  - planner_identifier (string)
  - assessment_last_modified (datetime - from API)
  - is_stale (boolean - calculated at snapshot time)
  - created_at
  ```
- Create model with relationships to Circuit

#### 3. Progress Calculation Service:
- `WeeklyProgressService` class with methods:
  - `getCircuitDelta(circuit, fridayDate)` - single circuit progress
  - `getPlannerWeeklyProgress(planner, fridayDate)` - sum of all circuit deltas
  - `getAllPlannerProgress(fridayDate)` - supervisor overview
- Returns: miles_delta, circuits_count, has_stale_data, meets_quota (>= 6.5)

#### 4. Data Freshness Check:
- At snapshot time, check `assessment_last_modified`
- Flag as stale if older than threshold (configurable, default 5 days)
- Store `is_stale` boolean on snapshot record
- Propagate stale warnings to progress calculations

#### 5. Manual Trigger Option:
- Admin ability to trigger snapshot manually (any day)
- Useful for testing or mid-week checks
- Add to Sync Controls page
- Clear labeling: "Manual snapshot (not a Friday)"

#### 6. Reporting Query Support:
- Efficient queries for supervisor reports:
  - All planners for a given Friday
  - Single planner trend over multiple weeks
  - Aggregate stats (average, min, max, % meeting quota)
  - Filter: exclude/include stale data

### Technical Constraints:
- Use Laravel task scheduling (`schedule:run`)
- Follow existing job patterns in codebase
- Include failed job handling with retry
- Write comprehensive tests
- Index on (circuit_id, snapshot_date) for fast lookups

### Edge Cases to Handle:
- Planner with no active circuits (0 miles, still show in report)
- New planner (no prior Friday baseline - show N/A or just current)
- Circuits added mid-week (only partial week delta)
- Circuits reassigned between planners mid-week
- Missing Friday snapshot (holiday, system down) - interpolation or skip?
- All circuits stale for a planner (warn supervisor)

---

## Context

This is the "data engine" that powers supervisor reporting. Without reliable weekly snapshots, we can't track progress accurately. This should run automatically but support manual intervention.

## Dependencies
- Prompt 03 (Schema Redesign) - Miles data available
- Prompt 07 (Planner Mileage Metrics) - Display layer

## Success Criteria
- [ ] Scheduled job runs Friday EOD
- [ ] Per-circuit snapshots stored with completed miles
- [ ] Assessment last_modified captured for freshness
- [ ] Progress calculation service calculates deltas correctly
- [ ] Multi-circuit planner totals aggregate properly
- [ ] Manual trigger available on Sync Controls
- [ ] Stale data flagged appropriately
- [ ] Tests cover happy path and edge cases

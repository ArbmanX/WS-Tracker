# Planner Analytics - Mileage Metrics

## Optimized Prompt

**Implement planner-specific analytics focusing on mileage tracking with weekly targets and progress indicators.**

### Objective:
Build the core planner analytics feature showing mileage data per planner. Track **completed miles** on active circuits using a weekly delta comparison method.

### Business Requirements:
- **Weekly Target:** Planners are required to complete **6.5 miles per week**
- **Work Week:** 40 hours (standard work week)
- **Tracking Method:** Compare completed miles between weekly snapshots (Friday to Friday)
- **Multi-Circuit Planners:** Sum the deltas from all assigned circuits to get total weekly progress
- **Reporting:** Supervisors must track planner progress and report on it

### How Mileage Tracking Works (Current Manual Process):
1. Every Friday (end of day), capture **completed miles** for each circuit
2. Compare to the previous Friday's snapshot
3. **This Week's Progress = Current Completed Miles - Last Week's Completed Miles**
4. For planners working multiple circuits: sum all circuit deltas together
5. Compare total to 6.5 mile weekly quota

### Tasks:

#### 1. Planner Metrics Display:
- Miles completed this week vs 6.5 mile target
- Progress bar/indicator showing % of weekly target (6.5 mi = 100%)
- Status indicator (on track, behind, ahead)
- Breakdown by circuit showing each circuit's delta contribution

#### 2. Per-Planner View:
- Individual planner cards/rows showing:
  - Planner name
  - Active circuits count
  - Per-circuit breakdown: Circuit Name | Last Week Miles | This Week Miles | Delta
  - **Total Weekly Miles** (sum of all circuit deltas)
  - Target achievement percentage (total / 6.5 * 100)

#### 3. Weekly Comparison Display:
- Show side-by-side: Previous Friday snapshot vs Current snapshot
- Highlight the delta (difference) prominently
- Color code: Green (met quota), Yellow (close), Red (behind)

#### 4. Supervisor Dashboard View:
- All planners overview in sortable table/cards
- Columns: Planner | Circuits | Weekly Miles | % of Target | Status
- Sort by performance (% of target)
- Highlight planners below target requiring attention
- Quick filters: Show only behind, show only ahead, show all

#### 5. Data Freshness Validation (Critical):
The circuit assessment **last modified date** determines if data is accurate. If a planner hasn't synced their device to the server, API data will be stale and calculations unreliable.

- Display "Last Modified" date for each circuit assessment
- **Stale Data Warning:** Flag circuits where last modified > X days old (configurable, suggest 3-5 days)
- Visual indicators:
  - ✅ Fresh (modified within threshold)
  - ⚠️ Warning (approaching stale)
  - ❌ Stale (needs sync)
- **Per-Planner Sync Status:** Show if any of their circuits have stale data
- **Action Required:** "Remind planner to sync device" button/notification
- Block or warn when calculating weekly progress with stale data

**UI Behavior:**
- Stale circuits should show warning badge on the circuit row
- Supervisor dashboard should highlight planners with stale data
- Consider: "Data may be inaccurate - planner needs to sync" message
- Option to exclude stale circuits from calculations with clear indication

### Data Model Considerations:
- `circuit_mile_snapshots` table for per-circuit Friday captures
  - circuit_id, snapshot_date, completed_miles, planner_identifier, **assessment_last_modified**
- Calculate deltas on-the-fly or store in `planner_weekly_progress` table
- Capture: planner_id, week_ending (Friday date), total_miles_delta, circuits_data (JSON), **has_stale_data (boolean)**
- Store the `last_modified` timestamp from API on the circuits table

### Technical Constraints:
- Build on Analytics Dashboard (Prompt 06)
- Use existing circuit/planner relationships
- Efficient queries for multiple planners
- Handle edge cases: new circuits mid-week, reassigned circuits
- **Validate data freshness before any calculations**

### NOT in Scope (for this prompt):
- Historical/lifetime totals (future enhancement)
- Non-active circuit statuses
- Automated notifications (manual "remind to sync" only)

---

## Context

This is where the core planner tracking happens. The 6.5 miles/week requirement is the key metric supervisors need to monitor. Focus on making this data clear and actionable.

## Dependencies
- Prompt 03 (Schema Redesign) - Miles data in database
- Prompt 06 (Analytics Dashboard Foundation) - Container for this data

## Success Criteria
- [ ] Per-planner metrics visible
- [ ] Weekly target (6.5 mi) clearly shown
- [ ] Progress toward target indicated
- [ ] Supervisor can see all planners at once
- [ ] Weekly snapshot mechanism in place
- [ ] Data freshness (last modified) displayed per circuit
- [ ] Stale data warnings visible and actionable

# Database Schema Redesign for Active Circuits

## Optimized Prompt

**Redesign the database schema for active circuits to store additional API fields identified in the data audit, with focus on mileage tracking for planner analytics.**

### Objective:
Based on the data audit findings (Prompt 02), implement schema changes to store additional fields directly in the database. This creates the foundation for accurate planner analytics.

### Tasks:

#### 1. Schema Migration Planning:
- Review the gap analysis from Prompt 02
- Prioritize fields needed for:
  - Mileage tracking (total miles planned at sync time)
  - Planner identification
  - Circuit status and completion data
- Design migration(s) to add new columns

#### 2. Migration Implementation:
- Create migration(s) for new columns on `circuits` table (or related tables)
- Add appropriate indexes for query performance
- Consider nullable vs default values for existing records

#### 3. Model Updates:
- Update Eloquent model(s) with new fillable fields
- Add any necessary casts (dates, decimals, etc.)
- Update factory if new required fields added

#### 4. Sync Job Updates:
- Modify the sync job to extract and store new fields from API response
- Ensure backward compatibility with existing data
- Add logging for new field population

### Technical Constraints:
- Migrations must be reversible (include `down()` method)
- Follow Laravel 12 migration conventions
- Run `vendor/bin/pint --dirty` after changes
- Write tests for new sync behavior

### Expected Deliverables:
- Migration file(s) for schema changes
- Updated model(s)
- Updated sync job
- Tests confirming new data is stored

---

## Context

This builds on the data audit (Prompt 02) to actually implement storage improvements. The schema should be designed with planner analytics in mind, particularly tracking miles at point-in-time for historical comparison.

## Dependencies
- Prompt 02 (Data Audit) - Must be completed first

## Success Criteria
- [ ] Migrations created and tested
- [ ] Models updated with new fields
- [ ] Sync job stores new data correctly
- [ ] Existing functionality unaffected
- [ ] Tests pass

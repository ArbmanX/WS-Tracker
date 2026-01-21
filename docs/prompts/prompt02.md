# Active Circuits Data Audit

## Optimized Prompt

**Audit the WorkStudio API response for active circuits against current database storage to identify missing/unused fields.**

### Objective:
Perform a comprehensive comparison between what the WorkStudio DDOProtocol API returns for active circuits versus what we currently store in the database. This is a discovery phase to inform schema improvements.

### Tasks:

#### 1. API Response Analysis:
- Make a test API call for active circuits using the existing sync mechanism
- Log or dump to file into the @docs/DataRefactor folder the complete API response structure
- Document ALL available fields with their data types and example values
- Pay special attention to:
  - **Mileage-related fields** (total miles, completed miles, planned miles, etc.)
  - **Assessment last modified date** (CRITICAL for data freshness validation)
  - Planner identifier fields
  - Status fields
- use the index.blade.php file in the @resources/views/assessments/ folder to display and compare the data from DB and API

#### 2. Current Database Schema Review:
- List all columns in the `circuits` table and related tables
- Identify what fields we're currently extracting and storing
- Note any transformations being applied to API data

#### 3. Gap Analysis:
- Create a comparison table: API Field | DB Column | Status (stored/ignored/transformed)
- Highlight fields that exist in API but aren't stored
- Flag fields that might be useful for planner analytics (especially miles data)

### Expected Deliverables:
- Complete field mapping document
- List of recommended fields to add to storage
- Any data quality observations (nulls, inconsistencies, etc.)
- **Specific attention to:** Identify the exact field name for "assessment last modified date" - this is critical for determining if planner has synced their device and data is fresh

### Technical Context:
- API Endpoint format: `{base_url}/DDOProtocol/{PROTOCOL_NAME}`
- Focus only on active circuits (status filter)
- Reference existing sync code in the codebase
- Only query for or collect data from circuits where the domain of the username is ASPLUNDH
  - usernames look like this "ASPLUNDH\cnewcombe" 
  - usernames are the values of the taken by column
  - This should become an option to set within the sudo admin data management settings
  - add a TODO and with a ordered list on the @../../resources/views/livewire/data-management/index.blade.php file somewhere out of the way so it can be implemented later 

---

## Context

This is the first step in a larger refactoring effort. We need to understand what data is available before redesigning storage. The ultimate goal is to support planner analytics with accurate mileage tracking.

## Dependencies
- None (starting point)

## Success Criteria
- [ ] Complete API response documented
- [ ] All database columns mapped
- [ ] Gap analysis completed
- [ ] Recommendations for new fields identified
- [ ] User selects additional fields to store directly in DB circuits table

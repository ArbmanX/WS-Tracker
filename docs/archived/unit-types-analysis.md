# PlannedUnits Unit Types Analysis

## Current Data Sample Analysis

Based on the PlannedUnits.json sample (5 records), I found these unit types:

### Confirmed Unit Types from Sample Data

| Unit Code | Description | Count | Length (ft) | Acres | Trees |
|-----------|-------------|-------|-------------|-------|-------|
| SPM | Single Phase - Manual | 2 | 97.17 | 0 | 0 |
| HCB | Hand Cut Brush | 2 | 0 | 0 | 0 |
| HERBNA | Herbicide - Non Aquatic | 1 | 0 | 0.22 | 0 |

### Dimension Analysis

From the sample, different unit types measure different things:

- **SPM (Single Phase - Manual)**: Uses `linear_feet` (97.17 ft total)
- **HCB (Hand Cut Brush)**: No measurements in sample (may use sqft or acres)
- **HERBNA (Herbicide - Non Aquatic)**: Uses `acres` (0.22 acres)

All samples showed `num_trees = 0`, suggesting tree removal units are separate.

---

## Unit Types Needing Verification

Based on common utility vegetation management practices, here are unit types that likely exist but weren't in the sample:

### Manual Trim Units
- **TPM** - Three Phase Manual (mentioned in ARCHITECTURE.md)
- **SPM** - Single Phase Manual (confirmed)
- **SPAD** - Single Phase Aerial Device (likely)
- **TPAD** - Three Phase Aerial Device (likely)

### Brush/Herbicide Units
- **HCB** - Hand Cut Brush (confirmed)
- **HERBNA** - Herbicide Non-Aquatic (confirmed)
- **HERBAQ** - Herbicide Aquatic (likely)
- **BRUSH** - Brush (generic, likely)
- **MECH** - Mechanical Brush Removal (likely)

### Tree Removal Units
- **TREERM** - Tree Removal (likely, would use `num_trees` field)
- **REMOVAL** - Removal (generic, likely)

### Specialized Units
- **DANGER** - Danger Tree (high priority removals)
- **CLEARANCE** - Clearance restoration
- **ACCESS** - Access/Right-of-way restoration
- **STUMP** - Stump removal
- **CHIP** - Chipping/cleanup

---

## Questions for User

I need your input to build the correct database schema:

1. **Complete Unit Type List**: Can you provide a complete list of all unit codes and descriptions that exist in your WorkStudio system?

2. **Measurement Fields**: For each unit type, which measurement applies?
   - Linear feet (`JOBVEGETATIONUNITS_LENGTHWRK`)
   - Acres (`JOBVEGETATIONUNITS_ACRES`)
   - Tree count (`JOBVEGETATIONUNITS_NUMTREES`)
   - Square feet (if any)
   - DBH/diameter (if tracked separately)

3. **Unit Type Groupings**: Do you categorize units into groups like:
   - Trim (manual/aerial)
   - Brush removal
   - Tree removal
   - Herbicide
   - Other

4. **Permission Requirements**: Do all unit types require permissions, or are some automatically approved?

5. **Cost Method**: I see a `VEGUNIT_COSTMETHOD` field (value "UC" in sample). What does this mean and does it relate to unit types?

---

## Proposed Schema Updates

Once we have the complete list, I'll update:

### Option 1: Normalized Columns (Current Proposal)
```sql
CREATE TABLE planned_units_circuit_aggregates (
    -- Common unit types as columns
    spm_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    hcb_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    herbna_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    tpm_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Add more as needed...
    other_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    -- Flexible JSON for complete breakdown
    all_unit_types JSON NULL,  -- {"SPM": 10, "HCB": 5, "RARE_TYPE": 1}
);
```

### Option 2: Fully Dynamic (If unit types change frequently)
```sql
CREATE TABLE unit_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,  -- SPM, HCB, etc.
    description VARCHAR(255) NOT NULL,
    category ENUM('trim', 'brush', 'removal', 'herbicide', 'other'),
    measurement_type ENUM('linear_feet', 'acres', 'trees', 'sqft'),
    requires_permission BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed with actual unit types
INSERT INTO unit_types (code, description, category, measurement_type) VALUES
('SPM', 'Single Phase - Manual', 'trim', 'linear_feet'),
('HCB', 'Hand Cut Brush', 'brush', 'acres'),
('HERBNA', 'Herbicide - Non Aquatic', 'herbicide', 'acres'),
-- ... rest of types
;
```

---

## Removal Categories

From the sample data:
- **Maintained**: 3 records (60%)
- **Non-Maintained**: 2 records (40%)

Are there other removal category values we should account for?

---

## Permission Statuses

Sample shows only "Approved". Based on the architecture doc, I expect:
- Blank or NULL (Pending)
- Approved
- Refused/Denied
- Not Surveyed
- Pending (explicit)

Do you have other status values like:
- Partial Approval
- Conditional
- Canceled
- Withdrawn

---

## Next Steps

Please provide:

1. **A complete list of unit codes and descriptions** from your WorkStudio system
2. **Which measurement field(s) each unit type uses**
3. **Any categories or groupings** you use for reporting
4. **Complete list of permission status values**
5. **Complete list of removal category values**

Once I have this information, I'll:
1. Update the aggregation strategy document
2. Design the optimal schema (normalized vs dynamic)
3. Write the correct SQL aggregation queries
4. Build the transformation logic

Would you like me to help you extract this information from:
- A larger API response export?
- Your WorkStudio database schema documentation?
- A configuration file or reference document?

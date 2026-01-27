<?php

namespace App\Services\WorkStudio\Queries;

class CircuitWithDailyRecordsQuery
{
    // =========================================================================
    // SQL Fragment Helpers
    // =========================================================================

    /**
     * Parse Microsoft JSON date format to SQL DATE.
     * Converts '/Date(1234567890000)/' to a proper DATE.
     */
    private static function parseMsDateToDate(string $column): string
    {
        return "CAST(CAST(REPLACE(REPLACE({$column}, '/Date(', ''), ')/', '') AS DATETIME) AS DATE)";
    }

    /**
     * Extract YEAR from date format: /Date(Jan 1, 2026)/ or similar
     * Used for Scope_Year calculation.
     */
    private static function extractYearFromMsDate(string $column): string
    {
        return "CASE
            WHEN {$column} IS NULL OR {$column} = '' THEN NULL
            ELSE YEAR(CAST(REPLACE(REPLACE({$column}, '/Date(', ''), ')/', '') AS DATE))
        END";
    }

    /**
     * Filter condition for valid units (excludes NW, empty, and null).
     */
    private static function validUnitFilter(string $tableAlias = 'VEGUNIT'): string
    {
        return "{$tableAlias}.UNIT != 'NW' AND {$tableAlias}.UNIT != '' AND {$tableAlias}.UNIT IS NOT NULL";
    }

    /**
     * Filter condition using NOT IN syntax.
     */
    private static function validUnitFilterNotIn(string $tableAlias = 'V'): string
    {
        return "{$tableAlias}.UNIT NOT IN ('NW', '') AND {$tableAlias}.UNIT IS NOT NULL";
    }

    /**
     * Get the first forester for a circuit.
     */
    private static function foresterSubquery(string $jobGuidRef = 'WSREQSS.JOBGUID'): string
    {
        return "(SELECT TOP 1 VEGUNIT.FORESTER
            FROM VEGUNIT
            WHERE VEGUNIT.JOBGUID = {$jobGuidRef}
              AND VEGUNIT.FORESTER IS NOT NULL
              AND VEGUNIT.FORESTER != '')";
    }

    /**
     * Get total footage for a circuit (sum of all station span lengths).
     */
    private static function totalFootageSubquery(string $jobGuidRef = 'WSREQSS.JOBGUID'): string
    {
        return "(SELECT CAST(SUM(SPANLGTH) AS DECIMAL(10,2)) FROM STATIONS WHERE STATIONS.JOBGUID = {$jobGuidRef})";
    }

    /**
     * Format datetime to Eastern time with readable format.
     */
    private static function formatToEasternTime(string $column): string
    {
        return "FORMAT(
            CAST(CAST({$column} AS DATETIME) AT TIME ZONE 'Eastern Standard Time' AS DATETIME),
            'MM/dd/yyyy h:mm tt'
        )";
    }

    /**
     * Build a unit count subquery for a specific permission status.
     */
    private static function unitCountSubquery(string $jobGuidRef, ?string $permStatus = null, bool $requireAssessedDate = false): string
    {
        $validUnit = self::validUnitFilter();

        $conditions = ["VEGUNIT.JOBGUID = {$jobGuidRef}", $validUnit];

        if ($requireAssessedDate) {
            $conditions[] = "VEGUNIT.ASSDDATE IS NOT NULL AND VEGUNIT.ASSDDATE != ''";
        }

        if ($permStatus === 'Pending') {
            $conditions[] = "(VEGUNIT.PERMSTAT = 'Pending' OR VEGUNIT.PERMSTAT = '' OR VEGUNIT.PERMSTAT IS NULL)";
        } elseif ($permStatus !== null) {
            $conditions[] = "VEGUNIT.PERMSTAT = '{$permStatus}'";
        }

        $where = implode("\n                AND ", $conditions);

        return "(SELECT COUNT(*) FROM VEGUNIT WHERE {$where})";
    }

    /**
     * Build the CROSS APPLY for unit counts (more efficient for list queries).
     */
    private static function unitCountsCrossApply(string $jobGuidRef = 'WSREQSS.JOBGUID'): string
    {
        $validUnit = self::validUnitFilter();

        return "CROSS APPLY (
            SELECT
                COUNT(CASE WHEN VEGUNIT.ASSDDATE IS NOT NULL AND VEGUNIT.ASSDDATE != ''
                        AND {$validUnit}
                    THEN 1 END) AS Total_Units_Planned,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Approved'
                        AND {$validUnit}
                    THEN 1 END) AS Total_Approvals,
                COUNT(CASE WHEN (VEGUNIT.PERMSTAT = '' OR VEGUNIT.PERMSTAT IS NULL)
                        AND {$validUnit}
                    THEN 1 END) AS Total_Pending,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'No Contact'
                        AND {$validUnit}
                    THEN 1 END) AS Total_No_Contacts,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Refusal'
                        AND {$validUnit}
                    THEN 1 END) AS Total_Refusals,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Deferred'
                        AND {$validUnit}
                    THEN 1 END) AS Total_Deferred,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'PPL Approved'
                        AND {$validUnit}
                    THEN 1 END) AS Total_PPL_Approved
            FROM VEGUNIT
            WHERE VEGUNIT.JOBGUID = {$jobGuidRef}
        ) AS UnitCounts";
    }

    /**
     * Build the Daily Records subquery/OUTER APPLY.
     * Groups by assessment date with distinct station footage calculation.
     *
     * Each station's SPANLGTH is only counted ONCE - on its first assessment date.
     * Uses ROW_NUMBER() to identify each station's first appearance.
     */
    private static function dailyRecordsQuery(string $jobGuidRef = 'WSREQSS.JOBGUID', bool $asOuterApply = true): string
    {
        $parseDateV = self::parseMsDateToDate('V.ASSDDATE');
        $parseDateV2 = self::parseMsDateToDate('V2.ASSDDATE');
        $validUnitV2 = self::validUnitFilterNotIn('V2');

        // Build the inner SELECT for daily records
        // Uses nested derived tables to:
        // 1. Get distinct (STATNAME, SPANLGTH, Date) combinations
        // 2. Assign ROW_NUMBER per station ordered by date
        // 3. Only sum where rn=1 (first occurrence of each station)
        $innerSelect = "SELECT
                    StationFirstDate.Assessed_Date,
                    CAST(SUM(StationFirstDate.SPANLGTH) / 1609.34 AS DECIMAL(10,4)) AS Total_Day_Miles,
                    (
                        SELECT COUNT(*)
                        FROM VEGUNIT V3
                        WHERE V3.JOBGUID = {$jobGuidRef}
                            AND V3.UNIT IS NOT NULL AND V3.UNIT != '' AND V3.UNIT != 'NW'
                            AND V3.ASSDDATE IS NOT NULL AND V3.ASSDDATE != ''
                            AND ".self::parseMsDateToDate('V3.ASSDDATE')." = StationFirstDate.Assessed_Date
                    ) AS Total_Unit_Count,
                    (
                        SELECT STRING_AGG(UNIT, ', ')
                        FROM (
                            SELECT DISTINCT V2.UNIT
                            FROM VEGUNIT V2
                            WHERE V2.JOBGUID = {$jobGuidRef}
                                AND {$validUnitV2}
                                AND {$parseDateV2} = StationFirstDate.Assessed_Date
                        ) AS UniqueUnits
                    ) AS Unit_List
                FROM (
                    SELECT
                        STATNAME,
                        SPANLGTH,
                        Assessed_Date,
                        ROW_NUMBER() OVER (PARTITION BY STATNAME ORDER BY Assessed_Date ASC) AS rn
                    FROM (
                        SELECT DISTINCT
                            S.STATNAME,
                            S.SPANLGTH,
                            {$parseDateV} AS Assessed_Date
                        FROM STATIONS S
                        INNER JOIN VEGUNIT V ON S.WO = V.WO AND S.STATNAME = V.STATNAME
                        WHERE S.JOBGUID = {$jobGuidRef}
                            AND S.SPANLGTH IS NOT NULL
                            AND S.SPANLGTH != ''
                            AND V.UNIT IS NOT NULL
                            AND V.UNIT != ''
                            AND V.ASSDDATE IS NOT NULL
                            AND V.ASSDDATE != ''
                    ) AS DistinctStationDates
                ) AS StationFirstDate
                WHERE StationFirstDate.rn = 1
                GROUP BY StationFirstDate.Assessed_Date
                FOR JSON PATH";

        if ($asOuterApply) {
            return "OUTER APPLY (
            SELECT ({$innerSelect}) AS Daily_Records
        ) AS DailyData";
        }

        return "({$innerSelect})";
    }

    /**
     * Build the Stations with nested Units subquery.
     */
    private static function stationsWithUnitsQuery(string $jobGuidRef = 'WSREQSS.JOBGUID'): string
    {
        $stationFields = SqlFieldBuilder::select('Stations', 'S');
        $vegunitFields = SqlFieldBuilder::select('VEGUNIT', 'U');

        return "(
            SELECT
                {$stationFields},
                (
                    SELECT {$vegunitFields}
                    FROM VEGUNIT U
                    WHERE U.WO = S.WO
                        AND U.STATNAME = S.STATNAME
                        AND U.UNIT IS NOT NULL
                        AND U.UNIT != ''
                        AND U.UNIT != 'NW'
                    FOR JSON PATH
                ) AS Units
            FROM STATIONS S
            WHERE S.JOBGUID = {$jobGuidRef}
            FOR JSON PATH
        )";
    }

    // =========================================================================
    // Public Query Methods
    // =========================================================================

    /**
     * Get the SQL query for fetching circuits with unit counts and daily records.
     * Returns JSON formatted data with nested Daily_Records array.
     *
     * @param  string  $username  The planner's username (e.g., 'ASPLUNDH\\cnewcombe')
     * @param  string  $contractor  The contractor name (e.g., 'Asplundh')
     */
    public static function getForPlannerMonitoring(string $username, string $contractor = 'Asplundh'): string
    {
        $escapedUsername = $username;

        // Build reusable fragments
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');
        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $unitCountsCrossApply = self::unitCountsCrossApply();
        $dailyRecordsOuterApply = self::dailyRecordsQuery();

        return "SELECT
            -- Circuit Info
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,
            WPStartDate_Assessment_Xrefs.WP_STARTDATE AS Scope_Year_Raw,
            {$scopeYear} AS Scope_Year,
            {$forester} AS Forester,
            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,
            CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
            CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,
            VEGJOB.CONTRACTOR AS Contractor,
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            {$lastSync} AS Last_Sync,
            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments,

            -- Unit counts from CROSS APPLY
            UnitCounts.Total_Units_Planned,
            UnitCounts.Total_Approvals,
            UnitCounts.Total_Pending,
            UnitCounts.Total_No_Contacts,
            UnitCounts.Total_Refusals,
            UnitCounts.Total_Deferred,
            UnitCounts.Total_PPL_Approved,

            -- Daily Records from OUTER APPLY
            DailyData.Daily_Records

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

        -- Single aggregation for all unit counts per circuit
        {$unitCountsCrossApply}

        -- Daily Records as nested JSON
        {$dailyRecordsOuterApply}

        WHERE VEGJOB.REGION IN (
            SELECT RESOURCEPERSON.GROUPDESC
            FROM RESOURCEPERSON
            WHERE RESOURCEPERSON.GROUPTYPE = 'GROUP'
            AND RESOURCEPERSON.USERNAME = '{$escapedUsername}'
        )

        AND WSREQSS.STATUS = 'ACTIV'
        AND VEGJOB.CONTRACTOR = '{$contractor}'
        AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
        AND WSREQSS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
        AND VEGJOB.CYCLETYPE NOT IN ('Reactive')

        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC
        FOR JSON PATH";
    }

    /**
     * Get the SQL query for a single circuit by JOBGUID.
     * Includes nested Stations array with Units sub-array.
     *
     * @param  string  $jobGuid  The circuit's JOBGUID
     */
    public static function getByJobGuid(string $jobGuid): string
    {
        // Build reusable fragments
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');
        $forester = self::foresterSubquery();
        $totalFootage = self::totalFootageSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $dailyRecords = self::dailyRecordsQuery('WSREQSS.JOBGUID', false);
        $stationsWithUnits = self::stationsWithUnitsQuery();

        // Unit count subqueries
        $totalUnitsPlanned = self::unitCountSubquery('WSREQSS.JOBGUID', null, true);
        $totalApprovals = self::unitCountSubquery('WSREQSS.JOBGUID', 'Approved');
        $totalPending = self::unitCountSubquery('WSREQSS.JOBGUID', 'Pending');
        $totalNoContacts = self::unitCountSubquery('WSREQSS.JOBGUID', 'No Contact');
        $totalRefusals = self::unitCountSubquery('WSREQSS.JOBGUID', 'Refusal');
        $totalDeferred = self::unitCountSubquery('WSREQSS.JOBGUID', 'Deferred');
        $totalPplApproved = self::unitCountSubquery('WSREQSS.JOBGUID', 'PPL Approved');

        return "SELECT
            -- Circuit Info
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,
            {$scopeYear} AS Scope_Year,
            {$forester} AS Forester,
            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,
            {$totalFootage} AS Total_Footage,
            CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
            CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,
            VEGJOB.CONTRACTOR AS Contractor,
            WSREQSS.TAKENBY AS Current_Owner,
            {$lastSync} AS Last_Sync,

            -- Unit Counts
            {$totalUnitsPlanned} AS Total_Units_Planned,
            {$totalApprovals} AS Total_Approvals,
            {$totalPending} AS Total_Pending,
            {$totalNoContacts} AS Total_No_Contacts,
            {$totalRefusals} AS Total_Refusals,
            {$totalDeferred} AS Total_Deferred,
            {$totalPplApproved} AS Total_PPL_Approved,

            -- Daily Records
            {$dailyRecords} AS Daily_Records,

            -- Stations with nested Units array
            {$stationsWithUnits} AS Stations

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
        WHERE WSREQSS.JOBGUID = '{$jobGuid}'
        FOR JSON PATH, WITHOUT_ARRAY_WRAPPER";
    }
}

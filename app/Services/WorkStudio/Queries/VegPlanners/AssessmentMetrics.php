<?php

namespace App\Services\WorkStudio\Queries\VegPlanners;

use App\WSHelpers;
use App\Services\WorkStudio\ResourceGroupAccessService;

class AssessmentMetrics
{
    /* Parameters 
    |  - full_scope
    |  - active_owned
    |  - username
    |  - active
    |  - qc
    |  - rewrk
    |  - closed
    */

    use SqlFragmentHelpers;

    /* =========================================================================
    * Base Data For Assessments
    * =========================================================================
    */

    public static function getBaseDataForEnitireScopeYearAssessments(): string
    {
        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.jobTypes'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
        $statues = WSHelpers::toSqlInClause(config('ws_assessment_query.statuses.planner_concern'));

        return "SELECT
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,

            {$scopeYear} AS Scope_Year,

            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.LENGTHCOMP AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            {$forester} AS Forester,
                
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            {$lastSync} AS Last_Sync,

            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WSREQSS.STATUS IN ({$statues})
        AND VEGJOB.CONTRACTOR IN ({$contractors})
        AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%2026%' 
        AND WSREQSS.JOBTYPE IN ({$jobTypes})
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    public static function getBaseDataForActiveAndOwnedAssessments(): string
    {
        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.jobTypes'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        return "SELECT
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,

            {$scopeYear} AS Scope_Year,

            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.LENGTHCOMP AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            VEGJOB.CONTRACTOR AS Contractor,
            {$forester} AS Forester,
                
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            {$lastSync} AS Last_Sync,

            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WSREQSS.STATUS = 'ACTIV'
        AND VEGJOB.CONTRACTOR = '{$contractors}'
        AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
        AND WSREQSS.JOBTYPE IN ({$jobTypes})
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }


    public static function getBaseDataForActiveAssessmentsByUsername(string $username): string
    {
        // $escapedUsername = str_replace('\\', '\\', $username);
        $escapedUsername = $username;

        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $resourceGroupsServ = app(ResourceGroupAccessService::class);

        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');

        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        return "SELECT
               WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,

            {$scopeYear} AS Scope_Year,

            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.LENGTHCOMP AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            VEGJOB.CONTRACTOR AS Contractor,
            {$forester} AS Forester,
                
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            {$lastSync} AS Last_Sync,

            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WSREQSS.STATUS = 'ACTIV'
        AND WSREQSS.TAKENBY LIKE '{$escapedUsername}'
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }


    /* =========================================================================
    * Job GUIDs For Assessments
    * =========================================================================
    */

    // TODO
    /*  SS.Taken by should come from database, and or config file
    *  (This should be stored to the data base and used to keep track of.
    *  snapshots, hashes, user defined edits, etc will be associated with the Job GUID.
    *  SO the Job Guid will get/tell you everything about this job until it is closed out. ).
    *    SS,STATUS - ACTIVE | SS.TAKENBY "ASPLUNDH" |  
    */
    public static function getAllJobGUIDsForEntireScopeYear(): string
    {
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.jobTypes'));
        $statues = WSHelpers::toSqlInClause(config('ws_assessment_query.statuses.planner_concern'));

        return "SELECT 
                    {$scopeYear} AS Scope_Year,

                    {$lastSync} AS Last_Sync,

                    SS.JOBGUID AS JOB_GUID,

                    SS.TAKENBY AS CURRENT_OWNER

                    FROM SS
                    LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
                    WHERE SS.STATUS IN ({$statues})
                    AND SS.TAKEN = 'true'
                    AND SS.TAKENBY IN 'ASPLUNDH\\%'
                    AND SS.TAKENBY NOT IN ({$excludedUsers})
                    AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%2026%'
                    AND SS.JOBTYPE IN ({$jobTypes})
                ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    public static function getAllJobGUIDsForActiveAndOwnedAssessments(): string
    {
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.jobTypes'));

        return "SELECT 
                    {$scopeYear} AS Scope_Year,

                    {$lastSync} AS Last_Sync,

                    SS.JOBGUID AS JOB_GUID,

                    SS.TAKENBY AS CURRENT_OWNER
                    FROM SS
                    LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
                    WHERE SS.STATUS = 'ACTIV' 
                    AND SS.TAKEN = 'true'
                    AND SS.TAKENBY LIKE 'ASPLUNDH\\%'
                    AND SS.TAKENBY NOT IN ({$excludedUsers})
                    AND SS.JOBTYPE IN ({$jobTypes})
                ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    public static function getAllJobGUIDsForActiveAssessmentsByUsername(string $username): string
    {
        // $escapedUsername = str_replace('\\', '\\', $username);
        $escapedUsername = $username;

        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        return "SELECT 
                    {$scopeYear} AS Scope_Year,
                    WPStartDate_Assessment_Xrefs.WP_STARTDATE AS Scope_Year_Raw,

                    {$lastSync} AS Last_Sync,
                    SS.EDITDATE AS RAW_LAST_SYNC,

                    SS.JOBGUID AS JOB_GUID,

                    SS.TAKENBY AS CURRENT_OWNER

                    FROM SS
                    LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
                    WHERE SS.STATUS = 'ACTIV' 
                    AND SS.TAKEN = 'true'
                    AND SS.TAKENBY LIKE '{$escapedUsername}'
                ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }


    /* =========================================================================
    * Units For Assessments
    * =========================================================================
    */
    public static function getAllUnitsForAssessmentByJobGUID(?string $jobguid = null): ?string
    {

        $totalFootage = self::totalFootageSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $stationsWithUnits = self::stationsWithUnitsQuery();

        return "SELECT
            -- Circuit Info
            WSREQSS.WO AS Work_Order,
            {$totalFootage} AS Total_Footage,
            CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
            CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,
            WSREQSS.TAKENBY AS Current_Owner,
            {$lastSync} AS Last_Sync,

            -- Stations with nested Units array
            {$stationsWithUnits} AS Stations

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        WHERE WSREQSS.JOBGUID = '{$jobguid}'
        FOR JSON PATH, WITHOUT_ARRAY_WRAPPER";
    }

    public static function getAllUnitsForActiveAndOwnedAssessments(): string
    {
        return "";
    }

    public static function getAllUnitsForActiveAssessmentsByUsername(string $username): ?string
    {
        return "";
    }

    /* =========================================================================
    * Performance Metrics For Assessments
    * =========================================================================
    */
    public static function getForPlannerMonitoring(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.jobTypes'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
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
            WSREQSS.TAKEN AS Taken,
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

        -- Single aggregation for all unit counts per circuit
        {$unitCountsCrossApply}

        -- Daily Records as nested JSON
        {$dailyRecordsOuterApply}

        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WSREQSS.STATUS = 'ACTIV'
        AND VEGJOB.CONTRACTOR IN ({$contractors})
        AND WSREQSS.TAKEN IS true
        AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
        AND WSREQSS.JOBTYPE IN ({$jobTypes})

        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC
        FOR JSON PATH";
    }
}

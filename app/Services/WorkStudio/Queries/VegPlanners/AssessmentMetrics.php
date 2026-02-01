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

      public static function test(): string
    {
         $resourceGroupsServ = app(ResourceGroupAccessService::class);

        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');

        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);
       

        return "SELECT
           
            *

        FROM JOBVEGETATIONUNIT
        
            WHERE JOBVEGETATIONUNITS.JOBGUID = ''";
    }


    // public static function test2(): string
    // {
    //     $resourceGroupsServ = app(ResourceGroupAccessService::class);
    //     $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
    //     $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

    //     $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
    //     $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
    //     $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');
    //     $forester = self::foresterSubquery();
    //     $totalFootage = self::totalFootageSubquery();
    //     $lastSync = self::formatToEasternTime('SS.EDITDATE');
    //     // $stationsWithUnits = self::stationsWithUnitsQuery();
    //     $unitCountsCrossApply = self::unitCountsCrossApply();
    //     // $dailyRecordsOuterApply = self::dailyRecordsQuery();

    //     return "SELECT
    //         -- test 2
    //         WSREQSS.JOBGUID AS Job_ID,
    //         VEGJOB.LINENAME AS Line_Name,
    //         WSREQSS.WO AS Work_Order,
    //         WSREQSS.EXT AS Extension,
    //         WSREQSS.STATUS AS Status,
    //         WSREQSS.TAKEN AS Taken,
    //         WPStartDate_Assessment_Xrefs.WP_STARTDATE AS Scope_Year_Raw,
    //         {$scopeYear} AS Scope_Year,
    //         {$forester} AS Forester,
    //         VEGJOB.OPCO AS Utility,
    //         VEGJOB.REGION AS Region,
    //         VEGJOB.SERVCOMP AS Department,
    //         WSREQSS.JOBTYPE AS Job_Type,
    //         VEGJOB.CYCLETYPE AS Cycle_Type,
    //         {$totalFootage} AS Total_Footage,
    //         CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
    //         CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
    //         VEGJOB.PRCENT AS Percent_Complete,
    //         VEGJOB.CONTRACTOR AS Contractor,
    //         WSREQSS.TAKENBY AS Current_Owner,
    //         SS.MODIFIEDBY AS Last_Modified_By,
    //         {$lastSync} AS Last_Sync,
    //         WSREQSS.ASSIGNEDTO AS Assigned_To,
    //         VEGJOB.COSTMETHOD AS Cost_Method,
    //         VEGJOB.CIRCCOMNTS AS Circuit_Comments,

    //         -- Unit counts from CROSS APPLY
    //         UnitCounts.Total_Units_Planned,
    //         UnitCounts.Total_Approvals,
    //         UnitCounts.Total_Pending,
    //         UnitCounts.Total_No_Contacts,
    //         UnitCounts.Total_Refusals,
    //         UnitCounts.Total_Deferred,
    //         UnitCounts.Total_PPL_Approved

    //         -- Daily Records from OUTER APPLY

    //     FROM SS
    //     INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
    //     INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
    //     LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

    //     -- Single aggregation for all unit counts per circuit
    //     {$unitCountsCrossApply}

    //     -- Daily Records as nested JSON

    //     WHERE VEGJOB.REGION IN ({$resourceGroups})

    //     AND WSREQSS.STATUS = 'ACTIV'
    //     AND VEGJOB.CONTRACTOR IN ({$contractors})
    //     AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
    //     AND WSREQSS.JOBTYPE IN ({$jobTypes})
    //     AND VEGJOB.CYCLETYPE NOT IN ('Reactive')

    //     ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC
    //     FOR JSON PATH";
    // }
    // public static function test1(): string
    // {

    //     $resourceGroupsServ = app(ResourceGroupAccessService::class);
    //     $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
    //     $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

    //     $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
    //     $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
    //     $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');
    //     $forester = self::foresterSubquery();
    //     $totalFootage = self::totalFootageSubquery();
    //     $lastSync = self::formatToEasternTime('SS.EDITDATE');
    //     // $dailyRecords = self::dailyRecordsQuery('WSREQSS.JOBGUID', false);
    //     // $stationsWithUnits = self::stationsWithUnitsQuery();

    //     // Unit count subqueries
    //     $totalUnitsPlanned = self::unitCountSubquery('SS.JOBGUID', null, true);
    //     $totalApprovals = self::unitCountSubquery('SS.JOBGUID', 'Approved');
    //     $totalPending = self::unitCountSubquery('SS.JOBGUID', 'Pending');
    //     $totalNoContacts = self::unitCountSubquery('SS.JOBGUID', 'No Contact');
    //     $totalRefusals = self::unitCountSubquery('SS.JOBGUID', 'Refusal');
    //     $totalDeferred = self::unitCountSubquery('SS.JOBGUID', 'Deferred');
    //     $totalPplApproved = self::unitCountSubquery('SS.JOBGUID', 'PPL Approved');

    //     return "SELECT
    //         -- Circuit Info
    //         SS.JOBGUID AS Job_ID,
    //         VEGJOB.LINENAME AS Line_Name,
    //         SS.WO AS Work_Order,
    //         SS.EXT AS Extension,
    //         SS.STATUS AS Status,
    //         SS.TAKEN AS Taken,
    //         WPStartDate_Assessment_Xrefs.WP_STARTDATE AS Scope_Year_Raw,
    //         {$scopeYear} AS Scope_Year,
    //         {$forester} AS Forester,
    //         VEGJOB.OPCO AS Utility,
    //         VEGJOB.REGION AS Region,
    //         VEGJOB.SERVCOMP AS Department,
    //         SS.JOBTYPE AS Job_Type,
    //         VEGJOB.CYCLETYPE AS Cycle_Type,
    //         {$totalFootage} AS Total_Footage,
    //         CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
    //         CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
    //         VEGJOB.PRCENT AS Percent_Complete,
    //         VEGJOB.CONTRACTOR AS Contractor,
    //         SS.TAKENBY AS Current_Owner,
    //         SS.MODIFIEDBY AS Last_Modified_By,
    //         {$lastSync} AS Last_Sync,
    //         SS.ASSIGNEDTO AS Assigned_To,
    //         VEGJOB.COSTMETHOD AS Cost_Method,
    //         VEGJOB.CIRCCOMNTS AS Circuit_Comments,

    //         -- Unit Counts
    //         {$totalUnitsPlanned} AS Total_Units_Planned,
    //         {$totalApprovals} AS Total_Approvals,
    //         {$totalPending} AS Total_Pending,
    //         {$totalNoContacts} AS Total_No_Contacts,
    //         {$totalRefusals} AS Total_Refusals,
    //         {$totalDeferred} AS Total_Deferred,
    //         {$totalPplApproved} AS Total_PPL_Approved


    //     FROM SS
    //     INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
    //     LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

    //     WHERE VEGJOB.REGION IN ({$resourceGroups})

    //     AND SS.STATUS = 'ACTIV'
    //     AND VEGJOB.CONTRACTOR IN ({$contractors})
    //     AND SS.TAKENBY IS NOT NULL AND SS.TAKENBY != '' AND SS.TAKENBY LIKE 'ASPLUNDH\\%'
    //     AND SS.JOBTYPE IN ({$jobTypes})
    //     AND VEGJOB.CYCLETYPE NOT IN ('Reactive')

    //     ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC
    //     FOR JSON PATH";
    // }

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
        $filterScopeYear = config('ws_assessment_query.scope_year');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $statues = WSHelpers::toSqlInClause(config('ws_assessment_query.statuses.planner_concern'));

        return "SELECT 
            -- getAllJobGUIDsForEntireScopeYear
                        {$scopeYear} AS Scope_Year,

                        {$lastSync} AS Last_Sync,

                        SS.JOBGUID AS JOB_GUID,

                        SS.TAKENBY AS CURRENT_OWNER

                        FROM SS
                        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
                        WHERE SS.STATUS IN ({$statues})
                        AND SS.TAKEN = 'true'
                        AND SS.TAKENBY IS NOT NULL AND SS.TAKENBY != '' AND SS.TAKENBY LIKE 'ASPLUNDH\\%'
                        AND SS.TAKENBY NOT IN ({$excludedUsers})
                        AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%'
                        AND SS.JOBTYPE IN ({$jobTypes})
                    ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Units For Assessments
    * =========================================================================
    */
    public static function getAllUnitsForAssessmentByJobGUID(?string $jobguid = null): ?string
    {
        //   -- need to find the approprate fields in the unit to get unique customers 
        //     -- get count of unique customers 
        //     -- persist customer with permstat only, unique ID and json timeline customer lifecycle 
        //     -- unique id in json to another table of json customer data, 
        //     --    --contact info, properties. customer notes, unit history, etc. 
        //     --    -- unique id needs to be explored to create an id out of the values if the fields related to customer
        //     --      -- Customer may have other properties and could be on other assessment so the data needs to be available across all areas of the app

        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $stationsWithUnits = self::stationsWithUnitsQuery();

        return "SELECT
                -- Circuit Info
                WSREQSS.WO AS Work_Order,
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

    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Performance Metrics Of Planners
    * =========================================================================
    */
    public static function getPlannerDailyActivity(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        $upContractors = str($contractors[0])->upper() . '\\%';
        // Build reusable fragments

        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $unitCountsCrossApply = self::unitCountsCrossApply();
        $dailyRecords = self::dailyRecordsQuery('WSREQSS.JOBGUID', false);


        return "SELECT
                
                CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
                CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
                VEGJOB.PRCENT AS Percent_Complete,
                WSREQSS.TAKENBY AS Current_Owner,
                {$lastSync} AS Last_Sync,

                {$dailyRecords} AS Daily_Records

              


       

            WHERE VEGJOB.REGION IN ({$resourceGroups})
            AND WSREQSS.STATUS = 'ACTIV'
            AND VEGJOB.CONTRACTOR IN ({$contractors})
            AND WSREQSS.TAKEN IS true
            AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE {$upContractors}
            AND WSREQSS.JOBTYPE IN ({$jobTypes})

            ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC
            FOR JSON PATH";
    }

    public static function getBaseDataForEntireScopeYearAssessments(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);
        $filterScopeYear = config('ws_assessment_query.scope_year');
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
        $statues = WSHelpers::toSqlInClause(config('ws_assessment_query.statuses.planner_concern'));

        // Build reusable fragments
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');
        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $dailyRecords = self::dailyRecordsQuery('WSREQSS.JOBGUID', false);

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
                {$dailyRecords} AS Daily_Records

            FROM SS
                INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
                INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
                LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
                WHERE VEGJOB.REGION IN ({$resourceGroups})
                AND WSREQSS.STATUS IN ({$statues})
                AND VEGJOB.CONTRACTOR IN ({$contractors})
                AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%' 
                AND WSREQSS.JOBTYPE IN ({$jobTypes})
                ORDER BY VEGJOB.REGION DESC, WSREQSS.STATUS ASC, WSREQSS.EDITDATE DESC, WSREQSS.WO DESC
            FOR JSON PATH";
    }

    //  -- Unit counts from CROSS APPLY
    // UnitCounts.Total_Units_Planned,
    // UnitCounts.Total_Approvals,
    // UnitCounts.Total_Pending,
    // UnitCounts.Total_No_Contacts,
    // UnitCounts.Total_Refusals,
    // UnitCounts.Total_Deferred,
    // UnitCounts.Total_PPL_Approved,
    // 
    // FROM SS
    // INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
    // INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
    // 
    // -- Single aggregation for all unit counts per circuit
    // {$unitCountsCrossApply}

    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Circuit Data
    * =========================================================================
    */


    /* =========================================================================
    * END
    * =========================================================================
    */

   
    

    /* =========================================================================
    * Base Data For Assessments
    * =========================================================================
    */

    // Create table to store this data and make the Job_ID the primary key 
    // Display data on the overview dashboard do a 2 by 2 grid of cards one per region. 
    //    -- display 
    //      total circuit count, total miles, total completed miles, remaining miles 
    //     USE ALL DAISY UI 
    public static function getBaseDataForScopeYearAssessments(): string
    {
        $forester = self::foresterSubquery();
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);

        $filterScopeYear = config('ws_assessment_query.scope_year');
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));
        $statues = WSHelpers::toSqlInClause(config('ws_assessment_query.statuses.planner_concern'));

        return "SELECT
            -- getBaseDataForEntireScopeYearAssessments
                -- Table = veg_assessment_base_data
                WSREQSS.JOBGUID AS Job_ID,
                VEGJOB.LINENAME AS Line_Name,
                WSREQSS.WO AS Work_Order,
                WSREQSS.EXT AS Extension,
                {$scopeYear} AS Scope_Year,
                {$forester} AS Forester,
                VEGJOB.OPCO AS Utility,
                VEGJOB.REGION AS Region,
                VEGJOB.SERVCOMP AS Department,
                WSREQSS.JOBTYPE AS Job_Type,
                VEGJOB.CYCLETYPE AS Cycle_Type,

                -- Table = veg_assessment_dynamic_base_data
                -- foreign key jobguid
                -- primary key 
                VEGJOB.LENGTH AS Total_Miles,
                VEGJOB.LENGTHCOMP AS Completed_Miles,
                VEGJOB.PRCENT AS Percent_Complete,
                WSREQSS.STATUS AS Status,
                WSREQSS.TAKEN AS Taken,
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
            AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%' 
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

        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        return "SELECT
            -- getBaseDataForActiveAndOwnedAssessments
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
            AND VEGJOB.CONTRACTOR = ({$contractors})
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
            -- getBaseDataForActiveAssessmentsByUsername
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




    public static function getAllJobGUIDsForActiveAndOwnedAssessments(): string
    {
        $lastSync = self::formatToEasternTime('SS.EDITDATE');
        $scopeYear = self::extractYearFromMsDate('WPStartDate_Assessment_Xrefs.WP_STARTDATE');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));

        return "SELECT 
            -- getAllJobGUIDsForActiveAndOwnedAssessments
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




    public static function getAllUnitsForActiveAndOwnedAssessments(): string
    {
        return "";
    }

    public static function getAllUnitsForActiveAssessmentsByUsername(string $username): ?string
    {
        return "";
    }
}

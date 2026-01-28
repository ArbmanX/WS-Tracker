<?php

namespace App\Services\WorkStudio\Queries\VegPlanners;

use App\WSHelpers;
use App\Services\WorkStudio\ResourceGroupAccessService;

class VegAssessmentQueries
{

    use SqlFragmentHelpers;

    public static function test(): string
    {

    // return "SELECT TOP 100 * FROM WORKPLANWO";
        // Explore JOBVEGETATIONUNITS - get distinct UNIT values with measurement totals
        // This helps map UNIT codes to work categories (removals, VPS, trimming, etc.)
        // return "SELECT
        //     UNIT,
        //     COUNT(*) AS Record_Count,
        //     SUM(NUMTREES) AS Total_Trees,
        //     SUM(ACRES) AS Total_Acres,
        //     SUM(LENGTHWRK) AS Total_Length
        // FROM JOBVEGETATIONUNITS
        // WHERE UNIT IS NOT NULL AND UNIT != ''
        // GROUP BY UNIT
        // ORDER BY Record_Count DESC";
    }
    /* =========================================================================
    * System Wide Data - This is broad counts and totals of data
    *  - count total circuits 
    *   - count in active
    *   - count in qc
    *   - count in rework
    *   - count in closed
    *  - count total circuits 
    *  - total miles 
    *  - total completed miles
    *  - total remaining miles
    *  - total active planners
    * =========================================================================
    */

    /*
    * where ss.status in ('ACTIV', 'QC', 'REWRK', 'CLOSED')
    * where scope year = config(workstudio.scope_year
    * really just want all counts from this query
    */
    public static function systemWideDataQuery(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);
        $filterScopeYear = config('ws_assessment_query.scope_year');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        return "SELECT
                -- Circuit Counts
                COUNT(*) AS Total_Circuits,
                SUM(CASE WHEN SS.STATUS = 'ACTIV' THEN 1 ELSE 0 END) AS Active_Count,
                SUM(CASE WHEN SS.STATUS = 'QC' THEN 1 ELSE 0 END) AS QC_Count,
                SUM(CASE WHEN SS.STATUS = 'REWRK' THEN 1 ELSE 0 END) AS Rework_Count,
                SUM(CASE WHEN SS.STATUS = 'CLOSE' THEN 1 ELSE 0 END) AS Closed_Count,

                -- Miles
                CAST(SUM(VEGJOB.LENGTH) AS DECIMAL(10,2)) AS Total_Miles,
                CAST(SUM(VEGJOB.LENGTHCOMP) AS DECIMAL(10,2)) AS Completed_Miles,

                -- Active Planners (unique TAKENBY usernames from assessments with status 'ACTIV' only)
                COUNT(DISTINCT CASE WHEN SS.STATUS = 'ACTIV' THEN SS.TAKENBY END) AS Active_Planners

            FROM SS
            INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
            LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

            WHERE VEGJOB.REGION IN ({$resourceGroups})
            AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%'
            AND VEGJOB.CYCLETYPE NOT IN ('Reactive')
            AND VEGJOB.CONTRACTOR IN ({$contractors})
            AND SS.STATUS IN ('ACTIV', 'QC', 'REWRK', 'CLOSE')
            AND SS.TAKENBY NOT IN ({$excludedUsers})
            AND SS.JOBTYPE IN ({$jobTypes})
            AND VEGJOB.CYCLETYPE NOT IN ('Reactive', 'Storm Follow Up', 'Misc. Project Work', 'PUC-STORM FOLLOW UP')";
    }


    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Regional Data - This is regional based data
    *    - same data as System Wide Data but per region DONE
    *    - total unit count 
    *    - total units pending 
    *    - total units approved 
    *    - total units no contact 
    *    - total units refused
    *    - total units defered
    *    - total units ppl approved 
    *    - total removals = 6-12 | count
    *    - total removal greater then 6-12 | count 
    *    - total vps | count 
    *    - total hand cut brush | acres
    *    - total herbicide | acres
    *    - total manaual trimmimg  | linear feet
    *    - total bucket trimmimg | linear feet
    * =========================================================================
    */
    public static function groupedByRegionDataQuery(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);
        $filterScopeYear = config('ws_assessment_query.scope_year');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        return "SELECT
            -- Region Identifier
            VEGJOB.REGION AS Region,

            -- Circuit Counts by Status
            COUNT(*) AS Total_Circuits,
            SUM(CASE WHEN SS.STATUS = 'ACTIV' THEN 1 ELSE 0 END) AS Active_Count,
            SUM(CASE WHEN SS.STATUS = 'QC' THEN 1 ELSE 0 END) AS QC_Count,
            SUM(CASE WHEN SS.STATUS = 'REWRK' THEN 1 ELSE 0 END) AS Rework_Count,
            SUM(CASE WHEN SS.STATUS = 'CLOSE' THEN 1 ELSE 0 END) AS Closed_Count,

            -- Miles
            CAST(SUM(VEGJOB.LENGTH) AS DECIMAL(10,2)) AS Total_Miles,
            CAST(SUM(VEGJOB.LENGTHCOMP) AS DECIMAL(10,2)) AS Completed_Miles,

            -- Active Planners (unique TAKENBY with ACTIV status)
            COUNT(DISTINCT CASE WHEN SS.STATUS = 'ACTIV' THEN SS.TAKENBY END) AS Active_Planners,

            -- Permission Counts (aggregated from CROSS APPLY)
            SUM(UnitData.Total_Units) AS Total_Units,
            SUM(UnitData.Approved_Count) AS Approved_Count,
            SUM(UnitData.Pending_Count) AS Pending_Count,
            SUM(UnitData.No_Contact_Count) AS No_Contact_Count,
            SUM(UnitData.Refusal_Count) AS Refusal_Count,
            SUM(UnitData.Deferred_Count) AS Deferred_Count,
            SUM(UnitData.PPL_Approved_Count) AS PPL_Approved_Count,

            -- Work Measurements (aggregated from CROSS APPLY)
            SUM(WorkData.Rem_6_12_Count) AS Rem_6_12_Count,
            SUM(WorkData.Rem_Over_12_Count) AS Rem_Over_12_Count,
            SUM(WorkData.Ash_Removal_Count) AS Ash_Removal_Count,
            SUM(WorkData.VPS_Count) AS VPS_Count,
            CAST(SUM(WorkData.Brush_Acres) AS DECIMAL(10,2)) AS Brush_Acres,
            CAST(SUM(WorkData.Herbicide_Acres) AS DECIMAL(10,2)) AS Herbicide_Acres,
            CAST(SUM(WorkData.Bucket_Trim_Length) AS DECIMAL(10,2)) AS Bucket_Trim_Length,
            CAST(SUM(WorkData.Manual_Trim_Length) AS DECIMAL(10,2)) AS Manual_Trim_Length

        FROM SS
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

        -- CROSS APPLY for VEGUNIT (permission counts per circuit)
        CROSS APPLY (
            SELECT
                COUNT(*) AS Total_Units,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Approved' THEN 1 END) AS Approved_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Pending' OR VEGUNIT.PERMSTAT IS NULL OR VEGUNIT.PERMSTAT = '' THEN 1 END) AS Pending_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'No Contact' THEN 1 END) AS No_Contact_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Refusal' THEN 1 END) AS Refusal_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Deferred' THEN 1 END) AS Deferred_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'PPL Approved' THEN 1 END) AS PPL_Approved_Count
            FROM VEGUNIT
            WHERE VEGUNIT.JOBGUID = SS.JOBGUID
              AND VEGUNIT.UNIT IS NOT NULL
              AND VEGUNIT.UNIT != ''
              AND VEGUNIT.UNIT != 'NW'
        ) AS UnitData

        -- CROSS APPLY for JOBVEGETATIONUNITS (work measurements per circuit)
        CROSS APPLY (
            SELECT
                COUNT(CASE WHEN UNIT = 'REM612' THEN 1 END) AS Rem_6_12_Count,
                COUNT(CASE WHEN UNIT IN ('REM1218', 'REM1824', 'REM2430', 'REM3036') THEN 1 END) AS Rem_Over_12_Count,
                COUNT(CASE WHEN UNIT IN ('ASH612', 'ASH1218', 'ASH1824', 'ASH2430', 'ASH3036') THEN 1 END) AS Ash_Removal_Count,
                COUNT(CASE WHEN UNIT = 'VPS' THEN 1 END) AS VPS_Count,
                SUM(CASE WHEN UNIT IN ('BRUSH', 'HCB', 'BRUSHTRIM') THEN ACRES ELSE 0 END) AS Brush_Acres,
                SUM(CASE WHEN UNIT IN ('HERBA', 'HERBNA') THEN ACRES ELSE 0 END) AS Herbicide_Acres,
                SUM(CASE WHEN UNIT IN ('SPB', 'MPB') THEN LENGTHWRK ELSE 0 END) AS Bucket_Trim_Length,
                SUM(CASE WHEN UNIT IN ('SPM', 'MPM') THEN LENGTHWRK ELSE 0 END) AS Manual_Trim_Length
            FROM JOBVEGETATIONUNITS
            WHERE JOBVEGETATIONUNITS.JOBGUID = SS.JOBGUID
        ) AS WorkData

        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%'
        AND SS.STATUS IN ('ACTIV', 'QC', 'REWRK', 'CLOSE')
        AND VEGJOB.CONTRACTOR IN ({$contractors})
        AND SS.TAKENBY NOT IN ({$excludedUsers})
        AND SS.JOBTYPE IN ({$jobTypes})
        AND VEGJOB.CYCLETYPE NOT IN ('Reactive', 'Storm Follow Up', 'Misc. Project Work', 'PUC-STORM FOLLOW UP')

        GROUP BY VEGJOB.REGION
        ORDER BY VEGJOB.REGION";
    }

    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Circuit Data - All data should be grouped by the JOBGUID
    *    - Planner / Planners, while going thru the units we must check the VEGUNIT.FORESTER field to determine all the planners because multipule planners can work on the same assessment and the VEGUNIT stores the name of the person who physically planned that specific unit 
    *    - date of first assessed unit    
    *    - date of last unit assessed    
    *    - date of last Sync Date   
    *    - same data as System Wide Data but per circuit
    *    - total unit count 
    *    - total units pending 
    *    - total units approved 
    *    - total units no contact 
    *    - total units refused
    *    - total units defered
    *    - total units ppl approved 
    *    - total count of occurance of unit | REM612, REM1218, REM1824, REM2430, REM3036, ASH612, ASH1218, ASH1824, ASH2430, ASH3036, VPS  
    *    - total acres of units | BRUSH, HCB (Hand Cut Brush), HERBA (Herbicide Aquatic), HERBNA (Herbicide Non-aqutic), BRUSHTRIM (Hand Cut Brush w/ Trim) 
    *    - total length of units | 'SPB', 'MPB', 'SPM', 'MPM'
    * =========================================================================
    */

    public static function groupedByCircuitDataQuery(): string
    {
        $resourceGroupsServ = app(ResourceGroupAccessService::class);
        $resGrpArr = $resourceGroupsServ->getRegionsForRole('planner');
        $resourceGroups = WSHelpers::toSqlInClause($resGrpArr);
        $filterScopeYear = config('ws_assessment_query.scope_year');

        $excludedUsers = WSHelpers::toSqlInClause(config('ws_assessment_query.excludedUsers'));
        $jobTypes = WSHelpers::toSqlInClause(config('ws_assessment_query.job_types'));
        $contractors = WSHelpers::toSqlInClause(config('ws_assessment_query.contractors'));

        $lastSync = self::formatToEasternTime('SS.EDITDATE');

        return "SELECT
            -- Circuit Identifiers
            SS.JOBGUID AS Job_GUID,
            SS.WO AS Work_Order,
            SS.EXT AS Extension,
            SS.STATUS AS Status,
            VEGJOB.LINENAME AS Line_Name,
            VEGJOB.REGION AS Region,
            VEGJOB.CYCLETYPE AS Cycle_Type,
            CAST(VEGJOB.LENGTH AS DECIMAL(10,2)) AS Total_Miles,
            CAST(VEGJOB.LENGTHCOMP AS DECIMAL(10,2)) AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,
            {$lastSync} AS Last_Sync,

            -- Planners (distinct foresters via subquery)
            (SELECT STRING_AGG(DF.FORESTER, ', ')
             FROM (SELECT DISTINCT VEGUNIT.FORESTER
                   FROM VEGUNIT
                   WHERE VEGUNIT.JOBGUID = SS.JOBGUID
                     AND VEGUNIT.FORESTER IS NOT NULL
                     AND VEGUNIT.FORESTER != '') AS DF) AS Planners,

            -- Phase 1: Permission Data (from VEGUNIT)
            UnitData.First_Assessed_Date,
            UnitData.Last_Assessed_Date,
            UnitData.Total_Units,
            UnitData.Approved_Count,
            UnitData.Pending_Count,
            UnitData.No_Contact_Count,
            UnitData.Refusal_Count,
            UnitData.Deferred_Count,
            UnitData.PPL_Approved_Count,

            -- Phase 2: Work Measurements (from JOBVEGETATIONUNITS)
            WorkData.Rem_6_12_Count,
            WorkData.Rem_Over_12_Count,
            WorkData.Ash_Removal_Count,
            WorkData.VPS_Count,
            WorkData.Brush_Acres,
            WorkData.Herbicide_Acres,
            WorkData.Bucket_Trim_Length,
            WorkData.Manual_Trim_Length

        FROM SS
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID

        -- Phase 1: CROSS APPLY for VEGUNIT (dates, permission counts)
        CROSS APPLY (
            SELECT
                MIN(VEGUNIT.ASSDDATE) AS First_Assessed_Date,
                MAX(VEGUNIT.ASSDDATE) AS Last_Assessed_Date,
                COUNT(*) AS Total_Units,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Approved' THEN 1 END) AS Approved_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Pending' OR VEGUNIT.PERMSTAT IS NULL OR VEGUNIT.PERMSTAT = '' THEN 1 END) AS Pending_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'No Contact' THEN 1 END) AS No_Contact_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Refusal' THEN 1 END) AS Refusal_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'Deferred' THEN 1 END) AS Deferred_Count,
                COUNT(CASE WHEN VEGUNIT.PERMSTAT = 'PPL Approved' THEN 1 END) AS PPL_Approved_Count
            FROM VEGUNIT
            WHERE VEGUNIT.JOBGUID = SS.JOBGUID
              AND VEGUNIT.UNIT IS NOT NULL
              AND VEGUNIT.UNIT != ''
              AND VEGUNIT.UNIT != 'NW'
        ) AS UnitData

        -- Phase 2: CROSS APPLY for JOBVEGETATIONUNITS (work measurements)
        CROSS APPLY (
            SELECT
                -- Removals 6-12 (separate)
                COUNT(CASE WHEN UNIT = 'REM612' THEN 1 END) AS Rem_6_12_Count,

                -- Removals > 12 (grouped)
                COUNT(CASE WHEN UNIT IN ('REM1218', 'REM1824', 'REM2430', 'REM3036') THEN 1 END) AS Rem_Over_12_Count,

                -- All Ash Removals (grouped)
                COUNT(CASE WHEN UNIT IN ('ASH612', 'ASH1218', 'ASH1824', 'ASH2430', 'ASH3036') THEN 1 END) AS Ash_Removal_Count,

                -- VPS count
                COUNT(CASE WHEN UNIT = 'VPS' THEN 1 END) AS VPS_Count,

                -- Brush acres (grouped)
                SUM(CASE WHEN UNIT IN ('BRUSH', 'HCB', 'BRUSHTRIM') THEN ACRES ELSE 0 END) AS Brush_Acres,

                -- Herbicide acres (grouped)
                SUM(CASE WHEN UNIT IN ('HERBA', 'HERBNA') THEN ACRES ELSE 0 END) AS Herbicide_Acres,

                -- Bucket trimming length (SPB, MPB)
                SUM(CASE WHEN UNIT IN ('SPB', 'MPB') THEN LENGTHWRK ELSE 0 END) AS Bucket_Trim_Length,

                -- Manual trimming length (SPM, MPM)
                SUM(CASE WHEN UNIT IN ('SPM', 'MPM') THEN LENGTHWRK ELSE 0 END) AS Manual_Trim_Length
            FROM JOBVEGETATIONUNITS
            WHERE JOBVEGETATIONUNITS.JOBGUID = SS.JOBGUID
        ) AS WorkData

        WHERE VEGJOB.REGION IN ({$resourceGroups})
        AND WPStartDate_Assessment_Xrefs.WP_STARTDATE LIKE '%{$filterScopeYear}%'
        AND SS.STATUS IN ('ACTIV', 'QC', 'REWRK', 'CLOSE')
        AND VEGJOB.CONTRACTOR IN ({$contractors})
        AND SS.TAKENBY NOT IN ({$excludedUsers})
        AND SS.JOBTYPE IN ({$jobTypes})
        AND VEGJOB.CYCLETYPE NOT IN ('Reactive', 'Storm Follow Up', 'Misc. Project Work', 'PUC-STORM FOLLOW UP')

        ORDER BY VEGJOB.REGION, SS.STATUS, SS.WO";
    }



    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Planner Data - This is measurements only
    *  There should be no permission relevant data collected here
    * =========================================================================
    */


    /* =========================================================================
    * END
    * =========================================================================
    */

    /* =========================================================================
    * Permission Data For Planner Dashboard
    * =========================================================================
    */


    /* =========================================================================
    * END
    * =========================================================================
    */
}

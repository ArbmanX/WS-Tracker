<?php

namespace App\Services\WorkStudio\Queries;

use App\Services\WorkStudio\ResourceGroupAccessService;
use App\WSHelpers;

class PlannerOwnedCircuitsQuery
{
    /** Useful SQL snippets at bottom */

    /** RESOURCE GROUPS */
    // 1 CENTRAL
    // 2 DISTRIBUTION
    // 3 HARRISBURG
    // 4 LANCASTER
    // 5 LEHIGH
    // 6 PRE_PLANNERS
    // 7 VEG_ASSESSORS
    // 8 VEG_CREWS
    // 9 VEG_FOREMAN
    // 10 VEG_PLANNERS

    public static function selectStar(string $contractor = 'Asplundh'): string
    {
        $jobguid = '{9C2BFF24-4C3D-42D5-9E4E-7FCBEFAE7DF2}';


        return "SELECT
                    *
                FROM SS
                WHERE SS.JOBGUID = '{$jobguid}'";
    }

    public static function getAllJobGUIDsForActiveAndOwnedAssessments(?string $username = null, string $contractor = 'Asplundh'): string
    {

        $resourceGroups = app(ResourceGroupAccessService::class);

        $resGrpArr = $resourceGroups->getRegionsForRole('planner');
        $resGrps = WSHelpers::toSqlInClause($resGrpArr);


        return "SELECT 
                    SS.JOBGUID AS JOB_GUID,
                    SS.TAKENBY AS CURRENT_OWNER
                    FROM SS
                    WHERE SS.STATUS = 'ACTIV' 
                    AND SS.TAKEN = 'true'
                    AND SS.TAKENBY LIKE 'ASPLUNDH\\%'
                    AND SS.TAKENBY NOT IN ('ASPLUNDH\\jcompton', 'ASPLUNDH\\joseam')
                    AND SS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
                ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";

        // return "SELECT

        //     VEGJOB.CONTRACTOR AS Contractor,

        // (SELECT 
        //         TOP 1 VEGUNIT.FORESTER
        //         FROM VEGUNIT
        //         WHERE VEGUNIT.JOBGUID = WSREQSS.JOBGUID
        //         AND VEGUNIT.FORESTER IS NOT NULL
        //         AND VEGUNIT.FORESTER != '') AS Forester,

        //     VEGJOB.LINENAME AS Line_Name,
        //     SS.JOBTYPE AS Job_Type,   --Assessment Dx , Work Job Dx ...
        //     VEGJOB.CYCLETYPE AS Cycle_Type, -- Cycle Maintenance - Trim, Reactive, FFP Lump Sum ... 

        //     SS.JOBGUID AS Job_ID,
        //     SS.TAKENBY AS Current_Owner

        // FROM SS
        // INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        // INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        // WHERE VEGJOB.REGION IN ({$resGrps})
        // AND WSREQSS.STATUS = 'ACTIV'
        // AND VEGJOB.CONTRACTOR = '{$contractor}'
        // AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
        // AND WSREQSS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
        // ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    /**
     * Get the SQL query for fetching planner-owned circuits.
     *
     *
     * @param  string  $username  The planner's username (e.g., 'ASPLUNDH\\cnewcombe')
     * @param  string  $contractor  The contractor name (e.g., 'Asplundh')
     */
    public static function getOwnedAndActiveCircuits(string $username, string $contractor = 'Asplundh'): string
    {
        // $escapedUsername = str_replace('\\', '\\', $username);
        $escapedUsername = $username;

        return "SELECT
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            WSREQSS.WO AS Work_Order,
            WSREQSS.EXT AS Extension,
            WSREQSS.STATUS AS Status,
            WSREQSS.TAKEN AS Taken,
            WPStartDate_Assessment_Xrefs.WP_STARTDATE AS Scope_Year_Raw,

            CASE
                WHEN WPStartDate_Assessment_Xrefs.WP_STARTDATE IS NULL OR WPStartDate_Assessment_Xrefs.WP_STARTDATE = '' THEN NULL
                ELSE CAST(LEFT(REPLACE(REPLACE(WPStartDate_Assessment_Xrefs.WP_STARTDATE, '/Date(', ''), ')/', ''), 4) AS INT)
            END AS Scope_Year,

            VEGJOB.OPCO AS Utility,
            VEGJOB.REGION AS Region,
            VEGJOB.SERVCOMP AS Department,
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.LENGTHCOMP AS Completed_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            VEGJOB.CONTRACTOR AS Contractor,
            (SELECT 
                TOP 1 VEGUNIT.FORESTER
                FROM VEGUNIT
                WHERE VEGUNIT.JOBGUID = WSREQSS.JOBGUID
                AND VEGUNIT.FORESTER IS NOT NULL
                AND VEGUNIT.FORESTER != '') AS Forester,
                
            WSREQSS.TAKENBY AS Current_Owner,
            SS.MODIFIEDBY AS Last_Modified_By,
            FORMAT(
                CAST(
                    CAST(SS.EDITDATE AS DATETIME)
                    AT TIME ZONE 'UTC'
                    AT TIME ZONE 'Eastern Standard Time'
                AS DATETIME),
                    'MM/dd/yyyy h:mm tt'
            ) AS Last_Sync,

            WSREQSS.ASSIGNEDTO AS Assigned_To,
            VEGJOB.COSTMETHOD AS Cost_Method,
            VEGJOB.CIRCCOMNTS AS Circuit_Comments

        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        LEFT JOIN WPStartDate_Assessment_Xrefs ON SS.JOBGUID = WPStartDate_Assessment_Xrefs.Assess_JOBGUID
        WHERE VEGJOB.REGION IN (
            'CENTRAL', 'HARRISBURG', 
            'LEHIGH', 'LANCASTER', 
            'DISTRIBUTION', 'PRE_PLANNER', 
            'VEG_ASSESSORS', 'VEG_PLANNERS'
        )
        AND WSREQSS.STATUS = 'ACTIV'
        AND VEGJOB.CONTRACTOR = '{$contractor}'
        AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE 'ASPLUNDH\\%'
        AND WSREQSS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    /**
     * Get the SQL query for fetching planner-owned circuits.
     *
     *
     * @param  string  $username  The planner's username (e.g., 'ASPLUNDH\\cnewcombe')
     * @param  string  $contractor  The contractor name (e.g., 'Asplundh')
     */
    public static function getOwnedAndActiveJobGuidByPlannerUsername(string $username, string $contractor = 'Asplundh'): string
    {
        $escapedUsername = str_replace('\\', '\\', $username);
        // $escapedUsername = $username;

        return "SELECT
            WSREQSS.JOBGUID AS Job_ID,
            VEGJOB.LINENAME AS Line_Name,
            
            WSREQSS.JOBTYPE AS Job_Type,
            VEGJOB.CYCLETYPE AS Cycle_Type,

            VEGJOB.LENGTH AS Total_Miles,
            VEGJOB.PRCENT AS Percent_Complete,

            VEGJOB.CONTRACTOR AS Contractor,
            (SELECT 
                TOP 1 VEGUNIT.FORESTER
                FROM VEGUNIT
                WHERE VEGUNIT.JOBGUID = WSREQSS.JOBGUID
                AND VEGUNIT.FORESTER IS NOT NULL
                AND VEGUNIT.FORESTER != '') AS Forester,
                
            WSREQSS.TAKENBY AS Current_Owner


        FROM SS
        INNER JOIN SS AS WSREQSS ON SS.JOBGUID = WSREQSS.JOBGUID
        INNER JOIN VEGJOB ON SS.JOBGUID = VEGJOB.JOBGUID
        WHERE VEGJOB.REGION IN ('CENTRAL', 'HARRISBURG', 'LEHIGH', 'LANCASTER')
        AND WSREQSS.STATUS = 'ACTIV'
        AND VEGJOB.CONTRACTOR = '{$contractor}'
        AND WSREQSS.TAKENBY IS NOT NULL AND WSREQSS.TAKENBY != '' AND WSREQSS.TAKENBY LIKE '{$escapedUsername}'
        AND WSREQSS.JOBTYPE IN ('Assessment', 'Assessment Dx', 'Split_Assessment', 'Tandem_Assessment')
        ORDER BY SS.EDITDATE DESC, SS.WO DESC, SS.EXT DESC";
    }

    public static function getAllUnits(?string $jobguid): ?string
    {
        // return "SELECT
        //     CAST(CAST(REPLACE(REPLACE(V.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE) AS Assessed_Date,
        //     SUM(S.SPANLGTH) AS Total_Span_Length_Feet,
        //     CAST(SUM(S.SPANLGTH) / 5280.0 AS DECIMAL(10,4)) AS Total_Miles,
        //     COUNT(DISTINCT S.STATNAME) AS Station_Count,
        //     SUM(CASE WHEN V.UNIT != 'NW' THEN 1 ELSE 0 END) AS Unit_Count,
        //     (
        //         SELECT STRING_AGG(UNIT, ', ')
        //         FROM (SELECT DISTINCT V2.UNIT FROM VEGUNIT V2
        //               WHERE V2.JOBGUID = '{$jobguid}'
        //                 AND V2.UNIT IS NOT NULL AND V2.UNIT != ''
        //                 AND CAST(CAST(REPLACE(REPLACE(V2.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE) =
        //                     CAST(CAST(REPLACE(REPLACE(V.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE)
        //         ) AS x
        //     ) AS Units_Worked
        // FROM STATIONS S
        // INNER JOIN VEGUNIT V ON S.WO = V.WO AND S.STATNAME = V.STATNAME
        // WHERE S.JOBGUID = '{$jobguid}'
        //     AND V.UNIT IS NOT NULL
        //     AND V.ASSDDATE IS NOT NULL
        // GROUP BY CAST(CAST(REPLACE(REPLACE(V.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE)
        // ORDER BY CAST(CAST(REPLACE(REPLACE(V.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE) DESC";

        //     return "SELECT
        //     STATIONS.STATNAME AS Station_Name,
        //     MAX(STATIONS.SPANLGTH) AS Span_Length,
        //     STRING_AGG(VEGUNIT.UNIT, ', ') AS Units,
        //     STRING_AGG(VEGUNIT.PERMSTAT, ', ') AS Permission_Statuses,
        //     COUNT(VEGUNIT.UNIT) AS Unit_Count,
        //     MIN(CASE
        //         WHEN VEGUNIT.ASSDDATE IS NULL OR VEGUNIT.ASSDDATE = '' THEN NULL
        //         ELSE FORMAT(
        //             CAST(
        //                 CAST(
        //                     REPLACE(REPLACE(VEGUNIT.ASSDDATE, '/Date(', ''), ')/', '')
        //                 AS DATETIME)
        //                 AT TIME ZONE 'UTC'
        //                 AT TIME ZONE 'Eastern Standard Time'
        //             AS DATETIME),
        //             'MM/dd/yyyy h:mm tt'
        //         )
        //     END) AS First_Assessed_Date
        // FROM STATIONS
        // LEFT JOIN VEGUNIT ON STATIONS.WO = VEGUNIT.WO
        //     AND STATIONS.STATNAME = VEGUNIT.STATNAME
        // WHERE STATIONS.JOBGUID = '{$jobguid}'
        //     AND VEGUNIT.UNIT IS NOT NULL
        // GROUP BY STATIONS.STATNAME";

        //     return "SELECT
        //     Station_Name, Span_Length, Unit, Assessment_Notes,
        //     Parcel_Notes, Permission_Status, First_Name, Last_Name,
        //     C_Address, C_City, C_State, C_Zip, State, Assessed_Date
        // FROM (
        //     SELECT
        //         STATIONS.STATNAME AS Station_Name,
        //         STATIONS.SPANLGTH AS Span_Length,
        //         VEGUNIT.UNIT AS Unit,
        //         VEGUNIT.ASSNOTE AS Assessment_Notes,
        //         VEGUNIT.PARCELCOMMENTS AS Parcel_Notes,
        //         VEGUNIT.PERMSTAT AS Permission_Status,
        //         VEGUNIT.FIRSTNAME AS First_Name,
        //         VEGUNIT.LASTNAME AS Last_Name,
        //         VEGUNIT.CADDRESS AS C_Address,
        //         VEGUNIT.CCITY AS C_City,
        //         VEGUNIT.CSTATE AS C_State,
        //         VEGUNIT.CZIP AS C_Zip,
        //         VEGUNIT.STATE AS State,
        //         CASE
        //             WHEN VEGUNIT.ASSDDATE IS NULL OR VEGUNIT.ASSDDATE = '' THEN NULL
        //             ELSE FORMAT(
        //                 CAST(
        //                     CAST(
        //                         REPLACE(REPLACE(VEGUNIT.ASSDDATE, '/Date(', ''), ')/', '')
        //                     AS DATETIME)
        //                     AT TIME ZONE 'UTC'
        //                     AT TIME ZONE 'Eastern Standard Time'
        //                 AS DATETIME),
        //                 'MM/dd/yyyy h:mm tt'
        //             )
        //         END AS Assessed_Date,
        //         ROW_NUMBER() OVER (PARTITION BY STATIONS.STATNAME ORDER BY VEGUNIT.ASSDDATE DESC) AS rn
        //     FROM STATIONS
        //     LEFT JOIN VEGUNIT ON STATIONS.WO = VEGUNIT.WO
        //         AND STATIONS.STATNAME = VEGUNIT.STATNAME
        //     WHERE STATIONS.JOBGUID = '{$jobguid}'
        //         AND VEGUNIT.UNIT IS NOT NULL
        // ) sub
        // WHERE rn = 1";

        // return "SELECT DISTINCT
        //     STATIONS.STATNAME AS Station_Name,
        //     STATIONS.SPANLGTH AS Span_Length,
        //     VEGUNIT.UNIT AS Unit,
        //     VEGUNIT.ASSDDATE AS Assessed_On
        // FROM STATIONS
        // LEFT JOIN VEGUNIT ON STATIONS.WO = VEGUNIT.WO
        //     AND STATIONS.STATNAME = VEGUNIT.STATNAME
        // WHERE STATIONS.JOBGUID = '{$jobguid}'
        // AND VEGUNIT.UNIT IS NOT NULL
        // GROUP BY CAST(CAST(REPLACE(REPLACE(VEGUNIT.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME) AS DATE)";

        // Returns all stations with their units (including NULL units for unassessed stations)
        return "SELECT
            STATIONS.STATNAME AS Station_Name,
            STATIONS.SPANLGTH AS Span_Length,
            VEGUNIT.WO AS WO,
            VEGUNIT.UNIT AS Unit,
            VEGUNIT.PERMSTAT AS Permission_Status,
            CASE
                WHEN VEGUNIT.ASSDDATE IS NULL OR VEGUNIT.ASSDDATE = '' THEN NULL
                ELSE CONVERT(VARCHAR(10),
                    CAST(REPLACE(REPLACE(VEGUNIT.ASSDDATE, '/Date(', ''), ')/', '') AS DATETIME),
                    120)
            END AS Assessed_Date
        FROM STATIONS
        LEFT JOIN VEGUNIT ON STATIONS.WO = VEGUNIT.WO
            AND STATIONS.STATNAME = VEGUNIT.STATNAME
        WHERE STATIONS.JOBGUID = '{$jobguid}'";
    }

    // useful snippets

    // =====Gets all the groups a user belongs to ==========
    //     (SELECT GROUPDESC FROM (
    //     SELECT RESOURCEPERSON.GROUPDESC,
    //     ROW_NUMBER() OVER (ORDER BY RESOURCEPERSON.GROUPDESC) as rn
    //     FROM RESOURCEPERSON
    //     WHERE RESOURCEPERSON.GROUPTYPE = 'GROUP'
    //     AND RESOURCEPERSON.USERNAME = '{$escapedUsername}'
    // ) sub WHERE rn = 2) AS Resource_Group
}

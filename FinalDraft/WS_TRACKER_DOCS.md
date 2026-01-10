\***\*\*\*\*** DOCS ****\*\*\*\*****\*****\*\*\*\*****

##Link to home page of API Docs for WS Distribution -https://ppl02.geodigital.com:8372/documentation/LandingPage.html

##Link to the API Docs for Distribution -https://ppl02.geodigital.com:8372/protocol

##Link to Database Schema for Distribution -https://ppl02.geodigital.com:8372/documentation/DatabaseSchema.html

##Link to home page of API Docs for WS Transmission -https://ppl01.geodigital.com:8380/documentation/LandingPage.html

##Link to the API Docs for Transmission -https://ppl01.geodigital.com:8380/protocols/

##Link to Database Schema for Transmission -https://ppl01.geodigital.com:8380/documentation/DatabaseSchema.html

**\*\*\*\***\*\*\*\***\*\*\*\*** API REQUESTS ****\*\*\*\*****\*****\*\*\*\*****

## API Integration

### WorkStudio API Base URLs

| Environment  | Base URL                                         |
| ------------ | ------------------------------------------------ |
| Distribution | `https://ppl02.geodigital.com:8372/ddoprotocol/` |

### Endpoints & View GUIDs

| View                   | GUID                                     | Purpose       |
| ---------------------- | ---------------------------------------- | ------------- |
| Vegetation Assessments | `{A856F956-88DF-4807-90E2-7E12C25B5B32}` | Circuit list  |
| Planned Units          | `{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}` | Unit details  |

---

### FILTER VALUES

**_THESE ARE THE VALUES THAT SHOULD BE USED TO STRUCTURE THE API CALLS_**
**ACTIV WILL BE THE DEFAULT**

```json
{
  "WorkViews": [
    {
      "ViewGuid": "",
      "ViewCaption": "",
      "ViewFilters": [
        {
          "FilterValue": "SA",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "New"
        },
        {
          "FilterValue": "ACTIV",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "In Progress"
        },
        {
          "FilterValue": "QC",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "QC"
        },
        {
          "FilterValue": "REWRK",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "Rework"
        },
        {
          "FilterValue": "DEF",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "Deferral"
        },
        {
          "FilterValue": "CLOSE",
          "FilterPrompt": "",
          "FilterName": "By Job Status",
          "FilterCaption": "Closed"
        },
        {
          "FilterValue": "",
          "FilterPrompt": "Enter Work Order number (i.e. 2015-0093)",
          "FilterName": "Work Order Search",
          "FilterCaption": "Work Order Search"
        }
      ]
    }
  ]
}
```

***

# VEG ASSESSMENTs

### CIRCUIT LISTS

***REQUEST***

*This is used to get a list of circuits based on a filter*
**ALL THE DATA RETURNED FROM THIS API CALL WILL NEED CACHED AND JOBUID AND CIRCUIT NAME SHOULD BE CACHED AS A KEY VALUE PAIR.**

```json
{
  "protocol": "GETVIEWDATA",
  "ViewDefinitionGuid": "{A856F956-88DF-4807-90E2-7E12C25B5B32}",
  "ViewFilter": {
    "FilterName": "By Job Status",
    "FilterValue": "ACTIV",
    "FilterCaption": "In Progress",
    "PersistFilter": true,
    "FilterPrompt": "",
    "ClassName": "TViewFilter"
  },
  "ResultFormat": "DDOTable"
}
```

***RESPONSE***

```json
{
  "Protocol": "DATASET",
  "DataSet": {
    "Heading": [
      "VEGJOB_SERVCOMP",
      "VEGJOB_OPCO",
      "REGION",
      "SS_READONLY",
      "WPStartDate_Assessment_Xrefs_WP_STARTDATE",
      "SS_WO",
      "SS_EXT",
      "SS_TITLE",
      "VEGJOB_CYCLETYPE",
      "SS_JOBTYPE",
      "VEGJOB_LINENAME",
      "VEGJOB_LENGTH",
      "VEGJOB_LENGTHCOMP",
      "VEGJOB_PRCENT",
      "VEGJOB_PROJACRES",
      "UNITCOUNTS_LENGTHWRK",
      "UNITCOUNTS_NUMTREES",
      "SS_EDITDATE",
      "SS_ASSIGNEDTO",
      "VEGJOB_FORESTER",
      "VEGJOB_CONTRACTOR",
      "VEGJOB_GF",
      "SS_TAKENBY",
      "VEGJOB_CIRCCOMNTS",
      "SS_ITEMTYPELIST",
      "VEGJOB_COSTMETHOD",
      "SS_JOBGUID",
      "WSREQ_READONLY",
      "WSREQ_SYNCHSTATE",
      "WSREQ_STATUS",
      "WSREQ_JOBGUID",
      "WSREQ_WO",
      "WSREQ_EXT",
      "WSREQ_JOBTYPE",
      "WSREQ_TAKEN",
      "WSREQ_TAKENBY",
      "WSREQ_BOUNDSGEOM",
      "WSREQ_SKETCHLEFT",
      "WSREQ_SKETCHTOP",
      "WSREQ_SKETCHBOTM",
      "WSREQ_SKETCHRITE",
      "WSREQ_COORDSYS",
      "WSREQ_ASSIGNEDTO",
      "WSREQ_VERSION",
      "WSREQ_SYNCHVERSN",
      "WSREQ_ESTMINS",
      "WSREQ_MINSLEFT",
      "WSREQ_TRAVELMINS"
    ],
    "Data": [
      [
        "Distribution",
        "PPL",
        "Lehigh",
        false,
        "/Date(2026-01-01)/",
        "2025-1930",
        "@",
        "HATFIELD 69/12 KV 20-01 LINE",
        "Cycle Maintenance - Trim",
        "Assessment Dx",
        "HATFIELD 69/12 KV 20-01 LINE",
        14.93,
        4.38,
        35,
        1.42,
        1.28800945233652,
        50,
        "/Date(2025-12-05T20:12:44.142Z)/",
        "",
        "Derek Cinicola ",
        "ASPLUNDH",
        "",
        "ASPLUNDH\\plongenecker",
        "",
        "UNIT",
        "UC",
        "{14A9372D-531F-4CE9-9906-657A6C965CC0}",
        false,
        false,
        "ACTIV",
        "{14A9372D-531F-4CE9-9906-657A6C965CC0}",
        "2025-1930",
        "@",
        "Assessment Dx",
        true,
        "ASPLUNDH\\plongenecker",
        {
          "@sourceFormat": "DataObjectGeometry",
          "type": "Polygon",
          "coordinates": [
            [
              [-75.2830737764032, 40.33851436344],
              [-75.2830691707838, 40.3385144885653],
              [-75.2830375689372, 40.3385097973382],
              [-75.2830058718412, 40.3385058000364],
              [-75.2830014702744, 40.3385044385681],
              [-75.2829969128969, 40.3385037620351]
            ]
          ]
        },
        -75.31345590325,
        40.3010312470572,
        40.3385149399004,
        -75.2675155416109,
        "GPS Latitude/Longitude",
        "",
        27,
        23,
        0,
        0,
        0
      ],
      [
        "Distribution",
        "PPL",
        "Lancaster",
        false,
        "/Date(2026-01-01)/",
        "2025-2077",
        "@",
        "EAST PETERSBURG 69/12 KV 15-05 LINE",
        "Cycle Maintenance - Trim",
        "Assessment Dx",
        "EAST PETERSBURG 69/12 KV 15-05 LINE",
        8.56,
        8.56,
        100,
        0.53,
        1.38966495177645,
        29,
        "/Date(2025-12-05T20:12:23.408Z)/",
        "",
        "Derek Cinicola ",
        "Asplundh",
        "",
        "ONEPPL\\LWaltermyer@pplweb.com",
        "",
        "UNIT",
        "UC",
        "{6610A60E-29F1-469B-ADD0-5DAB1F1387BE}",
        false,
        false,
        "ACTIV",
        "{6610A60E-29F1-469B-ADD0-5DAB1F1387BE}",
        "2025-2077",
        "@",
        "Assessment Dx",
        true,
        "ONEPPL\\LWaltermyer@pplweb.com",
        {
          "@sourceFormat": "DataObjectGeometry",
          "type": "Polygon",
          "coordinates": [
            [
              [-76.3053522178548, 40.1003914315161],
              [-76.305358082792, 40.1003768864364],
              [-76.3053691129457, 40.100359207402],
              [-76.3053787889122, 40.1003407523696],
              [-76.3053883725667, 40.1003283382489],
              [-76.3053966741288, 40.1003150325773],
              [-76.3054106066418, 40.1002995374932],
              [-76.3054233402914, 40.1002830430482],
              [-76.3054349340382, 40.10027248171],
              [-76.3054454199865, 40.1002608197326]
            ]
          ]
        },
        -76.3457573265615,
        40.0901936000717,
        40.1189971968747,
        -76.3053130806695,
        "GPS Latitude/Longitude",
        "",
        122,
        110,
        0,
        0,
        0
      ]
    ]
  },
  "SQL": "select VEGJOB.SERVCOMP as VEGJOB_SERVCOMP,VEGJOB.OPCO as VEGJOB_OPCO,VEGJOB.REGION as REGION,SS.READONLY as SS_READONLY,WPStartDate_Assessment_Xrefs.WP_STARTDATE as WPStartDate_Assessment_Xrefs_WP_STARTDATE,SS.WO as SS_WO,SS.EXT as SS_EXT,SS.TITLE as SS_TITLE,VEGJOB.CYCLETYPE as VEGJOB_CYCLETYPE,SS.JOBTYPE as SS_JOBTYPE,VEGJOB.LINENAME as VEGJOB_LINENAME,VEGJOB.LENGTH as VEGJOB_LENGTH,VEGJOB.LENGTHCOMP as VEGJOB_LENGTHCOMP,VEGJOB.PRCENT as VEGJOB_PRCENT,VEGJOB.PROJACRES as VEGJOB_PROJACRES,UNITCOUNTS.LENGTHWRK as UNITCOUNTS_LENGTHWRK,UNITCOUNTS.NUMTREES as UNITCOUNTS_NUMTREES,SS.EDITDATE as SS_EDITDATE,Coalesce(SSCUSTOM.GROUPASSIGNEDTO_USERS, SS.ASSIGNEDTO) as SS_ASSIGNEDTO,VEGJOB.FORESTER as VEGJOB_FORESTER,VEGJOB.CONTRACTOR as VEGJOB_CONTRACTOR,VEGJOB.GF as VEGJOB_GF,SS.TAKENBY as SS_TAKENBY,VEGJOB.CIRCCOMNTS as VEGJOB_CIRCCOMNTS,SS.ITEMTYPELIST as SS_ITEMTYPELIST,VEGJOB.COSTMETHOD as VEGJOB_COSTMETHOD,SS.JOBGUID as SS_JOBGUID,WSREQSS.READONLY as WSREQ_READONLY,WSREQSS.READONLY as WSREQ_SYNCHSTATE,WSREQSS.STATUS as WSREQ_STATUS,WSREQSS.JOBGUID as WSREQ_JOBGUID,WSREQSS.WO as WSREQ_WO,WSREQSS.EXT as WSREQ_EXT,WSREQSS.JOBTYPE as WSREQ_JOBTYPE,WSREQSS.TAKEN as WSREQ_TAKEN,WSREQSS.TAKENBY as WSREQ_TAKENBY,WSREQSS.BOUNDSGEOM as WSREQ_BOUNDSGEOM,WSREQSS.SKETCHLEFT as WSREQ_SKETCHLEFT,WSREQSS.SKETCHTOP as WSREQ_SKETCHTOP,WSREQSS.SKETCHBOTM as WSREQ_SKETCHBOTM,WSREQSS.SKETCHRITE as WSREQ_SKETCHRITE,WSREQSS.COORDSYS as WSREQ_COORDSYS,WSREQSS.ASSIGNEDTO as WSREQ_ASSIGNEDTO,WSREQSS.VERSION as WSREQ_VERSION,WSREQSS.SYNCHVERSN as WSREQ_SYNCHVERSN,WSREQSS.ESTMINS as WSREQ_ESTMINS,WSREQSS.MINSLEFT as WSREQ_MINSLEFT,WSREQSS.TRAVELMINS as WSREQ_TRAVELMINS from [SS]  LEFT OUTER JOIN [VEGJOB] ON (SS.JOBGUID=VEGJOB.JOBGUID)  LEFT OUTER JOIN [UNITCOUNTS] ON (SS.JOBGUID=UNITCOUNTS.JOBGUID)  LEFT OUTER JOIN [SSCUSTOM] ON (SS.JOBGUID=SSCUSTOM.JOBGUID)  LEFT OUTER JOIN [WPStartDate_Assessment_Xrefs] ON (SS.JOBGUID=WPStartDate_Assessment_Xrefs.Assess_JOBGUID)  INNER JOIN SS as [WSREQSS] ON (SS.JOBGUID=WSREQSS.JOBGUID) where 1=1  AND 1 = 1 and VEGJOB.REGION in (select RESOURCEPERSON.GROUPDESC from RESOURCEPERSON where RESOURCEPERSON.GROUPTYPE = 'GROUP' AND RESOURCEPERSON.USERNAME = 'ASPLUNDH\\cnewcombe') AND WSREQSS.STATUS = 'ACTIV' AND ( WSREQSS.JOBTYPE = 'Assessment' OR WSREQSS.JOBTYPE = 'Assessment Dx' OR WSREQSS.JOBTYPE = 'Split_Assessment' OR WSREQSS.JOBTYPE = 'Tandem_Assessment')AND SS.STATUS IN ('SA', 'PERM', 'PERMC', 'ACTIV', 'QC', 'REWRK', 'DEF', 'NRS', 'A', 'READY', 'WPGEN', 'S8', 'SZ', 'S9', 'CMPLT', 'CLOSE', 'NEW', 'PREV', 'PTNEW') order by SS.EDITDATE  DESC,SS.WO  DESC,SS.EXT  DESC",
  "ColumnGroup": {}
}
```

***

### GET WHOLE ASSESSMENT

***REQUEST***

*This requires the jobguid to make the request*


```json
{
  "Protocol": "REQUESTJOB",
  "jobDescription": {
    "jobguid": "this will come from the circuit list",
    "ReadOnly": true
  },
  "ResultFormat": "DDOTable"
}
```

***RESPONSE***
**BE CAREFUL CAN POTENTIALLY have 1 million rows + of data on DDOTable**
-Need to come up with the best way to handle this response because it will be very large and I will only need a few of the values. Can possibly run cron job over night and cache the values we would need, with a manual sync option. 

*Response is in its own file 

***

### GET PLANNED UNITS

*THIS WILL BE ADDED IN THE FUTURE, THE REQUESTJOB ENDPOINT BRINGS IN THE SAME DATA AND MORE OF IT*

***REQUEST***

_This will require the WO# which can be provided manually or by using the circuit list_
**Planned Units (by WO#):**

```json
{
  "protocol": "GETVIEWDATA",
  "ViewDefinitionGuid": "{985AECEF-D75B-40F3-9F9B-37F21C63FF4A}",
  "ViewFilter": {
    "FilterName": "WO#",
    "FilterValue": "2025-1234",
    "FilterCaption": "WO Number"
  },
  "ResultFormat": "DDOTable"
}
```

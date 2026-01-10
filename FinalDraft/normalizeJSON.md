 #Approach 1: Normalized with References (Recommended)
{
    "protocol": "WorkViewData",
    "filterDefinitions": {
      "jobStatus": {
        "filterName": "By Job Status",
        "filterPrompt": "",
        "filters": [
          { "id": "new", "value": "SA", "caption": "New" },
          { "id": "inProgress", "value": "ACTIV", "caption": "In Progress" },
          { "id": "qc", "value": "QC", "caption": "QC" },
          { "id": "rework", "value": "REWRK", "caption": "Rework" },
          { "id": "deferral", "value": "DEF", "caption": "Deferral" },
          { "id": "closed", "value": "CLOSE", "caption": "Closed" }
        ]
      },
      "workOrderSearch": {
        "filterName": "Work Order Search",
        "filterPrompt": "Enter Work Order number (i.e. 2015-0093)",
        "filterValue": "",
        "filterCaption": "Work Order Search"
      }
    },
    "workViews": [
      {
        "viewGuid": "{A856F956-88DF-4807-90E2-7E12C25B5B32}",
        "viewCaption": "Vegetation Assessments",
        "filters": ["jobStatus", "workOrderSearch"]
      },
      {
        "viewGuid": "{546D9963-9242-4945-8A74-15CA83CDA537}",
        "viewCaption": "Work Jobs",
        "filters": ["jobStatus"]
      }
    ]
  }


  Extraction Example:
  function getViewWithFilters(data, viewCaption) {
    const view = data.workViews.find(v => v.viewCaption === viewCaption);
    if (!view) return null;

    return {
      ...view,
      expandedFilters: view.filters.map(filterId => ({
        id: filterId,
        ...data.filterDefinitions[filterId]
      }))
    };
  }

  // Usage
  const vegAssessments = getViewWithFilters(data, "Vegetation Assessments");

  ---
  #Approach 2: Flat Structure with Indices

  {
    "protocol": "WorkViewData",
    "filters": {
      "SA": { "caption": "New", "type": "jobStatus" },
      "ACTIV": { "caption": "In Progress", "type": "jobStatus" },
      "QC": { "caption": "QC", "type": "jobStatus" },
      "REWRK": { "caption": "Rework", "type": "jobStatus" },
      "DEF": { "caption": "Deferral", "type": "jobStatus" },
      "CLOSE": { "caption": "Closed", "type": "jobStatus" },
      "WORK_ORDER": {
        "caption": "Work Order Search",
        "type": "search",
        "prompt": "Enter Work Order number (i.e. 2015-0093)"
      }
    },
    "workViews": {
      "{A856F956-88DF-4807-90E2-7E12C25B5B32}": {
        "caption": "Vegetation Assessments",
        "filters": ["SA", "ACTIV", "QC", "REWRK", "DEF", "CLOSE", "WORK_ORDER"]
      },
      "{546D9963-9242-4945-8A74-15CA83CDA537}": {
        "caption": "Work Jobs",
        "filters": ["SA", "ACTIV", "QC", "REWRK", "DEF", "CLOSE"]
      }
    }
  }

  Extraction Example:
  function getFiltersByView(data, viewGuid) {
    const view = data.workViews[viewGuid];
    return view.filters.map(filterCode => ({
      code: filterCode,
      ...data.filters[filterCode]
    }));
  }

  ---
  #Approach 3: Hierarchical with Shared Configs

  {
    "protocol": "WorkViewData",
    "config": {
      "commonFilters": {
        "standardJobStatuses": [
          { "value": "SA", "caption": "New" },
          { "value": "ACTIV", "caption": "In Progress" },
          { "value": "QC", "caption": "QC" },
          { "value": "REWRK", "caption": "Rework" },
          { "value": "DEF", "caption": "Deferral" },
          { "value": "CLOSE", "caption": "Closed" }
        ]
      }
    },
    "workViews": [
      {
        "viewGuid": "{A856F956-88DF-4807-90E2-7E12C25B5B32}",
        "viewCaption": "Vegetation Assessments",
        "filters": {
          "jobStatus": {
            "type": "predefined",
            "source": "config.commonFilters.standardJobStatuses"
          },
          "workOrderSearch": {
            "type": "custom",
            "filterName": "Work Order Search",
            "filterPrompt": "Enter Work Order number (i.e. 2015-0093)",
            "filterValue": ""
          }
        }
      },
      {
        "viewGuid": "{546D9963-9242-4945-8A74-15CA83CDA537}",
        "viewCaption": "Work Jobs",
        "filters": {
          "jobStatus": {
            "type": "predefined",
            "source": "config.commonFilters.standardJobStatuses"
          }
        }
      }
    ]
  }

  ---
  #Approach 4: Tag-based System (Most Flexible)

  {
    "protocol": "WorkViewData",
    "filterSets": {
      "standardJobStatus": {
        "name": "By Job Status",
        "prompt": "",
        "options": [
          { "value": "SA", "caption": "New", "tags": ["new", "unassigned"] },
          { "value": "ACTIV", "caption": "In Progress", "tags": ["active", "in-progress"] },
          { "value": "QC", "caption": "QC", "tags": ["review", "quality"] },
          { "value": "REWRK", "caption": "Rework", "tags": ["active", "rework"] },
          { "value": "DEF", "caption": "Deferral", "tags": ["deferred", "postponed"] },
          { "value": "CLOSE", "caption": "Closed", "tags": ["completed", "archived"] }
        ]
      }
    },
    "searchFilters": {
      "workOrder": {
        "name": "Work Order Search",
        "prompt": "Enter Work Order number (i.e. 2015-0093)",
        "inputType": "text",
        "pattern": "\\d{4}-\\d{4}"
      }
    },
    "workViews": [
      {
        "id": "veg-assessments",
        "guid": "{A856F956-88DF-4807-90E2-7E12C25B5B32}",
        "caption": "Vegetation Assessments",
        "filterSets": ["standardJobStatus"],
        "searches": ["workOrder"]
      },
      {
        "id": "work-jobs",
        "guid": "{546D9963-9242-4945-8A74-15CA83CDA537}",
        "caption": "Work Jobs",
        "filterSets": ["standardJobStatus"]
      }
    ]
  }

  Extraction Example:
  function buildViewFilters(data, viewId) {
    const view = data.workViews.find(v => v.id === viewId);

    const filters = {
      sets: view.filterSets?.map(setName => ({
        name: setName,
        ...data.filterSets[setName]
      })) || [],
      searches: view.searches?.map(searchName => ({
        name: searchName,
        ...data.searchFilters[searchName]
      })) || []
    };

    return filters;
  }

  // Get all active status filters
  function getFiltersByTag(data, tag) {
    const results = [];
    Object.entries(data.filterSets).forEach(([setName, set]) => {
      set.options.forEach(option => {
        if (option.tags?.includes(tag)) {
          results.push({ setName, ...option });
        }
      });
    });
    return results;
  }

  // Usage
  const activeFilters = getFiltersByTag(data, 'active');
  // Returns: [{ value: "ACTIV", caption: "In Progress", ... }, { value: "REWRK", caption: "Rework", ... }]

  ---
  Comparison & Recommendations

  | Approach        | Best For                  | Pros                               | Cons
    |
  |-----------------|---------------------------|------------------------------------|---------------------------
  --|
  | 1. Normalized   | General use, APIs         | Clear separation, easy to maintain | Requires join logic
    |
  | 2. Flat/Indexed | Fast lookups, caching     | O(1) access, simple                | Less hierarchical
  structure |
  | 3. Hierarchical | Configuration files       | Intuitive, schema-like             | Can get deeply nested
    |
  | 4. Tag-based    | Complex filtering, search | Very flexible, queryable           | Overhead for simple cases
    |

  My Recommendation: Hybrid Approach

  {
    "protocol": "WorkViewData",
    "meta": {
      "version": "1.0",
      "lastUpdated": "2024-01-01"
    },
    "shared": {
      "jobStatuses": {
        "new": { "code": "SA", "name": "New", "order": 1 },
        "inProgress": { "code": "ACTIV", "name": "In Progress", "order": 2 },
        "qc": { "code": "QC", "name": "QC", "order": 3 },
        "rework": { "code": "REWRK", "name": "Rework", "order": 4 },
        "deferral": { "code": "DEF", "name": "Deferral", "order": 5 },
        "closed": { "code": "CLOSE", "name": "Closed", "order": 6 }
      }
    },
    "views": {
      "veg-assessments": {
        "guid": "{A856F956-88DF-4807-90E2-7E12C25B5B32}",
        "caption": "Vegetation Assessments",
        "filters": {
          "statuses": ["new", "inProgress", "qc", "rework", "deferral", "closed"],
          "searches": [{
            "type": "workOrder",
            "prompt": "Enter Work Order number (i.e. 2015-0093)"
          }]
        }
      },
      "work-jobs": {
        "guid": "{546D9963-9242-4945-8A74-15CA83CDA537}",
        "caption": "Work Jobs",
        "filters": {
          "statuses": ["new", "inProgress", "qc", "rework", "deferral", "closed"]
        }
      }
    }
  }

  Complete Utility Functions:

  class WorkViewManager {
    constructor(data) {
      this.data = data;
    }

    // Get all filters for a view
    getViewFilters(viewKey) {
      const view = this.data.views[viewKey];
      if (!view) return null;

      return {
        statuses: view.filters.statuses?.map(statusKey => ({
          key: statusKey,
          ...this.data.shared.jobStatuses[statusKey]
        })).sort((a, b) => a.order - b.order) || [],
        searches: view.filters.searches || []
      };
    }

    // Get view by GUID
    getViewByGuid(guid) {
      return Object.entries(this.data.views)
        .find(([_, view]) => view.guid === guid)?.[1];
    }

    // Get all views with a specific filter
    getViewsWithStatus(statusKey) {
      return Object.entries(this.data.views)
        .filter(([_, view]) => view.filters.statuses?.includes(statusKey))
        .map(([key, view]) => ({ key, ...view }));
    }

    // Export for API/UI
    toAPI() {
      return Object.entries(this.data.views).map(([key, view]) => ({
        id: key,
        guid: view.guid,
        caption: view.caption,
        filters: this.getViewFilters(key)
      }));
    }
  }

  // Usage
  const manager = new WorkViewManager(jsonData);
  const vegFilters = manager.getViewFilters('veg-assessments');
  const allViews = manager.toAPI();

  This hybrid approach gives you:
  - ✅ No data repetition
  - ✅ Easy extraction with helper class
  - ✅ Simple structure
  - ✅ Extensible (add new properties without breaking existing code)
  - ✅ Type-safe keys instead of searching arrays


# Analytics Dashboard Foundation

## Optimized Prompt

**Create a basic analytics dashboard structure that can be iteratively enhanced with planner metrics.**

### Objective:
Implement a foundational analytics dashboard layout. Start with a minimal design that establishes the structure for displaying planner and circuit analytics. This will be the home for planner performance metrics.

### Tasks:

#### 1. Dashboard Layout:
- Create Analytics dashboard Livewire component
- Design responsive grid layout for metric cards/charts
- Implement date range selector (default to current week)
- Add planner filter (single planner or all planners)

#### 2. Placeholder Sections:
- Summary stats section (total circuits, total miles, etc.)
- Planner performance section (placeholder for detailed metrics)
- Trends section (placeholder for charts)

#### 3. Basic Metrics Display:
- Total active circuits count
- Total miles across active circuits
- Number of planners
- Use DaisyUI stat components

#### 4. Foundation for Future:
- Structure for adding chart library (Chart.js, ApexCharts, etc.)
- Data fetching patterns that support time-range queries
- Component structure allowing section-by-section enhancement

### Design Guidelines:
- Use DaisyUI components (stats, cards)
- Skeleton loaders for async data
- Clean, professional appearance
- Mobile-responsive grid

### Technical Constraints:
- Livewire 3 component structure
- Efficient aggregate queries
- Follow existing code patterns

### NOT in Scope (for this prompt):
- Detailed planner analytics (Prompt 07)
- Historical comparisons
- Export functionality
- Complex visualizations

---

## Context

This creates the "container" for analytics. We're setting up the page structure and basic metrics. Detailed planner analytics will be added in subsequent prompts.

## Dependencies
- Prompt 04 (Overview Dashboard Polish) - Design consistency
- Prompt 03 (Schema Redesign) - Data availability

## Success Criteria
- [ ] Analytics dashboard page created
- [ ] Basic layout with sections defined
- [ ] Summary metrics displaying
- [ ] Date range and planner filters working
- [ ] Responsive design

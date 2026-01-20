# Phase 1: App Shell Layout

**Status:** Ready for Approval
**Branch:** `feature/app-shell-layout`
**Estimated Files:** 14 new, 3 modified, 4 removed

---

## Overview

This phase creates a new DaisyUI-based app shell (sidebar + header layout) that will replace all Flux layout components. The shell will serve as the foundation for both the Assessments dashboard and future Work Jobs dashboard.

### Goals
1. Create a responsive sidebar with drawer overlay on mobile
2. Create a top header bar with user menu
3. Establish Alpine.js state management scaffolding for dashboard state
4. Set up the new folder structure convention
5. Preserve existing theme system (already DaisyUI-compatible)
6. Remove Flux dependencies from the layout (not other pages yet)
7. Remove unused elements and elements that are replaced by newly created ones.

### Non-Goals (Future Phases)
- Migrating existing pages (settings, admin, auth) away from Flux
- Dashboard content/widgets
- User customization persistence

---

## Folder Structure

```
resources/views/
├── components/
│   ├── layouts/
│   │   ├── app-shell.blade.php          # NEW: Main DaisyUI layout
│   │   ├── partials/
│   │   │   ├── sidebar.blade.php        # NEW: DaisyUI sidebar
│   │   │   ├── sidebar-nav.blade.php    # NEW: Navigation items
│   │   │   ├── header.blade.php         # NEW: Top header bar
│   │   │   ├── user-menu.blade.php      # NEW: Unified user dropdown
│   │   │   └── mobile-drawer.blade.php  # NEW: Mobile drawer wrapper
│   │   ├── app.blade.php                # KEEP: Legacy Flux (for migration)
│   │   └── app/                         # KEEP: Legacy Flux partials
│   └── ui/
│       ├── icon.blade.php               # NEW: Icon component (Heroicons)
│       ├── tooltip.blade.php            # NEW: DaisyUI tooltip wrapper
│       ├── breadcrumbs.blade.php        # NEW: Breadcrumb navigation
│       └── theme-toggle.blade.php       # NEW: Theme switcher dropdown
│
├── livewire/
│   └── layouts/
│       └── app-shell-state.blade.php    # NEW: Alpine state initialization
│
app/Livewire/
└── Layouts/
    └── AppShellState.php                # NEW: Livewire state sync component

resources/js/
└── alpine/
    └── dashboard-state.js               # NEW: Alpine store for dashboard
```

---

## Component Specifications

### 1. App Shell (`components/layouts/app-shell.blade.php`)

The main layout wrapper that composes sidebar, header, and content area.

```html
<!-- Props -->
@props([
    'title' => null,           // Page title for <title> tag
    'showSidebar' => true,     // Toggle sidebar visibility
    'fullWidth' => false,      // Full-width content (no max-width)
    'breadcrumbs' => [],       // Array of breadcrumb items: ['label' => 'Home', 'route' => 'dashboard']
])
```

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│ [Mobile Only] Header with hamburger + logo + user avatar    │
├──────────┬──────────────────────────────────────────────────┤
│          │                                                  │
│          │                                                  │
│ Sidebar  │              Main Content                        │
│ (hidden  │              (with optional header)              │
│  mobile) │                                                  │
│          │                                                  │
│          │                                                  │
├──────────┴──────────────────────────────────────────────────┤
│ [Mobile Only] Bottom safe area padding                      │
└─────────────────────────────────────────────────────────────┘
```

**Responsive Behavior:**
- **Mobile (<768px):** Sidebar hidden, drawer overlay triggered by hamburger
- **Tablet (768-1024px):** Sidebar visible, collapsed to icons only, narrower width (~64px)
- **Desktop (>1024px):** Sidebar expanded by default (256px), user can collapse to icons (~64px)

**Sidebar Widths:**
- Expanded: 256px (standard)
- Collapsed (icons only): 64px
- Tablet default: Collapsed (64px)

---

### 2. Sidebar (`components/layouts/partials/sidebar.blade.php`)

DaisyUI drawer-based sidebar with collapsible navigation groups.

**Features:**
- Sticky positioning
- Logo at top (links to user's home dashboard or default dashboard)
- Collapsible navigation groups
- Collapse toggle button (desktop) to switch between expanded/icons-only
- User menu at bottom (desktop only)
- Scroll overflow for long nav lists
- Remembers collapsed state in localStorage

**DaisyUI Components Used:**
- `drawer` (for mobile overlay behavior)
- `menu` (for navigation items)
- `collapse` (for expandable groups)
- `tooltip` (for icon-only mode labels)
- `avatar` (for user display)
- `badge` (for notification counts)

**Navigation Structure (Mock):**
```
── Platform
   ├─ Overview Dashboard
   ├─ Kanban Board
   └─ Analytics

── Administration (admin only)
   ├─ Sync Control
   ├─ Sync History
   ├─ Unlinked Planners
   └─ User Management (sudo only)

── Settings
   ├─ Profile
   ├─ Appearance
   └─ Two-Factor

[Spacer]

── Help & Links
   ├─ Documentation
   └─ Repository
```

---

### 3. Header (`components/layouts/partials/header.blade.php`)

Top header bar for mobile and optionally desktop.

**Mobile Header (always shown <768px):**
```
┌─────────────────────────────────────────────┐
│ [☰]  [Logo]              [🔔] [Avatar ▼]   │
└─────────────────────────────────────────────┘
```

**Desktop Header (shown above content area):**
```
┌─────────────────────────────────────────────────────────────┐
│  Breadcrumbs / Page Title              [🔔] [🔍] [Avatar]  │
└─────────────────────────────────────────────────────────────┘
```

**Breadcrumb Support:**
- Pass breadcrumbs as prop to layout: `['Dashboard', 'Lehigh Region', 'Kanban']`
- Auto-generates links based on route names
- Last item is current page (no link)
- Uses DaisyUI `breadcrumbs` component

**DaisyUI Components Used:**
- `navbar`
- `breadcrumbs`
- `dropdown`
- `avatar`
- `btn` (ghost variant for icon buttons)
- `indicator` (for notification badge)

---

### 4. User Menu (`components/layouts/partials/user-menu.blade.php`)

Unified user dropdown used in both sidebar (desktop) and header (mobile).

**Menu Items:**
```
┌────────────────────────────┐
│ [Avatar] User Name         │
│          user@email.com    │
├────────────────────────────┤
│ ⚙️ Settings               │
│ 🎨 Appearance             │
│ 🔐 Two-Factor Auth        │
├────────────────────────────┤
│ 🚪 Log Out                │
└────────────────────────────┘
```

**DaisyUI Components Used:**
- `dropdown`
- `menu`
- `avatar`
- `divider`

---

### 5. Icon Component (`components/ui/icon.blade.php`)

Wrapper for Heroicons with consistent sizing and color handling.

```html
<!-- Usage -->
<x-ui.icon name="home" class="size-5" />
<x-ui.icon name="cog" size="lg" />
```

**Props:**
- `name` - Heroicon name (e.g., "home", "cog-6-tooth")
- `size` - sm|md|lg|xl (default: md)
- `variant` - outline|solid|mini (default: outline)

---

### 6. Tooltip Component (`components/ui/tooltip.blade.php`)

DaisyUI tooltip wrapper with consistent positioning.

```html
<!-- Usage -->
<x-ui.tooltip text="Click to save" position="top">
    <button class="btn">Save</button>
</x-ui.tooltip>
```

**DaisyUI Classes:**
- `tooltip`
- `tooltip-top`, `tooltip-bottom`, `tooltip-left`, `tooltip-right`
- `tooltip-primary`, `tooltip-secondary`, etc.

---

### 7. Alpine Dashboard State (`resources/js/alpine/dashboard-state.js`)

Global Alpine store for dashboard state management.

```javascript
Alpine.store('dashboard', {
    // Sidebar state
    sidebarOpen: false,
    sidebarCollapsed: false,

    // Region filter (global across dashboard)
    selectedRegion: null,

    // View preferences
    viewMode: 'cards', // cards | table | kanban

    // User customization (loaded from localStorage, synced to DB)
    customizations: {},

    // Methods
    toggleSidebar() { ... },
    setRegion(regionId) { ... },
    saveCustomization(key, value) { ... },
    loadFromStorage() { ... },
    syncToDatabase() { ... },
});
```

---

### 8. Livewire State Sync (`app/Livewire/Layouts/AppShellState.php`)

Livewire component for persisting Alpine state to database.

```php
class AppShellState extends Component
{
    // Receives state changes from Alpine via events
    #[On('dashboard-state-changed')]
    public function syncState(string $key, mixed $value): void
    {
        // Debounced save to user preferences
    }

    // Provides initial state to Alpine
    public function getInitialState(): array
    {
        return [
            'selectedRegion' => auth()->user()->default_region_id,
            'customizations' => $this->loadUserCustomizations(),
        ];
    }
}
```

---

## Implementation Steps

### Step 1: Create Base Files (No Functionality Yet)

1. Create folder structure
2. Create empty component files with basic props
3. Create Alpine store skeleton
4. Create Livewire component skeleton

### Step 2: Implement Sidebar

1. Build DaisyUI drawer structure
2. Add navigation menu with mock links
3. Add collapsible groups
4. Add user avatar section
5. Style with DaisyUI semantic colors
6. Add tooltip support for collapsed mode

### Step 3: Implement Header

1. Build mobile navbar
2. Add hamburger toggle (connected to sidebar)
3. Add user dropdown
4. Add notification indicator placeholder

### Step 4: Implement User Menu

1. Build dropdown structure
2. Add user info display
3. Add settings links
4. Add logout form
5. Extract to reusable component

### Step 5: Wire Up Alpine State

1. Initialize Alpine store on page load
2. Connect sidebar toggle to store
3. Add localStorage persistence
4. Connect to Livewire for DB sync

### Step 6: Refactor Theme System (Complete Rebuild)

The current theme system is broken and needs to be completely removed and rebuilt.

**Remove:**
1. Remove existing `themeManager` Alpine component from `app.js`
2. Remove `ThemeListener` Livewire component
3. Remove broken theme sync logic

**Rebuild:**
1. Create new `themeStore` Alpine store in `dashboard-state.js`
2. Implement clean theme detection (system preference, localStorage, user DB setting)
3. Add FOUC prevention inline script (keep simple, no Alpine dependency)
4. Create theme toggle component using DaisyUI `swap` or custom dropdown
5. Sync theme changes to database via Livewire (debounced)
6. Support themes: system, light, dark, ppl-light, ppl-dark, + other DaisyUI themes

**Theme Resolution Order:**
1. Check localStorage first (instant, no flash)
2. If "system", detect via `prefers-color-scheme`
3. On auth, merge with DB preference (DB wins if newer)

### Step 7: Create Test Route

1. Add temporary route `/test/app-shell`
2. Create test page using new layout
3. Verify all breakpoints work
4. Test sidebar toggle on mobile

---

## Files to Create

| File | Purpose |
|------|---------|
| `resources/views/components/layouts/app-shell.blade.php` | Main layout wrapper |
| `resources/views/components/layouts/partials/sidebar.blade.php` | Sidebar component |
| `resources/views/components/layouts/partials/sidebar-nav.blade.php` | Navigation items |
| `resources/views/components/layouts/partials/header.blade.php` | Top header bar |
| `resources/views/components/layouts/partials/user-menu.blade.php` | User dropdown |
| `resources/views/components/layouts/partials/mobile-drawer.blade.php` | Mobile drawer |
| `resources/views/components/ui/icon.blade.php` | Icon wrapper |
| `resources/views/components/ui/tooltip.blade.php` | Tooltip wrapper |
| `resources/views/components/ui/breadcrumbs.blade.php` | Breadcrumb navigation |
| `resources/views/components/ui/theme-toggle.blade.php` | Theme switcher dropdown |
| `resources/js/alpine/dashboard-state.js` | Alpine store (includes theme store) |
| `app/Livewire/Layouts/AppShellState.php` | State sync component |
| `resources/views/livewire/layouts/app-shell-state.blade.php` | Livewire view |
| `resources/views/test/app-shell-test.blade.php` | Test page |

## Files to Modify

| File | Change |
|------|--------|
| `resources/js/app.js` | Import dashboard-state.js, remove old themeManager |
| `routes/web.php` | Add test route (temporary) |
| `vite.config.js` | Ensure Alpine files are processed |

## Files to Remove

| File | Reason |
|------|--------|
| `resources/views/livewire/theme-listener.blade.php` | Replaced by new theme system |
| `app/Livewire/ThemeListener.php` | Replaced by AppShellState theme sync |
| `resources/views/components/desktop-user-menu.blade.php` | Replaced by unified user-menu.blade.php |
| `resources/views/components/utils/color-changer.blade.php` | Replaced by theme-toggle.blade.php |

**Note:** Legacy Flux layout files (`app.blade.php`, `app/sidebar.blade.php`, `app/header.blade.php`) are kept for now to support existing pages during migration.

---

## DaisyUI Components Reference

Components we'll use in this phase:

| Component | Usage |
|-----------|-------|
| `drawer` | Sidebar overlay on mobile |
| `menu` | Navigation list |
| `collapse` | Expandable nav groups |
| `navbar` | Header bar |
| `breadcrumbs` | Page navigation trail |
| `dropdown` | User menu |
| `avatar` | User display |
| `btn` | Icon buttons |
| `tooltip` | Help text |
| `badge` | Notification counts |
| `indicator` | Badge positioning |
| `divider` | Menu separators |
| `swap` | Theme toggle animation |

---

## Acceptance Criteria

### Layout & Sidebar
- [ ] Sidebar displays correctly on desktop (expanded, 256px)
- [ ] Sidebar can be collapsed to icons on desktop (64px)
- [ ] Sidebar collapsed state persists in localStorage
- [ ] Sidebar collapses to icons by default on tablet
- [ ] Sidebar hidden on mobile, opens as drawer overlay
- [ ] Hamburger menu toggles drawer on mobile
- [ ] Clicking outside drawer closes it on mobile
- [ ] User menu dropdown works in both sidebar and header
- [ ] All navigation links are present (mock routes OK)
- [ ] Tooltips appear on all icon-only buttons
- [ ] Responsive breakpoints match spec (mobile <768, tablet 768-1024, desktop >1024)

### Theme System (Refactored)
- [ ] Old theme system completely removed (ThemeListener, themeManager)
- [ ] New theme toggle component works
- [ ] Theme persists in localStorage (instant load, no FOUC)
- [ ] System theme detection works
- [ ] Theme syncs to database when changed
- [ ] All DaisyUI themes available (light, dark, ppl-light, ppl-dark, etc.)
- [ ] Theme changes apply immediately without page reload

### Breadcrumbs
- [ ] Breadcrumbs display correctly in header
- [ ] Last item shows as current page (no link)
- [ ] Breadcrumb links navigate correctly

### General
- [ ] No Flux components in the new layout files
- [ ] Only DaisyUI semantic colors used (no `blue-500`, etc.)
- [ ] Alpine store initializes correctly
- [ ] Existing pages still work with legacy Flux layout

---

## Testing Plan

### Manual Testing
1. Desktop: Verify sidebar fully expanded, navigation works
2. Resize to tablet: Verify sidebar collapses to icons
3. Resize to mobile: Verify sidebar hidden, hamburger appears
4. Mobile: Tap hamburger, verify drawer opens
5. Mobile: Tap outside drawer, verify it closes
6. Click user avatar: Verify dropdown appears
7. Change theme: Verify colors update throughout
8. Refresh page: Verify sidebar state persists

### Automated Testing (Feature Tests)
```php
// Tests to add
it('renders app shell layout on test page');
it('shows sidebar on desktop viewport');
it('hides sidebar on mobile viewport');
it('displays user menu with correct user info');
it('preserves theme preference across navigation');
```

---

## Migration Notes

This phase creates a **parallel** layout system. The existing Flux-based layouts remain functional for:
- Authentication pages
- Settings pages
- Admin pages

Migration of these pages to the new layout will happen in future phases. This approach allows:
1. Incremental migration without breaking existing functionality
2. Side-by-side comparison during development
3. Easy rollback if issues arise

---

## Decisions Made

| Question | Decision |
|----------|----------|
| Sidebar width | Standard 256px expanded, 64px collapsed. Tablet defaults to collapsed. Desktop can toggle between both. |
| Logo link | Links to user's default home dashboard (or system default if not set) |
| Notification bell | Placeholder included now, wired up in future phase |
| Search | Placeholder included now, wired up in future phase |
| Breadcrumbs | Included in this phase |
| Theme system | Complete refactor - remove broken system, rebuild clean |

---

## Approval Status

**Status:** Ready for final approval

**Changes incorporated:**
- [x] Goal #7 added: Remove unused/replaced elements
- [x] Step 6 rewritten: Theme system complete refactor
- [x] Sidebar widths specified with tablet narrower default
- [x] Desktop sidebar collapsible to icons
- [x] Logo links to user's home dashboard
- [x] Notification/Search as placeholders
- [x] Breadcrumb support added
- [x] Files to Remove section added
- [x] Acceptance criteria expanded

**To approve:** Reply "Approved" to proceed with implementation.

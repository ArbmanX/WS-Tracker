{{--
    User Preferences Initialization

    This component outputs a script tag that makes user preferences available
    to Alpine.js stores before they initialize. This enables:

    1. Database preferences to be restored on page load
    2. Cross-device sync (preferences persist to DB, not just localStorage)
    3. Guest fallback (null preferences = use localStorage only)

    Include in <head> BEFORE Alpine loads.
--}}

@auth
@php
    $prefs = [
        'theme' => auth()->user()->theme_preference ?? 'system',
        'dashboard' => auth()->user()->dashboard_preferences ?? [],
        'defaultRegionId' => auth()->user()->default_region_id,
    ];
@endphp
<script>
    window.__userPreferences = @json($prefs);
</script>
@else
<script>
    window.__userPreferences = null;
</script>
@endauth

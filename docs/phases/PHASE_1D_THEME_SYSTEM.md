# Phase 1D: Theme System

> **Goal:** Implement DaisyUI theme system with PPL branding and user preferences.
> **Estimated Time:** 1 day
> **Dependencies:** Phase 0 complete (NPM packages)
> **Can Run In Parallel With:** Phase 1B, 1C

---

## Status: ✅ Complete

| Item | Status | Notes |
|------|--------|-------|
| PPL brand theme CSS | ✅ Done | `ppl-light` and `ppl-dark` in app.css |
| Theme switcher Alpine | ✅ Done | `color-changer.blade.php` + `themeManager` in app.js |
| User preference storage | ✅ Done | `theme_preference` column + ThemeListener component |
| System detection | ✅ Done | Dynamic layout with FOUC prevention |

---

## Brand Colors

### PPL Electric Utilities

| Color Name | Hex | Usage |
|------------|-----|-------|
| Cyan Cornflower Blue | `#1882C5` | Primary actions, links |
| St. Patrick's Blue | `#28317E` | Headers, emphasis |

### Asplundh (Contractor)

| Color Name | Hex | Usage |
|------------|-----|-------|
| Orient Blue | `#00598D` | Secondary elements |
| Red Damask | `#E27434` | Accent, warnings |

---

## Checklist

### Theme CSS
- [x] Create PPL brand themes in `resources/css/app.css` (`ppl-light`, `ppl-dark`)
- [x] Define all DaisyUI color variables in OKLCH format
- [x] Configure 18 themes in DaisyUI plugin

### Alpine Component
- [x] Create `themeManager` Alpine component in `resources/js/app.js`
- [x] Implement system preference detection
- [x] Implement localStorage persistence with `$persist`
- [x] Add Livewire dispatch for server sync

### Livewire Integration
- [x] Create `ThemeListener` Livewire component for global event handling
- [x] Update `Appearance` Livewire component with full theme management
- [x] Add `theme_preference` column to users table (migration)
- [x] Sync localStorage with database preference via Livewire events

### Blade Components
- [x] Update `color-changer.blade.php` with categorized themes
- [x] Add dynamic `data-theme` attribute to layout
- [x] Add FOUC prevention script in head
- [x] Add `user-theme` meta tag for server-side preference

### Testing
- [x] 12 Pest tests for theme functionality
- [x] Test theme persistence across sessions
- [x] Test ThemeListener event handling

---

## File Structure (Implemented)

```
resources/
├── css/
│   └── app.css                            # ✅ PPL themes defined inline (ppl-light, ppl-dark)
├── js/
│   └── app.js                             # ✅ themeManager Alpine component
└── views/
    ├── components/
    │   ├── layouts/app/sidebar.blade.php  # ✅ Dynamic data-theme + FOUC prevention
    │   └── utils/color-changer.blade.php  # ✅ Theme dropdown with categories
    ├── livewire/
    │   ├── settings/appearance.blade.php  # ✅ Full theme settings page
    │   └── theme-listener.blade.php       # ✅ Global event listener
    └── partials/
        └── head.blade.php                 # ✅ user-theme meta tag

app/Livewire/
├── Settings/Appearance.php                # ✅ Theme settings component
└── ThemeListener.php                      # ✅ Global theme event handler

database/migrations/
└── *_add_theme_preference_to_users_table.php  # ✅ User preference column

tests/Feature/Settings/
└── AppearanceTest.php                     # ✅ 12 tests for theme system
```

---

## Key Implementation Details

### PPL Brand Theme CSS

```css
/* resources/css/themes/ppl-brand.css */
[data-theme="ppl-brand"] {
  color-scheme: light;

  /* Base colors */
  --color-base-100: oklch(100% 0 0);
  --color-base-200: oklch(98% 0.005 250);
  --color-base-300: oklch(95% 0.01 250);
  --color-base-content: oklch(25% 0.02 250);

  /* PPL Primary - Cyan Cornflower Blue #1882C5 */
  --color-primary: oklch(55% 0.15 230);
  --color-primary-content: oklch(98% 0.01 230);

  /* PPL Secondary - St. Patrick's Blue #28317E */
  --color-secondary: oklch(35% 0.15 270);
  --color-secondary-content: oklch(95% 0.02 270);

  /* Asplundh Accent - Red Damask #E27434 */
  --color-accent: oklch(65% 0.18 50);
  --color-accent-content: oklch(20% 0.05 50);

  /* Neutral - Asplundh Orient Blue #00598D */
  --color-neutral: oklch(40% 0.12 240);
  --color-neutral-content: oklch(95% 0.01 240);

  /* Semantic colors */
  --color-info: oklch(65% 0.15 230);
  --color-info-content: oklch(25% 0.05 230);
  --color-success: oklch(70% 0.17 145);
  --color-success-content: oklch(25% 0.05 145);
  --color-warning: oklch(80% 0.16 85);
  --color-warning-content: oklch(30% 0.08 85);
  --color-error: oklch(65% 0.2 25);
  --color-error-content: oklch(95% 0.02 25);

  /* Radii and sizing */
  --radius-selector: 0.375rem;
  --radius-field: 0.25rem;
  --radius-box: 0.5rem;
  --border: 1px;
  --depth: 1;
  --noise: 0;
}
```

### Theme Switcher Alpine Component

```javascript
// resources/js/theme-switcher.js
document.addEventListener('alpine:init', () => {
  Alpine.data('themeSwitcher', () => ({
    theme: localStorage.getItem('theme') || 'system',
    themes: ['light', 'dark', 'ppl-brand', 'corporate', 'business', 'dim'],

    init() {
      this.applyTheme();
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (this.theme === 'system') this.applyTheme();
      });
    },

    setTheme(newTheme) {
      this.theme = newTheme;
      localStorage.setItem('theme', newTheme);
      this.applyTheme();

      // Sync to server via Livewire
      if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('theme-changed', { theme: newTheme });
      }
    },

    applyTheme() {
      const html = document.documentElement;
      if (this.theme === 'system') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        html.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
      } else {
        html.setAttribute('data-theme', this.theme);
      }
    },

    get currentTheme() {
      return this.theme === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : this.theme;
    },

    get themeIcon() {
      const icons = {
        'system': 'computer-desktop',
        'light': 'sun',
        'dark': 'moon',
        'ppl-brand': 'building-office',
        'corporate': 'briefcase',
        'business': 'chart-bar',
        'dim': 'eye',
      };
      return icons[this.theme] || 'paint-brush';
    }
  }));
});
```

### Import in app.js

```javascript
// resources/js/app.js
import './bootstrap';
import 'livewire-sortable';
import ApexCharts from 'apexcharts';
import './theme-switcher';

window.ApexCharts = ApexCharts;
```

### Import Theme in app.css

```css
/* resources/css/app.css */
@import "tailwindcss";
@plugin "daisyui";
@import "./themes/ppl-brand.css";
```

### Theme Switcher Blade Component

```blade
{{-- resources/views/components/theme-switcher.blade.php --}}
<div x-data="themeSwitcher" class="dropdown dropdown-end">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
        <flux:icon :name="themeIcon" class="size-5" />
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
        <li class="menu-title">
            <span>Theme</span>
        </li>
        <template x-for="t in themes" :key="t">
            <li>
                <a
                    @click="setTheme(t)"
                    :class="{ 'active': theme === t }"
                    x-text="t.charAt(0).toUpperCase() + t.slice(1).replace('-', ' ')"
                ></a>
            </li>
        </template>
        <li class="border-t border-base-300 mt-2 pt-2">
            <a @click="setTheme('system')" :class="{ 'active': theme === 'system' }">
                <flux:icon name="computer-desktop" class="size-4" />
                System
            </a>
        </li>
    </ul>
</div>
```

### ThemeSelector Livewire Component

```php
<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\On;

class ThemeSelector extends Component
{
    public string $theme;

    public function mount(): void
    {
        $this->theme = auth()->user()->theme_preference ?? 'system';
    }

    #[On('theme-changed')]
    public function updateTheme(string $theme): void
    {
        $this->theme = $theme;

        auth()->user()->update([
            'theme_preference' => $theme,
        ]);
    }

    public function render()
    {
        return view('livewire.settings.theme-selector');
    }
}
```

### User Migration (Add theme column)

```php
// Migration: add_theme_preference_to_users_table
Schema::table('users', function (Blueprint $table) {
    $table->string('theme_preference')->nullable()->default('system');
});
```

### Layout Integration

```blade
{{-- resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        theme: localStorage.getItem('theme') || '{{ auth()->user()?->theme_preference ?? 'system' }}'
    }"
    x-init="
        if (theme === 'system') {
            $el.setAttribute('data-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } else {
            $el.setAttribute('data-theme', theme);
        }
    "
    :data-theme="theme === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme"
>
    <!-- ... -->
</html>
```

---

## Available Themes

| Theme | Description |
|-------|-------------|
| `light` | Default light theme |
| `dark` | Default dark theme |
| `ppl-brand` | Custom PPL Electric/Asplundh branded |
| `corporate` | Professional light theme |
| `business` | Business-focused dark theme |
| `dim` | Dimmed dark theme |
| `system` | Auto-detect OS preference |

---

## Testing Requirements

```php
// tests/Feature/ThemeTest.php
it('saves theme preference to database', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeSelector::class)
        ->dispatch('theme-changed', theme: 'dark');

    expect($user->fresh()->theme_preference)->toBe('dark');
});

it('loads theme preference on login', function () {
    $user = User::factory()->create(['theme_preference' => 'ppl-brand']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSee('data-theme');
});

// tests/Browser/ThemeSwitchingTest.php (Pest 4)
it('switches themes via dropdown', function () {
    $user = User::factory()->create();

    $page = visit('/dashboard')->actingAs($user);

    $page->click('[data-testid="theme-switcher"]')
        ->click('[data-theme-option="dark"]')
        ->assertAttribute('html', 'data-theme', 'dark');
});

it('persists theme across page loads', function () {
    $user = User::factory()->create(['theme_preference' => 'ppl-brand']);

    $page = visit('/dashboard')->actingAs($user);
    $page->assertAttribute('html', 'data-theme', 'ppl-brand');

    $page = visit('/settings');
    $page->assertAttribute('html', 'data-theme', 'ppl-brand');
});

it('respects system preference when set to auto', function () {
    $user = User::factory()->create(['theme_preference' => 'system']);

    $page = visit('/dashboard')
        ->actingAs($user)
        ->setColorScheme('dark');

    $page->assertAttribute('html', 'data-theme', 'dark');
});
```

---

## Next Phase

Once all items are checked, proceed to **[Phase 1E: Dashboard UI](./PHASE_1E_DASHBOARD_UI.md)**.

**Note:** This phase can run in parallel with Phase 1B and 1C since it only depends on Phase 0.

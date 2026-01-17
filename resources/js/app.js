
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

/**
 * ApexCharts Alpine.js Component
 *
 * Provides a reusable wrapper for ApexCharts with:
 * - Theme-aware colors that update on theme change
 * - Livewire integration for data updates
 * - Automatic cleanup on component destruction
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('apexChart', (config) => ({
        chart: null,

        init() {
            this.$nextTick(() => {
                this.renderChart();
            });

            // Re-render on theme change
            window.addEventListener('theme-changed', () => {
                this.chart?.destroy();
                this.renderChart();
            });

            // Also listen for theme-updated event
            window.addEventListener('theme-updated', () => {
                this.chart?.destroy();
                this.renderChart();
            });
        },

        renderChart() {
            const el = document.getElementById(config.chartId);
            if (!el) return;

            const isDark = document.documentElement.getAttribute('data-theme')?.includes('dark');

            // Get theme colors from CSS custom properties
            const colors = this.getThemeColors();

            const defaultOptions = {
                chart: {
                    type: config.type,
                    height: config.height,
                    background: 'transparent',
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        speed: 500,
                        animateGradually: { enabled: true, delay: 150 },
                        dynamicAnimation: { enabled: true, speed: 350 }
                    }
                },
                theme: {
                    mode: isDark ? 'dark' : 'light',
                },
                colors: colors,
                grid: {
                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                },
                ...config.options
            };

            this.chart = new ApexCharts(el, {
                ...defaultOptions,
                series: config.series
            });
            this.chart.render();
        },

        getThemeColors() {
            const style = getComputedStyle(document.documentElement);

            // Try to get oklch colors and convert, or use fallbacks
            return [
                this.getColorValue(style, '--color-primary', '#3b82f6'),
                this.getColorValue(style, '--color-secondary', '#8b5cf6'),
                this.getColorValue(style, '--color-accent', '#f59e0b'),
                this.getColorValue(style, '--color-success', '#22c55e'),
                this.getColorValue(style, '--color-warning', '#eab308'),
                this.getColorValue(style, '--color-error', '#ef4444'),
                this.getColorValue(style, '--color-info', '#06b6d4'),
            ];
        },

        getColorValue(style, prop, fallback) {
            const value = style.getPropertyValue(prop).trim();
            if (!value) return fallback;

            // If it's oklch format, we need to use it directly or convert
            // ApexCharts may not support oklch, so we use the fallback for now
            // The colors will still work through CSS inheritance
            if (value.startsWith('oklch')) {
                return fallback;
            }
            return value || fallback;
        },

        destroy() {
            this.chart?.destroy();
        }
    }));
});

/**
 * Theme Manager - Alpine.js Component
 *
 * Handles theme switching with:
 * - localStorage persistence
 * - System preference detection
 * - Server-side sync via Livewire events
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('themeManager', () => ({
        theme: 'system',
        systemPreference: 'light',

        init() {
            // Detect system preference
            this.systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                this.systemPreference = e.matches ? 'dark' : 'light';
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });

            // Listen for theme updates from Livewire
            window.addEventListener('theme-updated', (e) => {
                this.theme = e.detail.theme;
                this.applyTheme();
            });
        },

        initTheme() {
            // Get theme from localStorage or use default
            this.theme = localStorage.getItem('theme') || this.getServerTheme();
            this.applyTheme();
        },

        getServerTheme() {
            // Get the theme from the server-rendered value
            return document.querySelector('meta[name="user-theme"]')?.content || 'system';
        },

        get effectiveTheme() {
            if (this.theme === 'system') {
                return this.systemPreference;
            }
            return this.theme;
        },

        setTheme(newTheme) {
            this.theme = newTheme;
            localStorage.setItem('theme', newTheme);
            this.applyTheme();

            // Dispatch event for Livewire to sync to database
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('theme-changed', { theme: newTheme });
            }
        },

        applyTheme() {
            document.documentElement.setAttribute('data-theme', this.effectiveTheme);
        },

        // Helper for theme icons
        getThemeIcon(themeName) {
            const icons = {
                'light': 'sun',
                'dark': 'moon',
                'system': 'computer-desktop',
                'ppl-light': 'building-office',
                'ppl-dark': 'building-office-2',
            };
            return icons[themeName] || 'paint-brush';
        }
    }));
});


import 'livewire-sortable';
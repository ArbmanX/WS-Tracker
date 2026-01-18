/**
 * WS-Tracker Frontend Application
 *
 * Entry point for all frontend JavaScript.
 */

// Alpine.js Dashboard State Stores
// Must be imported before Alpine starts (loaded via Livewire)
import './alpine/dashboard-state.js';

/**
 * Livewire Sortable Plugin
 *
 * Must be loaded AFTER Livewire is initialized.
 * We use dynamic import triggered by livewire:init event.
 */
document.addEventListener('livewire:init', () => {
    import('livewire-sortable');
});

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


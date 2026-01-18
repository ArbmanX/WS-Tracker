<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="ppl-dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WS-Tracker - Vegetation Management Dashboard for PPL Electric Utilities">

    <title>WS-Tracker | Vegetation Management Dashboard</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* Animated gradient background */
        .hero-gradient {
            background: linear-gradient(135deg,
                    oklch(55% 0.15 230) 0%,
                    oklch(35% 0.12 270) 50%,
                    oklch(40% 0.10 240) 100%);
            background-size: 200% 200%;
            animation: gradient-shift 15s ease infinite;
        }

        @keyframes gradient-shift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Floating animation for decorative elements */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        .float-animation-delayed {
            animation: float 6s ease-in-out infinite;
            animation-delay: -3s;
        }

        /* Subtle grid pattern overlay */
        .grid-pattern {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Card hover effects */
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.15);
        }

        /* Stats counter animation */
        @keyframes count-up {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-animate {
            animation: count-up 0.6s ease-out forwards;
        }
    </style>
</head>

<body class="min-h-screen bg-base-100">
    <!-- Navigation -->
    <div class="navbar bg-base-100/80 backdrop-blur-lg fixed top-0 z-50 border-b border-base-200">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow-lg">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#stats">Statistics</a></li>
                    <li><a href="#about">About</a></li>
                </ul>
            </div>
            <a href="/" class="btn btn-ghost text-xl gap-2">
                <!-- Tree/Power Line Logo -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M12 2L8 8h3v4H8l4 6 4-6h-3V8h3L12 2z" />
                    <path d="M12 18v4" />
                    <path d="M8 22h8" />
                </svg>
                <span class="font-bold text-secondary">WS-Tracker</span>
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1 gap-2">
                <li><a href="#features" class="font-medium">Features</a></li>
                <li><a href="#stats" class="font-medium">Statistics</a></li>
                <li><a href="#about" class="font-medium">About</a></li>
            </ul>
        </div>
        <div class="navbar-end gap-2">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">
                        Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('onboarding') }}" class="btn btn-ghost btn-sm">First Time?</a>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Sign In</a>
                @endauth
            @endif
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-gradient grid-pattern min-h-screen pt-16">
        <div class="hero min-h-screen">
            <div class="hero-content text-center text-neutral-content">
                <div class="max-w-4xl">
                    <!-- Floating decorative elements -->
                    <div class="absolute top-32 left-10 opacity-20 float-animation hidden lg:block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="absolute top-48 right-20 opacity-20 float-animation-delayed hidden lg:block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>

                    <!-- Badge -->
                    <div class="badge badge-outline border-white/30 text-white/90 gap-2 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        PPL Electric Utilities
                    </div>

                    <!-- Main Heading -->
                    <h1 class="text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                        Vegetation Management
                        <span class="block text-accent">Made Simple</span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="text-xl lg:text-2xl text-white/80 mb-10 max-w-2xl mx-auto leading-relaxed">
                        Track circuit progress, monitor planner productivity, and manage vegetation assessments
                        with real-time WorkStudio integration.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="btn btn-lg bg-white text-secondary hover:bg-white/90 border-0 gap-2">
                                Go to Dashboard
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('onboarding') }}"
                                class="btn btn-lg bg-white text-secondary hover:bg-white/90 border-0 gap-2">
                                First Time? Set Up Account
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <a href="{{ route('login') }}"
                                class="btn btn-lg btn-outline border-white/30 text-white hover:bg-white/10 hover:border-white/50">
                                Sign In
                            </a>
                        @endauth
                        <a href="#features"
                            class="btn btn-lg btn-outline border-white/30 text-white hover:bg-white/10 hover:border-white/50">
                            Learn More
                        </a>
                    </div>

                    <!-- Scroll indicator -->
                    <div class="mt-16 animate-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-white/50" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="py-20 bg-base-200">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-base-content mb-4">Real-Time Tracking</h2>
                <p class="text-base-content/70 max-w-xl mx-auto">
                    Monitor vegetation assessment progress across all regions with live data from WorkStudio.
                </p>
            </div>

            <div class="stats stats-vertical lg:stats-horizontal shadow-lg w-full bg-base-100">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div class="stat-title">Regions</div>
                    <div class="stat-value text-primary">4</div>
                    <div class="stat-desc">PPL Service Areas</div>
                </div>

                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="stat-title">Active Circuits</div>
                    <div class="stat-value text-secondary">150+</div>
                    <div class="stat-desc">Currently in progress</div>
                </div>

                <div class="stat">
                    <div class="stat-figure text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Planners</div>
                    <div class="stat-value text-accent">40+</div>
                    <div class="stat-desc">Field professionals</div>
                </div>

                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Unit Types</div>
                    <div class="stat-value text-success">44</div>
                    <div class="stat-desc">Vegetation work categories</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-base-100">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <div class="badge badge-primary badge-outline mb-4">Features</div>
                <h2 class="text-4xl font-bold text-base-content mb-4">Everything You Need</h2>
                <p class="text-base-content/70 max-w-2xl mx-auto">
                    Comprehensive tools for vegetation management oversight, from circuit tracking to planner
                    productivity analysis.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Kanban Board -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-primary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">Kanban Workflow</h3>
                        <p class="text-base-content/70">
                            Drag-and-drop circuit cards through workflow stages: Active, Pending Permissions, QC,
                            Rework, and Closed.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Drag & Drop</div>
                            <div class="badge badge-outline badge-sm">Real-time</div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Regional Views -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-secondary/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">Regional Hierarchy</h3>
                        <p class="text-base-content/70">
                            View data at any level: Global overview, regional breakdowns, circuit details, or individual
                            planner metrics.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Multi-level</div>
                            <div class="badge badge-outline badge-sm">Filterable</div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Aggregate Tracking -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-accent/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">Aggregate Analytics</h3>
                        <p class="text-base-content/70">
                            Track linear feet, acres, tree counts, and unit totals. Daily snapshots enable trend
                            analysis over time.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Historical</div>
                            <div class="badge badge-outline badge-sm">Charts</div>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: WorkStudio Sync -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-info/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-info" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">WorkStudio Integration</h3>
                        <p class="text-base-content/70">
                            Automatic sync with WorkStudio GIS. Active circuits update twice daily, others weekly. Never
                            miss a change.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Automated</div>
                            <div class="badge badge-outline badge-sm">Scheduled</div>
                        </div>
                    </div>
                </div>

                <!-- Feature 5: Planner Metrics -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-success/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-success" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">Planner Productivity</h3>
                        <p class="text-base-content/70">
                            Monitor individual planner output with daily aggregates. Track circuits worked, units
                            assessed, and productivity trends.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Per-planner</div>
                            <div class="badge badge-outline badge-sm">Trending</div>
                        </div>
                    </div>
                </div>

                <!-- Feature 6: Role-Based Access -->
                <div class="card bg-base-100 shadow-xl border border-base-200 feature-card">
                    <div class="card-body">
                        <div class="w-14 h-14 bg-warning/10 rounded-xl flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-warning" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="card-title text-base-content">Role-Based Security</h3>
                        <p class="text-base-content/70">
                            Four permission levels: Admin, Manager, Supervisor, and Planner. Control who sees and edits
                            what data.
                        </p>
                        <div class="card-actions justify-start mt-4">
                            <div class="badge badge-outline badge-sm">Secure</div>
                            <div class="badge badge-outline badge-sm">Granular</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About / CTA Section -->
    <section id="about" class="py-20 bg-secondary text-secondary-content">
        <div class="container mx-auto px-6">
            <div class="flex flex-col lg:flex-row items-center gap-12">
                <div class="lg:w-1/2">
                    <h2 class="text-4xl font-bold mb-6">Built for PPL Electric Utilities</h2>
                    <p class="text-lg opacity-90 mb-6">
                        WS-Tracker is a management dashboard designed specifically for vegetation maintenance oversight.
                        It integrates with WorkStudio to provide real-time visibility into circuit progress and planner
                        productivity.
                    </p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Aggregated views - no individual unit records</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Management oversight, not field planning</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Historical tracking with daily snapshots</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Secure, role-based access control</span>
                        </li>
                    </ul>
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-accent btn-lg">
                            Sign In to Get Started
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    @endguest
                </div>
                <div class="lg:w-1/2">
                    <!-- Mockup Card -->
                    <div class="mockup-window bg-base-300 shadow-2xl">
                        <div class="bg-base-200 px-4 py-8">
                            <div class="flex flex-col gap-4">
                                <div class="flex gap-4">
                                    <div class="skeleton h-24 w-24 rounded-lg bg-primary/20"></div>
                                    <div class="flex flex-col gap-2 flex-1">
                                        <div class="skeleton h-4 w-3/4 bg-base-content/20"></div>
                                        <div class="skeleton h-4 w-1/2 bg-base-content/20"></div>
                                        <div class="skeleton h-4 w-2/3 bg-base-content/20"></div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="skeleton h-20 flex-1 rounded-lg bg-success/20"></div>
                                    <div class="skeleton h-20 flex-1 rounded-lg bg-warning/20"></div>
                                    <div class="skeleton h-20 flex-1 rounded-lg bg-error/20"></div>
                                </div>
                                <div class="skeleton h-32 w-full rounded-lg bg-base-content/10"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer footer-center bg-base-200 text-base-content p-10">
        <aside>
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L8 8h3v4H8l4 6 4-6h-3V8h3L12 2z" />
                    <path d="M12 18v4" />
                    <path d="M8 22h8" />
                </svg>
                <span class="text-xl font-bold text-secondary">WS-Tracker</span>
            </div>
            <p class="text-base-content/70">
                Vegetation Management Dashboard<br>
                PPL Electric Utilities &bull; Asplundh Tree Expert Co.
            </p>
        </aside>
        <nav>
            <div class="grid grid-flow-col gap-4">
                <a href="#features" class="link link-hover">Features</a>
                <a href="#stats" class="link link-hover">Statistics</a>
                <a href="#about" class="link link-hover">About</a>
            </div>
        </nav>
        <aside>
            <p class="text-sm text-base-content/50">
                &copy; {{ date('Y') }} WS-Tracker. Internal use only.
            </p>
        </aside>
    </footer>

    <!-- Theme Controller (hidden, for JS control) -->
    <script>
        // Check for system preference or saved preference
        const savedTheme = localStorage.getItem('theme') || 'ppl-light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', (event) => {
                if (event.matches) {
                    console.log('System preference changed to dark mode');
                } else {
                    console.log('System preference changed to light mode');
                }
            });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>

</html>

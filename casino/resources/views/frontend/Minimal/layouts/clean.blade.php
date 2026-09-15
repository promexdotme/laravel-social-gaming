<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
    <!-- Progressive Web App (PWA) Meta & Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b0e14">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Casino du Liban">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <title>@yield('page-title', 'Casino du Liban - Premier Social Gaming')</title>

    <!-- Tailwind CSS CDN with Forms & Container Queries -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <style>
        :root {
            --sat: env(safe-area-inset-top);
            --sar: env(safe-area-inset-right);
            --sab: env(safe-area-inset-bottom);
            --sal: env(safe-area-inset-left);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #0b0e14;
            color: #f8fafc;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(16, 185, 129, 0.07) 0%, transparent 45%),
                radial-gradient(circle at 90% 90%, rgba(6, 182, 212, 0.05) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.03) 0%, transparent 60%);
            min-height: 100vh;
            min-height: 100dvh;
        }
        .font-mono-jet {
            font-family: 'JetBrains Mono', monospace;
        }
        /* Glass & Card Surfaces */
        .glass-card {
            background: rgba(18, 22, 32, 0.75);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: 0 4px 24px -1px rgba(0, 0, 0, 0.35);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }
        .glass-panel {
            background: rgba(15, 19, 28, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        /* Subtle Luxury Glows */
        .glow-emerald {
            box-shadow: 0 0 24px rgba(16, 185, 129, 0.25);
        }
        .glow-gold {
            box-shadow: 0 0 24px rgba(245, 158, 11, 0.25);
        }
        .glow-cyan {
            box-shadow: 0 0 24px rgba(6, 182, 212, 0.25);
        }
        /* Custom Ultra-Clean Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #0b0e14;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(16, 185, 129, 0.5);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Modal Backdrop & Dialog */
        .modal {
            display: none !important;
            position: fixed;
            z-index: 99999;
            inset: 0;
            background: rgba(5, 7, 11, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal.active, .modal.show {
            display: flex !important;
        }
        .modal-content {
            background: #121622;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 28px;
            max-width: 480px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7);
            color: #f8fafc;
        }
        @media (max-width: 640px) {
            .modal-content {
                padding: 20px;
                border-radius: 20px;
            }
        }
        .close-modal {
            position: absolute;
            top: 18px;
            right: 20px;
            font-size: 22px;
            color: #94a3b8;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.2s;
        }
        .close-modal:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.15);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 11px;
            margin-bottom: 6px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            background: #0b0e14;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        .btn-primary {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        /* Mobile Safe-Area Utilities */
        .pb-safe {
            padding-bottom: calc(env(safe-area-inset-bottom, 16px) + 64px);
        }
    </style>

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        background: "#0b0e14",
                        surface: "#121622",
                        "surface-card": "#161c2b",
                        "surface-elevated": "#1c2438",
                        "surface-border": "rgba(255, 255, 255, 0.08)",
                        primary: {
                            DEFAULT: "#10b981",
                            dark: "#059669",
                            light: "#34d399",
                            50: "#ecfdf5",
                            100: "#d1fae5",
                            500: "#10b981",
                            600: "#059669",
                            700: "#047857"
                        },
                        secondary: {
                            DEFAULT: "#06b6d4",
                            dark: "#0891b2",
                            light: "#22d3ee"
                        },
                        accent: {
                            gold: "#f59e0b",
                            amber: "#d97706",
                            rose: "#f43f5e",
                            purple: "#a855f7"
                        },
                        "on-surface": "#f8fafc",
                        "on-surface-muted": "#94a3b8",
                        "on-surface-subtle": "#64748b"
                    },
                    spacing: {
                        gutter: "16px",
                        margin: "24px",
                        "sidebar-width": "260px"
                    }
                }
            }
        };
    </script>
    @yield('styles')
</head>
<body class="text-on-surface bg-background flex flex-col lg:flex-row min-h-screen custom-scrollbar w-full overflow-x-hidden antialiased selection:bg-primary/20 selection:text-primary">

    <!-- Navigation (Sidebar + Mobile Header + Mobile Sheet + Bottom Dock) -->
    @include('frontend.Minimal.partials.navbar')

    <!-- Main Content Area -->
    <main class="flex-1 w-full min-w-0 min-h-screen relative flex flex-col pb-28 lg:pb-14">
        <div class="px-3.5 sm:px-6 md:px-10 lg:px-12 py-5 sm:py-8 md:py-10 space-y-6 sm:space-y-8 md:space-y-10 max-w-7xl w-full min-w-0 mx-auto">
            @yield('content')
        </div>
    </main>

    <!-- Global Modals (Auth, WhatsApp OTP, Profile, Wallet Refill) -->
    @include('frontend.Minimal.partials.modals')

    <script src="/frontend/Default/js/jquery-3.4.1.min.js"></script>
    <script src="/minimal/js/app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('PWA ServiceWorker registered cleanly!');
                }).catch(function(err) {
                    console.log('PWA ServiceWorker error: ', err);
                });
            });
        }

        // Automatic Local Timezone Formatter for <time class="local-time" datetime="...">
        document.addEventListener('DOMContentLoaded', function() {
            const timeElems = document.querySelectorAll('time.local-time');
            timeElems.forEach(elem => {
                const isoStr = elem.getAttribute('datetime');
                if (!isoStr) return;
                try {
                    const d = new Date(isoStr);
                    const fmt = elem.dataset.format || 'time';
                    if (fmt === 'time') {
                        elem.innerText = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } else if (fmt === 'full') {
                        elem.innerText = d.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    }
                } catch (err) {
                    console.error('Timezone format error:', err);
                }
            });
        });
    </script>
    @yield('scripts')
</body>
</html>

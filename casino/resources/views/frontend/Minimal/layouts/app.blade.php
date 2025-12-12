<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title') - {{ settings('app_name') }}</title>
    <link rel="stylesheet" href="/minimal/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @yield('styles')
</head>

<body>
    <div class="app-container">
        @include('frontend.Minimal.partials.navbar')

        <main class="main-content">
            @yield('content')
        </main>

        <footer class="main-footer">
            <p>&copy; {{ date('Y') }} {{ settings('app_name') }}. All rights reserved.</p>
        </footer>
    </div>

    @include('frontend.Minimal.partials.modals')

    <script src="/frontend/Default/js/jquery-3.4.1.min.js"></script>
    <script src="/minimal/js/app.js"></script>
    @yield('scripts')
</body>

</html>
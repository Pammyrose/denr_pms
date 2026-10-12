<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ config('app.name', 'Laravel') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        .progress-trend-chart {
            display: grid;
            grid-template-columns: repeat(12, minmax(34px, 1fr));
            align-items: end;
            gap: 0.75rem;
            min-height: 260px;
            padding: 1rem 0.25rem 0;
        }

        .progress-trend-chart.is-quarterly {
            grid-template-columns: repeat(4, minmax(58px, 1fr));
        }

        .progress-trend-item {
            display: grid;
            grid-template-rows: 2rem 190px 1.5rem;
            gap: 0.5rem;
            min-width: 0;
            text-align: center;
        }

        .progress-trend-value {
            color: #1f2937;
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1rem;
            white-space: nowrap;
        }

        .progress-trend-bar-track {
            align-items: end;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem 0.5rem 0.25rem 0.25rem;
            display: flex;
            height: 190px;
            overflow: hidden;
            position: relative;
        }

        .progress-trend-bar-track::before {
            background-image: linear-gradient(to top, rgba(148, 163, 184, 0.22) 1px, transparent 1px);
            background-size: 100% 25%;
            content: "";
            inset: 0;
            pointer-events: none;
            position: absolute;
        }

        .progress-trend-bar {
            background: linear-gradient(180deg, #fb7185 0%, #dc2626 100%);
            border-radius: 0.45rem 0.45rem 0 0;
            min-height: 3px;
            position: relative;
            transition: height 180ms ease;
            width: 100%;
        }

        .progress-trend-label {
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1rem;
        }

        .progress-trend-empty {
            align-items: center;
            color: #6b7280;
            display: flex;
            justify-content: center;
            min-height: 180px;
        }

        .progress-trend-toggle .btn {
            min-width: 92px;
        }

        .progress-trend-toggle .btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .progress-trend-chart {
                grid-template-columns: repeat(12, minmax(48px, 1fr));
                overflow-x: auto;
            }

            .progress-trend-chart.is-quarterly {
                grid-template-columns: repeat(4, minmax(54px, 1fr));
            }

            .progress-trend-item {
                grid-template-rows: 2rem 150px 1.5rem;
            }

            .progress-trend-bar-track {
                height: 150px;
            }
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
</head>
<body class="dashboard-page">

    <!-- Top navigation bar (full width) -->
    @include('components.nav')

    <!-- Sidebar + Main Content (side-by-side) -->
    <div class="d-flex">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main content wrapper -->
        <main class="dashboard-main flex-grow-1 p-4 bg-gradient-to-b from-gray-50 to-white">
            @include('components.dashboard_content')
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Optional: mobile sidebar toggle script -->
    <script>
        document.getElementById('toggleSidebar')?.addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('d-none');
        });
    </script>
    <script src="{{ asset('js/dashboard-performance.js') }}"></script>
    <script src="{{ asset('js/dashboard-trend.js') }}"></script>
    <script src="{{ asset('js/dashboard-filters.js') }}"></script>
</body>
</html>

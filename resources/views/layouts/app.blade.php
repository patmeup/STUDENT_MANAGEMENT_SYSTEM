<!DOCTYPE html>
<html lang="en">
<head>
    <title>AU - ABC PORTAL</title>
    <!-- Lucide Icons for your Student/Faculty modules -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            background-color: #f4f6f9; /* Light gray background for content */
            font-family: 'Inter', sans-serif; /* Your required font */
        }

        .wrapper {
            display: flex;
            width: 100%;
        }

        .main-container {
            flex-grow: 1;
            margin-left: 260px; /* Matches sidebar width to prevent overlap */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content {
            padding: 30px;
            margin-top: 70px; /* Pushes content below the fixed topbar */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        @include('layouts.sidebar')

        <div class="main-container">
            @include('layouts.topbar')

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons(); // Initialize the icons we discussed
    </script>
</body>
</html>
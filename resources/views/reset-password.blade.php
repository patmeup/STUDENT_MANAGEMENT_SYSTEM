<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Arellano University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(rgba(221, 229, 247, 0.85), rgba(0, 86, 210, 0.85)), url("{{ asset('images/auabc_bg.jpg') }}");
            background-size: cover;
            background-position: center;
        }

        .login-card {
            width: 400px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
        }

        .btn-au {
            background-color: #0033A0;
            color: white;
            font-weight: 600;
            padding: 10px;
        }

        .btn-au:hover {
            background-color: #002270;
            color: white;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center">

<div class="card login-card shadow p-4 text-center">
    <img src="{{ asset('images/au_logo.png') }}" style="width: 80px; display: block; margin: 0 auto 10px;" alt="AU Logo">

    <h5 class="fw-bold" style="color: #0033A0;">Set New Password</h5>
    <small class="text-muted d-block mb-3">Please enter your new password below.</small>

    @if(session('error'))
        <div class="alert alert-danger p-2 small mb-3 text-start">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger p-2 small mb-3 text-start">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/reset-password">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
            <input type="email" name="email" class="form-control" value="{{ $email ?? old('email') }}" placeholder="Your Email" required readonly>
        </div>

        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
            <input type="password" name="password" class="form-control" placeholder="New Password" required autofocus>
        </div>

        <div class="input-group mb-4">
            <span class="input-group-text"><i class="fas fa-check-circle text-muted"></i></span>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm New Password" required>
        </div>

        <button type="submit" class="btn btn-au w-100">Update Password</button>
    </form>
</div>

</body>
</html>
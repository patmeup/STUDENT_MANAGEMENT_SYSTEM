<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Arellano University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: 
                linear-gradient(rgba(221, 229, 247, 0.85), rgba(0, 86, 210, 0.85)),
                url("{{ asset('images/auabc_bg.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-card {
            width: 400px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
        }

        .logo {
            width: 80px;
            display: block;
            margin: 0 auto 10px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #0033A0;
        }

        .btn-au {
            background-color: #0033A0;
            color: white;
            font-weight: 600;
            padding: 10px;
            transition: 0.3s;
        }

        .btn-au:hover {
            background-color: #002270;
            color: white;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center">

<div class="card login-card shadow p-4 text-center">

    <img src="{{ asset('images/au_logo.png') }}" class="logo" alt="AU Logo">

    <h5 class="fw-bold" style="color: #0033A0;">Reset Your Password</h5>
    <p class="text-muted small mb-3">Enter your account's registered email address to receive a password reset link.</p>

    @if(session('status'))
        <div class="alert alert-success p-2 small mb-3 text-start">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger p-2 small mb-3 text-start">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger p-2 small mb-3 text-start">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
            <input type="email" name="email" class="form-control" placeholder="Enter Registered Email" value="{{ old('email') }}" required autofocus>
        </div>

        <button type="submit" class="btn btn-au w-100 mb-3">Send Reset Link</button>
    </form>

    <div class="mt-2">
        <a href="{{ url('/') }}" class="text-decoration-none small" style="color: #0033A0;">
            <i class="fas fa-arrow-left me-1"></i> Back to Login
        </a>
    </div>

</div>

</body>
</html>
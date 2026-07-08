<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arellano University - Login Portal</title>
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
            width: 380px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
        }

        .logo {
            width: 90px;
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

    <h5 class="fw-bold" style="color: #0033A0;">ARELLANO UNIVERSITY</h5>
    <small class="text-muted d-block mb-4">Student Information & Enrollment System</small>

    @if(session('error'))
        <div class="alert alert-danger p-2 small mb-3">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger p-2 small mb-3 text-start">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}" autocomplete="off">
        @csrf

        <div class="input-group mb-3">
            <span class="input-group-text"><i class="fas fa-id-card text-muted"></i></span>
            <input type="text" name="login_id" class="form-control" placeholder="Student or Employee ID" required autofocus>
        </div>

        <div class="input-group mb-4">
            <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
            <span class="input-group-text" onclick="togglePassword()" style="cursor:pointer;">
                <i class="fas fa-eye text-muted" id="eyeIcon"></i>
            </span>
        </div>

        <button type="submit" class="btn btn-au w-100">Login to Portal</button>
    </form>

    <div class="mt-4">
        <small class="text-muted">Andres Bonifacio Campus</small>
    </div>

</div>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        password.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

</body>
</html>
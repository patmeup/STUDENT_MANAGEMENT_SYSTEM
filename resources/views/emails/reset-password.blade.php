<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;">
    <div style="max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; border-top: 5px solid #0033A0;">
        <h2 style="color: #0033A0;">Arellano University</h2>
        <p>Hello,</p>
        <p>You are receiving this email because we received a password reset request for your account.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetLink }}" style="background-color: #0033A0; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Reset Password</a>
        </div>

        <p style="font-size: 13px; color: #666;">If you did not request a password reset, no further action is required.</p>
    </div>
</body>
</html>
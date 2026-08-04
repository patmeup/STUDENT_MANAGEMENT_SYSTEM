<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document Request Received</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #2563eb; margin-top: 0;">Good day, {{ $student->first_name }}!</h2>
        
        <p>We have received your request for documents from the Registrar's Office. It is currently being processed.</p>
        
        <p><strong>Requested Documents:</strong></p>
        <ul>
            @foreach($requestedDocuments as $doc)
                <li>{{ $doc }}</li>
            @endforeach
        </ul>

        <p>We will notify you again via email once they are ready or approved.</p>
        
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b;">This is an automated message from the system. Please do not reply to this email.</p>
    </div>
</body>
</html>
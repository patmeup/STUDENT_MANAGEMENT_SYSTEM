<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document Request Received</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #2563eb; margin-top: 0;">Magandang Araw, {{ $student->first_name }}!</h2>
        
        <p>Natanggap na namin ang iyong kahilingan para sa mga dokumento mula sa Tanggapan ng Registrar. Kasalukuyan na itong pinoproseso.</p>
        
        <p><strong>Mga Nire-request na Dokumento:</strong></p>
        <ul>
            @foreach($requestedDocuments as $doc)
                <li>{{ $doc }}</li>
            @endforeach
        </ul>

        <p>Aabisuhan ka namin muli sa pamamagitan ng email kapag handa na o naaprubahan na ang mga ito.</p>
        
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b;">Ito ay awtomatikong mensahe mula sa sistema. Mangyaring huwag mag-reply sa email na ito.</p>
    </div>
</body>
</html>
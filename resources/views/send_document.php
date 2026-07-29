<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

function sendDocumentToStudent($studentEmail, $studentName, $documentName, $filePath) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host        = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aulinklms@gmail.com'; 
        $mail->Password   = 'sjrr dqjw xdfh ciyb';         
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('aulinklms@gmail.com', 'AU-LINK Registrar Office');
        $mail->addAddress($studentEmail, $studentName);

        if (file_exists($filePath)) {
            $mail->addAttachment($filePath); 
        } else {
            return "Error: Hindi makita ang file sa server.";
        }

        $mail->isHTML(true);
        $mail->Subject = "Your Requested Document: " . $documentName;
        
        $mail->Body    = "
            <h3>Hello, " . htmlspecialchars($studentName) . "!</h3>
            <p>Ang iyong nirequest na dokumento na <b>" . htmlspecialchars($documentName) . "</b> ay naaprubahan na at naka-attach sa email na ito.</p>
            <p>Maraming salamat po!</p>
            <br>
            <p><b>AU-LINK Registrar Team</b></p>
        ";
        
        $mail->AltBody = "Hello " . $studentName . ", ang iyong nirequest na " . $documentName . " ay naka-attach sa email na ito.";

        $mail->send();
        return "Success";
        
    } catch (Exception $e) {
        return "Error sa pag-send ng email: {$mail->ErrorInfo}";
    }
}

if (isset($_POST['approve_request'])) {
    $email = $_POST['student_email']; 
    $name = $_POST['student_name'];
    $docTitle = "Transcript of Records";
    $file = "uploads/documents/TOR_12345.pdf"; 

    $result = sendDocumentToStudent($email, $name, $docTitle, $file);
    echo $result;
}
?>
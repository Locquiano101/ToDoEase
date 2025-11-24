<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $to_email = filter_var($_POST['to_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $subject  = htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES);
    $message  = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES);

    if (!$to_email || !$subject || !$message) {
        die('All fields are required and valid.');
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'glez.allera09@gmail.com';
        $mail->Password   = 'wooc tted aupq cqdu'; // 16-character Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('glez.allera09@gmail.com', 'Todo Ease');
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        $mail->send();
        $status = "Email sent successfully to $to_email!";
    } catch (Exception $e) {
        $status = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

} else {
    die('Invalid request method.');
}
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="p-6 bg-white dark:bg-slate-800 rounded-xl shadow-md text-center">
        <p class="text-lg"><?= $status ?></p>
        <a href="javascript:history.back()" class="mt-4 inline-block px-4 py-2 bg-gray-200 dark:bg-slate-700 rounded">Go Back</a>
    </div>
</div>
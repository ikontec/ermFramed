
<?php
include 'classes/connect.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['user_input'] ?? '');
    if ($user_input === '') {
        $message = 'Please enter your email or username.';
    } else {
        // Search in both teacher and student tables
        $conn = $conn ?? (new mysqli('localhost', 'root', '', 'ermframed'));
        if ($conn->connect_error) {
            die('Database connection failed: ' . $conn->connect_error);
        }

        $user = null;
        $accountType = null;
        $email = null;

        // Check teacher account
        $stmt = $conn->prepare("SELECT id, email FROM staff WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param('ss', $user_input, $user_input);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user = $row['id'];
            $email = $row['email'];
            $accountType = 'teacher';
        }
        $stmt->close();

        // If not found, check student account
        if (!$user) {
            $stmt = $conn->prepare("SELECT id, email FROM students WHERE email = ? OR username = ? LIMIT 1");
            $stmt->bind_param('ss', $user_input, $user_input);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user = $row['id'];
                $email = $row['email'];
                $accountType = 'student';
            }
            $stmt->close();
        }

        if ($user && $email) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Store token in password_resets table
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, account_type VARCHAR(20), token VARCHAR(64), expires DATETIME, used TINYINT DEFAULT 0)");
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, account_type, token, expires) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isss', $user, $accountType, $token, $expires);
            $stmt->execute();
            $stmt->close();

            // Send email
            $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/resetPassword.php?token=$token";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; // Set your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'your@email.com'; // SMTP username
            $mail->Password = 'yourpassword'; // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('no-reply@example.com', 'ERM Password Reset');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Hello,\n\nA password reset was requested for your account. If you did not request this, please ignore this email.\n\nTo reset your password, click the link below (valid for 1 hour):\n$reset_link\n\nThank you.";

            if ($mail->send()) {
                $message = 'A password reset link has been sent to your email address.';
            } else {
                $message = 'Failed to send email. Please contact support.';
            }
        } else {
            $message = 'No account found with that email or username.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="myStyles/pc.css">
</head>
<body>
    <div class="container" style="max-width:400px;margin:40px auto;padding:20px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #ccc;">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div style="color: #d00; margin-bottom: 10px;"> <?= htmlspecialchars($message) ?> </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="user_input">Email or Username:</label><br>
            <input type="text" name="user_input" id="user_input" required style="width:100%;padding:8px;margin:10px 0;">
            <button type="submit" style="width:100%;padding:10px;background:#007bff;color:#fff;border:none;border-radius:4px;">Send Reset Link</button>
        </form>
        <p style="margin-top:20px;"><a href="index.php">Back to Login</a></p>
    </div>
</body>
</html>

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

session_start(); // Start the session

// Database connection
include "classes/connect.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registation</title>
    <link rel="stylesheet" href="Academic_Styles/pc.css">
    <link rel="stylesheet" href="Academic_Styles/tablet.css">
    <link rel="stylesheet" href="Academic_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header>
        <img src="../../images/default.png" alt="school logo" class="logo">
        <h1 class="schoolName">KWAUSO SECONDARY SCHOOL</h1>
    </header>

    <nav class="nav">
        <button class="menuButton" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-chevron-down" id="menuIcon"></i>
        </button>
        <ul>
            <li><a href="academicAccount.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>

<main class="boy">
    <h3>Parent Details</h3>
    <form class="form" method="post" enctype="multipart/form-data">
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Sanitize and fetch form inputs
            $student_url = htmlspecialchars($_POST['student_url']);
            $first_name = htmlspecialchars(strtoupper($_POST['p1_first_name']));
            $last_name = htmlspecialchars(strtoupper($_POST['p1_last_name']));
            $gender = htmlspecialchars($_POST['p1_gender']);
            $relationship = htmlspecialchars(strtoupper($_POST['p1_relationship']));
            $phone = htmlspecialchars($_POST['p1_phone']);
            $email = htmlspecialchars($_POST['p1_email']);

            // Fetch student ID
            $stmt = $conn->prepare("SELECT * FROM students WHERE url = ?");
            $stmt->bind_param("s", $student_url);
            $stmt->execute();
            $result = $stmt->get_result();
            $student_details = $result->fetch_assoc();
            $stmt->close();

            $student_id = $student_details['student_id'];
            $studentName = $student_details['first_name'] . ' ' . $student_details['middle_name'] . ' ' . $student_details['last_name'];
            $username = $student_details['username'];
            $password = $student_details['url'];

            if ($student_id) {
                // Check if parent already exists
                $stmt = $conn->prepare("SELECT parent_id FROM parent WHERE student_id = ? AND first_name = ? AND last_name = ?");
                $stmt->bind_param("iss", $student_id, $first_name, $last_name);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    echo "Parent details already exist.";
                } else {
                    // Insert parent details
                    $stmt = $conn->prepare("INSERT INTO parent (student_id, first_name, last_name, gender, relationship, phone, email) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssis", $student_id, $first_name, $last_name, $gender, $relationship, $phone, $email);

                    if ($stmt->execute()) {
                        // Send email to parent
                        $mail = new PHPMailer(true);

                        try {
                            // SMTP Configuration
                                $mail->isSMTP();
                                $mail->Host       = 'smtp.gmail.com';
                                $mail->SMTPAuth   = true;
                                $mail->Username   = 'vediusv@gmail.com';  // Your Gmail
                                $mail->Password   = 'xlvk pkuf cybi brpu';   // App Password from Google
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port       = 587;

                                // Disable SSL verification (Optional for local testing)
                                $mail->SMTPOptions = array(
                                    'ssl' => array(
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true
                                    )
                                );

                            // Fetch title for the email
                            $titleQuery = $conn->query("SELECT title FROM back_site");
                            $title = $titleQuery->fetch_assoc();

                            // Prepare email content
                            $mail->setFrom('vediusv@gmail.com', $title['title']);
                            $mail->addAddress($email); // Recipient email
                            $mail->isHTML(true);
                            $mail->Subject = htmlspecialchars($title['title']);
                            $mail->Body = "<p>Dear <b>" . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . "</b>,<br>
                            <br>Welcome to our Result Management System.<br> 
                            <br>Your user name is: <b>$username</b>.<br>
                            <br>Your password is: <b>$password</b>.<br>
                            <br>Please keep this information safe.</p>";

                            $mail->send();
                            header('Location: done.php'); // Redirect on success
                            exit();
                        } catch (Exception $e) {
                            echo "Error sending email: {$mail->ErrorInfo}";
                        }
                    } else {
                        echo "Error: " . "Email not sent. Chack your connection or try again later.";
                    }
                }
                $stmt->close();
            } else {
                echo "Student not found with the provided Link Code.";
            }

            $conn->close();
        }
        ?>

        <input type="text" id="student_url" name="student_url" placeholder="Link Code" required><br>
        <input type="text" id="p1_first_name" name="p1_first_name" placeholder="First Name" required><br>
        <input type="text" id="p1_last_name" name="p1_last_name" placeholder="Last Name" required><br>
        <label for="p1_gender">Gender:</label>
        <select id="p1_gender" name="p1_gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select><br>
        <input type="text" id="p1_relationship" name="p1_relationship" placeholder="Relationship" required><br>
        <input type="tel" id="p1_phone" name="p1_phone" placeholder="Phone" required><br>
        <input type="email" id="p1_email" name="p1_email" placeholder="Email" required><br>
        <button id="btn2" type="submit">DONE</button>
    </form>
</main>
<script src="script.js"></script>
</body>
</html>

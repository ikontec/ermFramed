<?php
set_time_limit(0); // Allow unlimited execution time (or use set_time_limit(300) for 5 minutes)
session_start();
// Ensure the author is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php"); // Redirect to the login page if not logged in
    exit();
}

include 'classes/connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

// Function to calculate grades based on average
function calculate_grade($average) {
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B';
    if ($average >= 60) return 'C';
    if ($average >= 50) return 'D';
    if ($average >= 40) return 'E';
    if ($average >= 35) return 'S';
    return 'F';
}

// Function to send results emails
function sendStudentResultsEmail($to, $student_name, $subject_list, $marks, $total, $average, $grade, $rank, $exam_name) {

    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'vediusv@gmail.com'; // Your email
        $mail->Password = 'xlvk pkuf cybi brpu'; // Your password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; // SMTP Port

        // Recipients
        $mail->setFrom('vediusv@gmail.com', 'MY SCHOOL');
        $mail->addAddress($to); // Add parent's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'RESULTS FOR ' . $exam_name;

        // Prepare email body
        $emailBody = "<h2>Student: $student_name</h2>";
        $emailBody .= "<p><b>Exam:</b> $exam_name</p>";
        $emailBody .= "<p><b>Subjects and Marks:</b></p>";
        $emailBody .= "<table border='1'><tr><th>Subject</th><th>Marks</th></tr>";

        foreach ($subject_list as $subject) {
            $emailBody .= "<tr><td>" . htmlspecialchars($subject) . "</td><td>" . htmlspecialchars($marks[$subject]) . "</td></tr>";
        }

        $emailBody .= "</table>";
        $emailBody .= "<p><b>Total Marks:</b> $total</p>";
        $emailBody .= "<p><b>Average:</b> $average</p>";
        $emailBody .= "<p><b>Grade:</b> $grade</p>";
        $emailBody .= "<p><b>Rank:</b> $rank</p>";
        
        $mail->Body = $emailBody;

        // Send the email
        $mail->send();
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }
}

// Function to queue emails for sending
function queueEmails($exam_id, $class) {
    global $conn;

    // Fetch subject list for the exam and class
    $subject_result = $conn->query("SELECT DISTINCT UPPER(subject_name) AS subject_name FROM class_marks WHERE exam_id = $exam_id AND class = '$class'");
    $subject_list = [];
    while ($row = $subject_result->fetch_assoc()) {
        $subject_list[] = $row['subject_name'];
    }

    // Fetch students who have marks for the exam and class
    $student_result = $conn->query("
        SELECT DISTINCT s.student_id, s.first_name, s.middle_name, s.last_name
        FROM students s
        JOIN class_marks c ON s.student_id = c.student_id
        WHERE c.exam_id = $exam_id AND c.class = '$class'
    ");

    $results = [];

    while ($student_row = $student_result->fetch_assoc()) {
        $student_id = $student_row['student_id'];
        $student_name = htmlspecialchars(trim($student_row['first_name'] . ' ' . $student_row['middle_name'] . ' ' . $student_row['last_name']));

        // Fetch parent email
        $email_query = $conn->query("SELECT email FROM parent WHERE student_id = '$student_id' LIMIT 1");
        $parent_email = '';
        if ($email_query && $email_row = $email_query->fetch_assoc()) {
            $parent_email = $email_row['email'];
        }

        // Skip if no valid email found
        if (!filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        // Fetch marks and calculate total, average, and grade
        $total = 0;
        $count = 0;
        $marks = [];

        foreach ($subject_list as $subject) {
            $mark_query = $conn->query("SELECT marks FROM class_marks WHERE student_id = '$student_id' AND exam_id = $exam_id AND class = '$class' AND UPPER(subject_name) = '$subject' LIMIT 1");

            if ($mark_row = $mark_query->fetch_assoc()) {
                $mark = $mark_row['marks'];
                $mark_val = is_numeric($mark) ? (int)$mark : '-';
                $marks[$subject] = $mark_val;

                if (is_numeric($mark_val)) {
                    $total += $mark_val;
                    $count++;
                } else {
                    $marks[$subject] = '-';
                }
            } else {
                $marks[$subject] = '-';
            }
        }

        $average = $count > 0 ? round($total / $count, 2) : 0;
        $grade = calculate_grade($average);

        // Store results for later processing
        $results[] = [
            'parent_email' => $parent_email,
            'student_name' => $student_name,
            'marks' => $marks,
            'total' => $total,
            'average' => $average,
            'grade' => $grade,
            'examname' => $_POST['exam_id'] // Exam name to include in email subject
        ];
    }

    // Send emails for all students
    foreach ($results as $email_data) {
        sendStudentResultsEmail(
            $email_data['parent_email'],
            $email_data['student_name'],
            $subject_list,
            $email_data['marks'],
            $email_data['total'],
            $email_data['average'],
            $email_data['grade'],
            '-', // Rank placeholder (add later if needed)
            $email_data['examname']
        );
    }

    return count($results); // Number of emails sent
}

// Process the request to send emails asynchronously
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id']) && isset($_POST['class'])) {
    $exam_id = $conn->real_escape_string($_POST['exam_id']);
    $class = $conn->real_escape_string($_POST['class']);

    // Queue emails and return success message
    $sent_count = queueEmails($exam_id, $class);
    $final = "Successfully sent results to $sent_count parents.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Results Emails</title>
    <link rel="stylesheet" href="Academic_Styles/pc.css">
    <link rel="stylesheet" href="Academic_Styles/tablet.css">
    <link rel="stylesheet" href="Academic_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .upt {
            padding: 20px;
        }
        .upt h1 {
            text-align: center;
            color: green;
        }
    </style>
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
            <li><a href="messages.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>

    <main class="upt">
        <div>
            <h1><?php echo $final; ?></h1>
        </div>
    </main>


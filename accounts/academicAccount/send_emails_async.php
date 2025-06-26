<?php
set_time_limit(0);
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
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
function sendStudentResultsEmail($to, $student_name, $average, $position, $total_students, $exam_name, $title, $is_incomplete) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'vediusv@gmail.com';
        $mail->Password = 'xlvk pkuf cybi brpu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('vediusv@gmail.com', $title);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'RESULTS FOR ' . $exam_name;

        if ($is_incomplete) {
            $emailBody = "<h2>" . strtoupper($title) . ".\n". " RESULTS FOR " . strtoupper($exam_name) . ".</h2>";
            $emailBody .= "<p>" . strtoupper($student_name) . " HAS INCOMPLETE MARKS. PLEASE CONTACT SCHOOL / VISIT ACCOUNT FOR MORE DETAILS.</p>";
        } else {
            $emailBody = "<h2>" . strtoupper($title) . ".\n". " RESULTS FOR " . strtoupper($exam_name) . ".</h2>";
            $emailBody .= "<p>" . strtoupper($student_name) . " HAS SCORED AVERAGE OF $average HOLDING POSITION-$position/$total_students.</p>";
            $emailBody .= "<p>VISIT ACCOUNT FOR MORE DETAILS.</p>";
        }

        $mail->Body = $emailBody;
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

    // Fetch school title
    $get_data = $conn->query("SELECT title FROM back_site");
    $site_data = $get_data->fetch_assoc();
    $title = $site_data['title'];

    // Fetch exam name
    $ex_name_query = $conn->query("SELECT exam_name FROM examinations WHERE exam_id = '$exam_id' LIMIT 1");
    $name_data = $ex_name_query->fetch_assoc();
    $exam_name = $name_data['exam_name'];

    // Prepare averages for ranking
    $avg_list = [];
    $avg_query = $conn->query("
        SELECT s.student_id, AVG(cm.marks) as avg_marks
        FROM students s
        JOIN class_marks cm ON s.student_id = cm.student_id
        WHERE cm.exam_id = $exam_id AND cm.class = '$class'
        GROUP BY s.student_id
        ORDER BY avg_marks DESC
    ");
    while ($avg_row = $avg_query->fetch_assoc()) {
        $avg_list[] = [
            'student_id' => $avg_row['student_id'],
            'avg_marks' => $avg_row['avg_marks']
        ];
    }
    $total_students = count($avg_list);

    $sent_count = 0;

    while ($student_row = $student_result->fetch_assoc()) {
        $student_id = $student_row['student_id'];
        $student_name = htmlspecialchars(trim($student_row['first_name'] . ' ' . $student_row['middle_name'] . ' ' . $student_row['last_name']));

        // Fetch parent email
        $email_query = $conn->query("SELECT email FROM parent WHERE student_id = '$student_id' LIMIT 1");
        $parent_email = '';
        if ($email_query && $email_row = $email_query->fetch_assoc()) {
            $parent_email = $email_row['email'];
        }
        if (!filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        // Count registered subjects for this student in this class and exam
        $reg_query = $conn->query("SELECT COUNT(DISTINCT subject_name) AS reg_count FROM class_marks WHERE student_id = '$student_id' AND exam_id = $exam_id AND class = '$class'");
        $reg_row = $reg_query->fetch_assoc();
        $registered_subjects = (int)$reg_row['reg_count'];

        // Count subjects with marks (not null and numeric)
        $marks_count = 0;
        $total = 0;
        $count = 0;
        foreach ($subject_list as $subject) {
            $mark_query = $conn->query("SELECT marks FROM class_marks WHERE student_id = '$student_id' AND exam_id = $exam_id AND class = '$class' AND UPPER(subject_name) = '$subject' LIMIT 1");
            if ($mark_row = $mark_query->fetch_assoc()) {
                $mark = $mark_row['marks'];
                if (is_numeric($mark)) {
                    $marks_count++;
                    $total += $mark;
                    $count++;
                }
            }
        }
        $average = $count > 0 ? round($total / $count, 2) : 0;

        // Check for incomplete
        $is_incomplete = ($marks_count < $registered_subjects);

        // Calculate position
        $position = 1;
        foreach ($avg_list as $idx => $item) {
            if ($item['student_id'] == $student_id) {
                $position = $idx + 1;
                break;
            }
        }

        sendStudentResultsEmail(
            $parent_email,
            $student_name,
            $average,
            $position,
            $total_students,
            $exam_name,
            $title,
            $is_incomplete
        );
        $sent_count++;
    }

    return $sent_count;
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
        .upt { padding: 20px; }
        .upt h1 { text-align: center; color: green; }
    </style>
</head>
<body>
    <header>
        <?php
            include '../../classes/connect.php';
            $get_data = $conn->query("SELECT image_id, image, title FROM back_site");
            $site_data = $get_data->fetch_assoc();
            $title = $site_data['title'];
            $image = $site_data['image'];
        ?>
        <img src="../../<?php echo $image; ?>" alt="school logo" class="logo">
        <h1 class="schoolName"><?php echo $title; ?></h1>
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
            <h1><?php echo isset($final) ? $final : ""; ?></h1>
        </div>
    </main>
</body>
</html>
<?php
set_time_limit(0);
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include 'classes/connect.php';

// Africa's Talking credentials
$username = "IkonTeki";
$apiKey = "atsk_a99e5d10fdfd95db7dc35071130e45058f70fade6de1a49c2e45da0cf416832a4bdce10c";

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

// Function to send SMS via Africa's Talking
function sendSMS($to, $message, $username, $apiKey) {
    $data = http_build_query([
        'username' => $username,
        'to' => $to,
        'message' => $message
    ]);
    $url = 'https://api.africastalking.com/version1/messaging';
    $headers = [
        'apiKey: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return $err ? false : true;
}

// Function to queue SMS for all parents
function queueSMS($exam_id, $class, $conn, $username, $apiKey) {
    // Fetch subject list for this class and exam
    $subject_result = $conn->query("SELECT DISTINCT UPPER(subject_name) AS subject_name FROM class_marks WHERE exam_id = $exam_id AND class = '$class'");
    $subject_list = [];
    while ($row = $subject_result->fetch_assoc()) {
        $subject_list[] = $row['subject_name'];
    }

    // Fetch students
    $student_result = $conn->query("
        SELECT DISTINCT s.student_id, s.first_name, s.middle_name, s.last_name
        FROM students s
        JOIN class_marks c ON s.student_id = c.student_id
        WHERE c.exam_id = $exam_id AND c.class = '$class'
    ");

    $sent_count = 0;

    while ($student_row = $student_result->fetch_assoc()) {
        $student_id = $student_row['student_id'];
        $student_name = trim($student_row['first_name'] . ' ' . $student_row['middle_name'] . ' ' . $student_row['last_name']);

        // Fetch parent phone
        $phone_query = $conn->query("SELECT phone FROM parent WHERE student_id = '$student_id' LIMIT 1");
        $parent_phone = '';
        if ($phone_query && $phone_row = $phone_query->fetch_assoc()) {
            $parent_phone = "+255".$phone_row['phone'];
        }

        // Skip if no valid phone
        if (!preg_match('/^\+?\d{10,15}$/', $parent_phone)) {
            continue;
        }

        // Fetch data from the backsite table
        include 'classes/connect.php';
        $get_data = $conn->query("SELECT title FROM back_site");
        if (!$get_data) {
            die("Error fetching exam name: " . $conn->error);
        }
        $site_data = $get_data->fetch_assoc();
        $title = $site_data['title'];

        // Fetch exam name
        $ex_name_query = $conn->query("SELECT exam_name FROM examinations WHERE exam_id = '$exam_id' LIMIT 1");
        if (!$ex_name_query) {
            die("Error fetching exam name: " . $conn->error);
        }
        $name_data = $ex_name_query->fetch_assoc();
        $ex_name = $name_data['exam_name'];

        // Count registered subjects for this student in this class and exam
        $reg_query = $conn->query("SELECT COUNT(DISTINCT subject_name) AS reg_count FROM class_marks WHERE student_id = '$student_id' AND exam_id = $exam_id AND class = '$class'");
        $reg_row = $reg_query->fetch_assoc();
        $registered_subjects = (int)$reg_row['reg_count'];

        // Count subjects with marks (not null and numeric)
        $marks_count = 0;
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
                    $marks_count++;
                    $total += $mark_val;
                    $count++;
                }
            } else {
                $marks[$subject] = '-';
            }
        }
        $average = $count > 0 ? round($total / $count, 2) : 0;

        // Check for incomplete
        if ($marks_count < $registered_subjects) {
            $msg = strtoupper($title) . ".\n". " RESULTS FOR " . strtoupper($ex_name) . ". ";
            $msg .= strtoupper($student_name) . " HAS INCOMPLETE MARKS. PLEASE CONTACT SCHOOL FOR DETAILS.";
        } else {
            // Calculate position
            $position = 1;
            $total_students = 1;
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
            foreach ($avg_list as $idx => $item) {
                if ($item['student_id'] == $student_id) {
                    $position = $idx + 1;
                    break;
                }
            }

            $msg = strtoupper($title) . ".\n". " RESULTS FOR " . strtoupper($ex_name) . ". ";
            $msg .= strtoupper($student_name) . " HAS SCORED AVERAGE OF $average HOLDING POSITION-$position/$total_students. ";
            $msg .= "VISIT ACCOUNT FOR MORE DETAILS.";
        }

        // Send SMS
        if (sendSMS($parent_phone, $msg, $username, $apiKey)) {
            $sent_count++;
        }
    }
    return $sent_count;
}

// Process the request to send SMS asynchronously
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id']) && isset($_POST['class'])) {
    $exam_id = $conn->real_escape_string($_POST['exam_id']);
    $class = $conn->real_escape_string($_POST['class']);
    $sent_count = queueSMS($exam_id, $class, $conn, $username, $apiKey);
    $final = "Successfully sent SMS to $sent_count parents.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Results SMS</title>
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
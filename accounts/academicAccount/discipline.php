<?php
session_start(); // Start the session
// Ensure the parent is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

$student_id = intval($_GET['student_id']);

// Fetch data from the students table
$student_query = $conn->query("SELECT first_name, middle_name, last_name, date_of_birth, gender, class, photo FROM students WHERE student_id = $student_id");
$student = $student_query->fetch_assoc();

// Function to calculate grades
function calculate_grade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    if ($marks >= 40) return 'E';
    if ($marks >= 35) return 'S';
    if ($marks >= 0) return 'F';
    return '-';
}


    // Fetch data from the parent table
    $get_data = $conn->query("SELECT image_id, title FROM back_site");
    if (!$get_data) {
        die("Error fetching exam name: " . $conn->error);
    }
    $site_data = $get_data->fetch_assoc();
    $title = $site_data['title'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Dashboard</title>
    <link rel="stylesheet" href="Academic_Styles/pc.css">
    <link rel="stylesheet" href="Academic_Styles/tablet.css">
    <link rel="stylesheet" href="Academic_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="student_list.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>
    <div class="header">
        <h3 class="heading"><?php echo $title; ?></h3>
        <h4 class="heading">GENERAL REPORT FOR <?= htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['last_name']) ?></h4>
        <h4 class="heading"><?= htmlspecialchars($student['class']) ?></h4>
        <h4 class="heading"><?php echo date("Y"); ?></h4> 
    </div>

    <div>
        <h4 style="text-align: center;">STUDENT'S ACADEMIC TREND</h4>
            <div class="results">
                <?php
                // Fetch the last three exams for the student
                $exams_query = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC LIMIT 3");
                if (!$exams_query) {
                    die('Error fetching exams: ' . $conn->error);
                }

                $exam_data = [];

                while ($exam = $exams_query->fetch_assoc()) {
                    $exam_id = $exam['exam_id'];
                    $exam_name = $exam['exam_name'];

                    // Fetch student marks for each exam
                    $marks_query = $conn->query("SELECT subject_name, marks FROM class_marks WHERE exam_id = $exam_id AND student_id = $student_id");
                    if (!$marks_query) {
                        die('Error occurred when fetching marks: ' . $conn->error);
                    }

                    $marks = [];
                    $total_marks = 0;
                    $subject_count = 0;
                    $has_incomplete = false;

                    while ($mark = $marks_query->fetch_assoc()) {
                        $subject = strtoupper($mark['subject_name']);
                        $score = $mark['marks'];

                        $marks[] = [
                            'subject' => $subject,
                            'score' => $score
                        ];

                        if (is_numeric($score)) {
                            $total_marks += $score;
                            $subject_count++;
                        } else {
                            $has_incomplete = true;
                        }
                    }

                    if ($has_incomplete || $subject_count == 0) {
                        // Incomplete exam, assign dashes
                        $exam_data[] = [
                            'exam_name' => $exam_name,
                            'marks' => $marks,
                            'total' => '-',
                            'average' => '-',
                            'grade' => '-'
                        ];
                    } else {
                        $average = $total_marks / $subject_count;
                        $exam_data[] = [
                            'exam_name' => $exam_name,
                            'marks' => $marks,
                            'total' => $total_marks,
                            'average' => $average,
                            'grade' => calculate_grade($average)
                        ];
                    }
                }

                // Reverse the array to show the latest exam first
                $exam_data = array_reverse($exam_data);

                // Display the exam results
                foreach ($exam_data as $data) {
                    echo '<div class="test">';
                    echo '<h4>' . htmlspecialchars($data['exam_name']) . '</h4>';
                    echo '<table border="1">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Subject</th>';
                    echo '<th>Marks</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    foreach ($data['marks'] as $mark) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($mark['subject']) . '</td>';
                        echo '<td>' . htmlspecialchars($mark['score']) . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';

                    if (is_numeric($data['average'])) {
                        echo '<p><strong>Total:</strong> ' . $data['total'] . '</p>';
                        echo '<p><strong>Average:</strong> ' . number_format($data['average'], 1) . '</p>';
                        echo '<p><strong>Grade:</strong> ' . htmlspecialchars($data['grade']) . '</p>';
                    } else {
                        echo '<p><strong>Total:</strong> -</p>';
                        echo '<p><strong>Average:</strong> -</p>';
                        echo '<p><strong>Grade:</strong> -</p>';
                    }

                    echo '</div><br>'; // Space between exam sections
                }

                // Prepare the arrays for the progress chart (only for complete exams)
                $averages = [];
                $exam_names = [];

                foreach ($exam_data as $data) {
                    if (is_numeric($data['average'])) {
                        $averages[] = $data['average'];
                        $exam_names[] = $data['exam_name'];
                    }
                }

                // Determine the progress trend
                $remark = 'Undetermined';
                if (count($averages) === 3) {
                    if ($averages[0] > $averages[1] && $averages[1] > $averages[2]) {
                        $remark = 'Declining';
                    } elseif ($averages[0] < $averages[1] && $averages[1] < $averages[2]) {
                        $remark = 'Improving';
                    } elseif ($averages[0] === $averages[1] && $averages[1] === $averages[2]) {
                        $remark = 'Stagnant';
                    }
                }
                ?>

            </div>

        <div class="chart">
            <label>PROGRESS CHART:</label><br>
            <canvas id="progressChart" width="400" height="200"></canvas>
            <script>
                var ctx = document.getElementById('progressChart').getContext('2d');
                var progressChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($exam_names) ?>,
                        datasets: [{
                            label: 'Average Marks',
                            data: <?= json_encode($averages) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            </script>
            <label>REMARK: </label><?= htmlspecialchars($remark) ?>
        </div><br><br>

<h4 style="text-align: center;">STUDENT'S DISCIPLINE TREND</h4>
        <div class="discipline">
            <?php
            // Query to fetch discipline cases for the student
            $discipline_query = $conn->query("SELECT case_description, action_taken, date_reported FROM discipline_cases WHERE student_id = $student_id ORDER BY date_reported ASC");
            if (!$discipline_query) {
                die('Error fetching discipline cases: ' . $conn->error);
            }

            // Check if there are any discipline cases reported
            if ($discipline_query->num_rows > 0) {
                // Display each discipline case
                while ($case = $discipline_query->fetch_assoc()) {
                    $case_description = $case['case_description'];
                    $action_taken = $case['action_taken'];
                    $date_reported = date('F j, Y', strtotime($case['date_reported'])); // Format the date

                    echo '<div class="case">';
                    echo '<p><strong>Date Reported:</strong> ' . htmlspecialchars($date_reported) . '</p>';
                    echo '<p><strong>Case:</strong> ' . htmlspecialchars($case_description) . '</p>';
                    echo '<p><strong>Action Taken:</strong> ' . htmlspecialchars($action_taken) . '</p>';
                    echo '<hr>'; // Separator for each case
                    echo '</div>';
                }
            } else {
                // No cases reported
                echo '<label>NO CASE REPORTED</label><br>';
                echo '<label>REMARK: </label>KEEP IT UP';
            }
            ?>
        </div><br><br>

        <div class="form-container">
            <h4>REPORT DISCIPLINE CASE</h4>

            <?php
            // Include your database connection file
            include 'classes/connect.php';

            // Process form submission
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Retrieve form data
                $case_description = $_POST['case_description'];
                $action_taken = $_POST['action_taken'];
                $date_reported = $_POST['date_reported'];

                // Insert data into the discipline_cases table
                $insert_query = $conn->prepare("INSERT INTO discipline_cases (student_id, case_description, action_taken, date_reported) VALUES (?, ?, ?, ?)");
                $insert_query->bind_param("isss", $student_id, $case_description, $action_taken, $date_reported);

                if ($insert_query->execute()) {
                    echo "<p style='color: green;'>Discipline case reported successfully!</p>";
                    header('student_list.php');
                } else {
                    echo "<p style='color: red;'>Error reporting discipline case: " . $conn->error . "</p>";
                }
                $insert_query->close();
            }
            ?>

            <form action=" " method="POST">
                <label for="case_description">Case Description:</label>
                <textarea id="case_description" name="case_description" rows="4" required></textarea>

                <label for="action_taken">Action Taken:</label>
                <textarea id="action_taken" name="action_taken" rows="4" required></textarea>

                <label for="date_reported">Date Reported:</label>
                <input type="date" id="date_reported" name="date_reported" required>

                <button type="submit">Submit Case</button>
            </form>
        </div>
</body>
</html>

<?php
session_start();

// Ensure only authenticated admin can access this page
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Adjust to your login page
    exit();
}

// Include your database connection file
require_once 'classes/connect.php'; // Adjust path as per your file structure

/**
 * Calculates the grade based on marks.
 * @param int|string $marks The marks obtained by the student.
 * @return string The corresponding grade.
 */
function calculate_grade($marks) {
    // Handle non-numeric or empty marks gracefully
    if (!is_numeric($marks) || $marks === '' || $marks === '-') {
        return 'N/A';
    }
    $marks = (int) $marks; // Cast to integer for comparison
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    if ($marks >= 40) return 'E';
    if ($marks >= 35) return 'S';
    return 'F';
}

// Define the list of subjects. Ideally, fetch this dynamically from a 'subjects' table.
$subject_list = [
    'english', 'kiswahili', 'maths', 'biology', 'physics', 'chemistry',
    'geography', 'history', 'civics', 'computer'
];

// Fetch distinct classes from the students table
$classes_result = $conn->query("SELECT DISTINCT class FROM students ORDER BY class ASC");
$classes = ['all' => 'All Classes']; // Option for all classes
if ($classes_result) {
    while ($row = $classes_result->fetch_assoc()) {
        if (!empty($row['class'])) { // Ensure class name is not empty
            $classes[strtolower($row['class'])] = $row['class'];
        }
    }
} else {
    error_log("Error fetching classes: " . $conn->error);
}

// Fetch the latest 5 examinations
$exams_result = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC LIMIT 5");
$exams = [];
if ($exams_result) {
    while ($row = $exams_result->fetch_assoc()) {
        $exams[] = $row;
    }
} else {
    error_log("Error fetching exams: " . $conn->error);
}

// Initialize variables for selected subject, class, and analysis data
$selected_subject = $_POST['subject'] ?? ''; // Pre-select previous choice if available
$selected_class = $_POST['class'] ?? 'all';   // Pre-select previous choice, default to 'all'
$analysis_data = [];
$top_five = [];
$bottom_five = [];
$message = ''; // To display user messages

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $input_subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $input_class = filter_input(INPUT_POST, 'class', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validate subject and class against allowed lists
    if (!in_array($input_subject, $subject_list)) {
        $message = "Invalid subject selected. Please choose from the provided list.";
        $selected_subject = ''; // Clear invalid selection
    } elseif (!array_key_exists($input_class, $classes)) {
        $message = "Invalid class selected. Please choose from the provided list.";
        $selected_class = 'all'; // Reset to default
    } else {
        // Inputs are valid, proceed with analysis
        $selected_subject = $input_subject;
        $selected_class = $input_class;

        if (empty($exams)) {
            $message = "No examinations found in the system to analyze performance.";
        } else {
            // --- Subject Performance Over Time (for the last 5 exams) ---
            foreach ($exams as $exam) {
                $exam_id = $exam['exam_id'];
                $exam_name = htmlspecialchars($exam['exam_name']);

                // Build the class condition for the SQL query
                $class_condition = ($selected_class !== 'all') ? "AND s.class = '" . $conn->real_escape_string($selected_class) . "'" : "";

                // Query to get aggregate statistics for the selected subject and exam
                $stats_query_sql = "SELECT
                    AVG(CAST(cm.marks AS DECIMAL(5,2))) AS avg_marks,
                    MAX(CAST(cm.marks AS DECIMAL(5,2))) AS max_marks,
                    MIN(CAST(cm.marks AS DECIMAL(5,2))) AS min_marks,
                    COUNT(DISTINCT cm.student_id) AS students_count
                    FROM class_marks cm
                    JOIN students s ON cm.student_id = s.student_id
                    WHERE cm.exam_id = '" . $conn->real_escape_string($exam_id) . "'
                    AND cm.subject_name = '" . $conn->real_escape_string($selected_subject) . "'
                    $class_condition
                    AND cm.marks IS NOT NULL AND cm.marks != '' AND cm.marks != '-'";

                $stats_query_result = $conn->query($stats_query_sql);

                if ($stats_query_result) {
                    $data = $stats_query_result->fetch_assoc();
                    $analysis_data[] = [
                        'exam' => $exam_name,
                        'avg' => round($data['avg_marks'] ?? 0, 2),
                        'max' => round($data['max_marks'] ?? 0, 2),
                        'min' => round($data['min_marks'] ?? 0, 2),
                        'count' => $data['students_count'] ?? 0
                    ];
                } else {
                    error_log("Error fetching stats for exam {$exam_id}: " . $conn->error);
                    $message = "Database error fetching exam statistics. Please try again.";
                    $analysis_data = []; // Clear data to prevent incomplete display
                    break; // Exit loop on error
                }
            }

            // --- Top and Bottom 5 Students for the Latest Exam ---
            if (!empty($exams)) {
                $latest_exam_id = $exams[0]['exam_id']; // The first exam in $exams is the latest due to ORDER BY date DESC
                $latest_exam_name = htmlspecialchars($exams[0]['exam_name']);

                $class_condition = ($selected_class !== 'all') ? "AND s.class = '" . $conn->real_escape_string($selected_class) . "'" : "";

                $students_query_sql = "SELECT
                    s.first_name, s.middle_name, s.last_name, cm.marks
                    FROM class_marks cm
                    JOIN students s ON s.student_id = cm.student_id
                    WHERE cm.subject_name = '" . $conn->real_escape_string($selected_subject) . "'
                    AND cm.exam_id = '" . $conn->real_escape_string($latest_exam_id) . "'
                    $class_condition
                    AND cm.marks IS NOT NULL AND cm.marks != '' AND cm.marks != '-'
                    ORDER BY CAST(cm.marks AS DECIMAL(5,2)) DESC"; // Order by marks as decimal for correct sorting

                $students_query_result = $conn->query($students_query_sql);

                $students = [];
                if ($students_query_result) {
                    while ($row = $students_query_result->fetch_assoc()) {
                        $students[] = [
                            'name' => htmlspecialchars(trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'])),
                            'marks' => htmlspecialchars($row['marks']),
                            'grade' => calculate_grade($row['marks'])
                        ];
                    }
                    // Populate top and bottom five, only if students data exists
                    if (!empty($students)) {
                        $top_five = array_slice($students, 0, 5);
                        $bottom_five = array_slice(array_reverse($students), 0, 5);
                    }
                } else {
                    error_log("Error fetching student marks for latest exam: " . $conn->error);
                    $message = "Database error fetching student data. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Manager</title>
    <link rel="stylesheet" href="Academic_Styles/pc.css">
    <link rel="stylesheet" href="Academic_Styles/tablet.css">
    <link rel="stylesheet" href="Academic_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <header>
        <?php
            include '../../classes/connect.php';

            // Fetch data from the parent table
            $get_data = $conn->query("SELECT image_id, image, title FROM back_site");
            if (!$get_data) {
                die("Error fetching exam name: " . $conn->error);
            }
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
            <li><a href="resultsManager.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>

<main>    
<div class="container">
    <h2>Subject Performance Analysis (Last 5 Exams)</h2>

    <?php if (!empty($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="subject">Subject:</label>
        <select name="subject" id="subject" required>
            <option value="">Select Subject</option>
            <?php foreach ($subject_list as $subj): ?>
                <option value="<?= htmlspecialchars($subj) ?>" <?= ($selected_subject === $subj) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($subj)) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="class">Class:</label>
        <select name="class" id="class">
            <?php foreach ($classes as $key => $val): ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= ($selected_class === $key) ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Analyze Performance</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)): ?>
        <?php if (!empty($analysis_data) && array_sum(array_column($analysis_data, 'count')) > 0): // Check if there's any student data?>
            <h3>Performance Overview</h3>
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Students</th>
                        <th>Average</th>
                        <th>Highest</th>
                        <th>Lowest</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis_data as $row): ?>
                        <tr>
                            <td><?= $row['exam'] ?></td>
                            <td><?= $row['count'] ?></td>
                            <td><?= $row['avg'] ?></td>
                            <td><?= $row['max'] ?></td>
                            <td><?= $row['min'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($top_five) || !empty($bottom_five)): ?>
                <h3>Top 5 Performers (Latest Exam: <?= htmlspecialchars($exams[0]['exam_name'] ?? 'N/A') ?>)</h3>
                <?php if (!empty($top_five)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_five as $s): ?>
                                <tr>
                                    <td><?= $s['name'] ?></td>
                                    <td><?= $s['marks'] ?></td>
                                    <td><?= $s['grade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No top performers found for this subject and class in the latest exam.</p>
                <?php endif; ?>

                <h3>Bottom 5 Performers (Latest Exam: <?= htmlspecialchars($exams[0]['exam_name'] ?? 'N/A') ?>)</h3>
                <?php if (!empty($bottom_five)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bottom_five as $s): ?>
                                <tr>
                                    <td><?= $s['name'] ?></td>
                                    <td><?= $s['marks'] ?></td>
                                    <td><?= $s['grade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No bottom performers found for this subject and class in the latest exam.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-data">No individual student performance data available for the latest exam in the selected subject and class.</p>
            <?php endif; ?>

            <div class="chart-container" style="position: relative; width: 100%; max-width: 800px; height: 400px; margin: auto;">
                <h3>Performance Trend</h3>
                <canvas id="trendChart" style="width: 100%; height: 100%;"></canvas>
            </div>


            <script>
            document.addEventListener("DOMContentLoaded", function () {
                const ctx = document.getElementById('trendChart');
                if (ctx) {
                    const trendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?= json_encode(array_column($analysis_data, 'exam')) ?>,
                            datasets: [{
                                label: 'Average Marks',
                                data: <?= json_encode(array_column($analysis_data, 'avg')) ?>,
                                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                                borderColor: '#007BFF',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2,
                                pointBackgroundColor: '#007BFF',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: '#007BFF'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    title: {
                                        display: true,
                                        text: 'Marks (%)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Examination'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            }
                        }
                    });
                } else {
                    console.error("Canvas element with ID 'trendChart' not found.");
                }
            });
            </script>

        <?php else: ?>
            <p class="no-data">No performance data found for the selected subject and class in the last 5 exams. Please try a different selection or ensure data exists.</p>
        <?php endif; ?>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($message)): ?>
        <p class="no-data">Select a subject and class from the options above to view performance analysis.</p>
    <?php endif; ?>
</div>
    <script src="script.js"></script>
</body>
</html>
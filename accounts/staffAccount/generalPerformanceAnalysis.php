<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

include 'classes/connect.php';

$class_query = $conn->query("SELECT DISTINCT class FROM class_marks");
$class_list = [];
while ($row = $class_query->fetch_assoc()) {
    $class_list[] = strtoupper($row['class']);
}

$exam_query = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC");
$exam_list = [];
while ($row = $exam_query->fetch_assoc()) {
    $exam_list[] = $row;
}

function calculate_grade($avg) {
    if ($avg >= 80) return 'A';
    if ($avg >= 70) return 'B';
    if ($avg >= 60) return 'C';
    if ($avg >= 50) return 'D';
    if ($avg >= 40) return 'E';
    if ($avg >= 35) return 'S';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Manager</title>
    <link rel="stylesheet" href="Staff_Styles/pc.css">
    <link rel="stylesheet" href="Staff_Styles/tablet.css">
    <link rel="stylesheet" href="Staff_Styles/phone.css">
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
        <li><a href="resultsManager.php"><i class="fas fa-arrow-left"></i> Back</a></li>
    </ul>
</nav>
<main>
    <div class="container">
        <h3>Academic Performance Dashboard</h3>
        <form method="POST" id="filterForm">
            <select id="class" name="class" required>
                <option value="">Select Class</option>
                <?php foreach ($class_list as $cls): ?>
                    <option value="<?= $cls ?>"><?= ucwords($cls) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="exam_id" id="exam_id" required>
                <option value="">Select Examination</option>
                <?php foreach ($exam_list as $exam): ?>
                    <option value="<?= $exam['exam_id'] ?>"><?= htmlspecialchars($exam['exam_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Generate Report</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['exam_id'])):
            $class = $conn->real_escape_string($_POST['class']);
            $exam_id = $conn->real_escape_string($_POST['exam_id']);

            $grades = ["A" => 0, "B" => 0, "C" => 0, "D" => 0, "E" => 0, "S" => 0, "F" => 0];
            $subjects = [];
            $subject_averages = [];
            $top_students = [];

            $result = $conn->query("SELECT s.first_name, s.middle_name, s.last_name, AVG(c.marks) as avg_mark
                FROM class_marks c
                JOIN students s ON s.student_id = c.student_id
                WHERE c.exam_id = '$exam_id' AND c.class = '$class'
                GROUP BY c.student_id");

            while ($row = $result->fetch_assoc()) {
                $avg = round($row['avg_mark'], 2);
                $grade = calculate_grade($avg);
                $grades[$grade]++;
                $name = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                $top_students[] = ['name' => $name, 'avg' => $avg];
            }

            usort($top_students, fn($a, $b) => $b['avg'] <=> $a['avg']);
            $top_students = array_slice($top_students, 0, 10);

            $subject_query = $conn->query("SELECT subject_name, AVG(marks) as avg_mark FROM class_marks
                WHERE exam_id = '$exam_id' AND class = '$class'
                GROUP BY subject_name");

            while ($row = $subject_query->fetch_assoc()) {
                $subjects[] = strtoupper($row['subject_name']);
                $subject_averages[] = round($row['avg_mark'], 2);
            }
        ?>
        <div id="charts">
            <h4>Grade Distribution</h4>
            <canvas id="gradeChart"></canvas>

            <h4>Subject Averages Analysis</h4>
            <canvas id="subjectChart"></canvas>

            <h4>Top Performers</h4>
            <canvas id="topChart"></canvas>
        </div>

        <script>
            const gradeData = <?= json_encode(array_values($grades)) ?>;
            const subjectLabels = <?= json_encode($subjects) ?>;
            const subjectData = <?= json_encode($subject_averages) ?>;
            const topLabels = <?= json_encode(array_column($top_students, 'name')) ?>;
            const topData = <?= json_encode(array_column($top_students, 'avg')) ?>;

            new Chart(document.getElementById('gradeChart'), {
                type: 'bar',
                data: {
                    labels: ['A', 'B', 'C', 'D', 'E', 'S', 'F'],
                    datasets: [{
                        label: 'Grade Distribution',
                        data: gradeData,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    }]
                }
            });

            new Chart(document.getElementById('subjectChart'), {
                type: 'bar',
                data: {
                    labels: subjectLabels,
                    datasets: [{
                        label: 'Subject Averages',
                        data: subjectData,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }]
                }
            });

            new Chart(document.getElementById('topChart'), {
                type: 'bar',
                data: {
                    labels: topLabels,
                    datasets: [{
                        label: 'Top 10 Students',
                        data: topData,
                        backgroundColor: 'rgba(255, 159, 64, 0.6)'
                    }]
                }
            });
        </script>
        <?php endif; ?>
    </div>
</main>
<script src="script.js"></script>
</body>
</html>

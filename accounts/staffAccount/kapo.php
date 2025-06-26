<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff.php");
    exit();
}

include 'classes/connect.php';

// Function to calculate grades based on average
function calculate_grade($average) {
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B';
    if ($average >= 60) return 'C';
    if ($average >= 50) return 'D';
    if ($average >= 40) return 'E';
    if ($average >= 35) return 'S';
    if ($average >= 0) return 'F';
    return '-';
}

$results = [];
$examname = '';
$upperclass = '';
$subject_list = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST)) {
    $class = $conn->real_escape_string($_POST['class']);
    $exam_id = $conn->real_escape_string($_POST['exam_id']);
    $upperclass = strtoupper($class);

    // Get all subjects
    $subject_result = $conn->query("SELECT DISTINCT UPPER(subject_name) AS subject_name FROM class_marks WHERE exam_id = $exam_id AND class = '$class'");
    while ($row = $subject_result->fetch_assoc()) {
        $subject_list[] = $row['subject_name'];
    }

    // Get students
    $student_result = $conn->query("
        SELECT DISTINCT s.student_id, s.first_name, s.middle_name, s.last_name
        FROM students s
        JOIN class_marks c ON s.student_id = c.student_id
        WHERE c.exam_id = $exam_id AND c.class = '$class'
    ");

    if (!$student_result) {
        die('Error fetching students: ' . $conn->error);
    }

    while ($student_row = $student_result->fetch_assoc()) {
        $student_id = $student_row['student_id'];
        $student_name = htmlspecialchars(trim($student_row['first_name'] . ' ' . $student_row['middle_name'] . ' ' . $student_row['last_name']));

        $results[$student_id] = [
            'name' => $student_name,
            'marks' => [],
            'total' => '-',
            'average' => '-',
            'grade' => '-',
            'position' => '-',
        ];

        $subject_query = $conn->query("
            SELECT UPPER(subject_name) AS subject_name
            FROM class_marks
            WHERE student_id = '$student_id' AND exam_id = $exam_id AND class = '$class'
        ");
        $registered_subjects = [];
        while ($sub_row = $subject_query->fetch_assoc()) {
            $registered_subjects[] = $sub_row['subject_name'];
        }

        $total = 0;
        $count = 0;
        $has_all_marks = true;

        foreach ($subject_list as $subject) {
            if (in_array($subject, $registered_subjects)) {
                $safe_subject = $conn->real_escape_string($subject);
                $mark_query = $conn->query("SELECT marks FROM class_marks
                                            WHERE student_id = '$student_id'
                                            AND exam_id = $exam_id
                                            AND class = '$class'
                                            AND UPPER(subject_name) = '$safe_subject'
                                            LIMIT 1");

                if ($mark_row = $mark_query->fetch_assoc()) {
                    $mark = $mark_row['marks'];
                    $mark_val = is_numeric($mark) ? (int)$mark : '-';
                    $results[$student_id]['marks'][$subject] = $mark_val;

                    if (is_numeric($mark_val)) {
                        $total += $mark_val;
                        $count++;
                    } else {
                        $results[$student_id]['marks'][$subject] = '-';
                        $has_all_marks = false;
                    }
                } else {
                    $results[$student_id]['marks'][$subject] = '-';
                    $has_all_marks = false;
                }
            } else {
                $results[$student_id]['marks'][$subject] = ''; // Not registered
            }
        }

        if ($has_all_marks && $count > 0) {
            $avg = round($total / $count, 2);
            $results[$student_id]['total'] = $total;
            $results[$student_id]['average'] = $avg;
            $results[$student_id]['grade'] = calculate_grade($avg);
        }
    }

    // Ranking
    $average_marks = [];
    foreach ($results as $student_id => $student_data) {
        if (is_numeric($student_data['average'])) {
            $average_marks[$student_id] = $student_data['average'];
        }
    }
    arsort($average_marks);
    $rank = 1;
    foreach ($average_marks as $student_id => $avg) {
        $results[$student_id]['position'] = $rank++;
    }

    // Exam name
    $exam_query = $conn->query("SELECT exam_name FROM examinations WHERE exam_id = '$exam_id'");
    if ($exam_query) {
        $exam_row = $exam_query->fetch_assoc();
        $examname = $exam_row['exam_name'];
    }
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

<style>

/* Main Content Area */
main {
    padding: 30px;
    background: #ffffff;
    margin: 20px auto;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    overflow-x: auto; /* Allows horizontal scrolling for tables */
    max-width: 1200px;
}

h4 {
    text-align: center;
    margin-bottom: 25px;
    color: #0056b3; /* Consistent blue for headings */
    font-size: 24px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 1em;
    min-width: 900px; /* Ensures table doesn't look too cramped on medium screens */
    margin-bottom: 20px;
}

thead {
    background-color: #0056b3; /* Blue header for table */
    color: white;
}

th, td {
    padding: 12px 8px;
    border: 1px solid #e0e0e0; /* Lighter border for a softer look */
    text-align: center;
    vertical-align: middle;
}

th {
    font-weight: 600;
    white-space: nowrap; /* Prevent text wrapping in headers */
}

/* Specific column widths */
td:first-child, th:first-child {
    text-align: left;
    min-width: 250px; /* Increased width for student names */
    max-width: 350px;
    word-wrap: break-word; /* Allows long names to wrap */
}

/* Zebra striping for table rows */
tbody tr:nth-child(even) {
    background-color: #f9f9f9; /* Slightly off-white */
}

tbody tr:nth-child(odd) {
    background-color: #ffffff;
}

tbody tr:hover {
    background-color: #e9f5ff; /* Light blue on hover for rows */
}

/* Center alignment for specific cells */
td.center {
    text-align: center;
}

/* No results message */
main p {
    text-align: center;
    font-size: 1.1em;
    color: #555;
    padding: 20px;
    border: 1px dashed #ccc;
    border-radius: 5px;
    background-color: #fcfcfc;
}

/* --- Responsive Design --- */

/* Tablet Styles */
@media screen and (max-width: 768px) {

    main {
        padding: 15px;
        margin: 10px auto;
        border-radius: 5px;
    }

    h4 {
        font-size: 20px;
        margin-bottom: 20px;
    }

    table {
        font-size: 0.9em;
        min-width: 700px; /* Adjust min-width for tablets */
    }

    th, td {
        padding: 10px 5px;
    }

    td:first-child, th:first-child {
        min-width: 200px; /* Adjust name column for tablets */
        max-width: 280px;
    }
}

/* Phone Styles */
@media screen and (max-width: 480px) {

    main {
        padding: 10px;
        margin: 5px auto;
    }

    h4 {
        font-size: 18px;
        margin-bottom: 15px;
    }

    table {
        font-size: 0.75em; /* Smaller font for phone screens */
        min-width: 600px; /* Ensure horizontal scroll on smaller phones */
    }

    th, td {
        padding: 8px 4px;
    }

    td:first-child, th:first-child {
        min-width: 180px; /* Further adjust name column for phones */
        max-width: 250px;
    }
}
</style>


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
        <?php if (!empty($results)) : ?>
            <h4>RESULTS FOR <?php echo htmlspecialchars($examname) . ' ' . htmlspecialchars($upperclass); ?></h4>
            <table border="1">
                <thead>
                    <tr>
                        <th>STUDENT NAME</th>
                        <?php foreach ($subject_list as $subject) : ?>
                            <th><?php echo htmlspecialchars($subject); ?></th>
                        <?php endforeach; ?>
                        <th>TOTAL</th>
                        <th>AVERAGE</th>
                        <th>GRADE</th>
                        <th>POSITION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result) : ?>
                        <tr>
                            <td><?php echo $result['name']; ?></td>
                            <?php foreach ($subject_list as $subject) : ?>
                                <td class="center">
                                    <?php echo $result['marks'][$subject] !== '' ? htmlspecialchars($result['marks'][$subject]) : ''; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="center"><?php echo htmlspecialchars($result['total']); ?></td>
                            <td class="center"><?php echo htmlspecialchars($result['average']); ?></td>
                            <td class="center"><?php echo htmlspecialchars($result['grade']); ?></td>
                            <td class="center"><?php echo htmlspecialchars($result['position']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($results)) : ?>
            <p>No results found for the selected class and exam.</p>
        <?php endif; ?>
    </main>

</body>
<script src="script.js"></script>
</html>

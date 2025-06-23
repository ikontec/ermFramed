<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

include 'classes/connect.php';

if (!isset($_GET['student_id']) || !isset($_GET['subject'])) {
    echo "Missing student ID or subject.";
    exit();
}

$student_id = $conn->real_escape_string($_GET['student_id']);
$subject = strtoupper($conn->real_escape_string($_GET['subject']));

// Fetch student details
$student_query = $conn->query("SELECT first_name, middle_name, last_name FROM students WHERE student_id = '$student_id'");
if (!$student_query || $student_query->num_rows === 0) {
    echo "Student not found.";
    exit();
}
$student = $student_query->fetch_assoc();

// Fetch last 3 exams
$exam_query = $conn->query("SELECT exam_id, exam_name, date FROM examinations ORDER BY date DESC LIMIT 3");
$exams = [];
while ($row = $exam_query->fetch_assoc()) {
    $exams[] = $row;
}

// Function to calculate grade
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

// Fetch marks for the 3 exams
$progress = [];
foreach ($exams as $exam) {
    $exam_id = $exam['exam_id'];
    $mark_result = $conn->query("SELECT marks FROM class_marks WHERE student_id = '$student_id' AND exam_id = '$exam_id' AND subject_name = '$subject' LIMIT 1");
    $marks = '-';
    $grade = '-';

    if ($mark_result && $mark_result->num_rows > 0) {
        $row = $mark_result->fetch_assoc();
        $marks = $row['marks'];
        if (is_numeric($marks)) {
            $grade = calculate_grade($marks);
        }
    }

    $progress[] = [
        'exam_name' => $exam['exam_name'],
        'marks' => $marks,
        'grade' => $grade
    ];
}

// Output result as HTML table
?>
<div style="padding: 10px; font-family: Arial;">
    <h4>Progress for <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></h4>
    <table border="1" style="border-collapse: collapse; width: 100%; text-align: center;">
        <thead>
            <tr style="background-color: #007bff; color: white;">
                <th>Exam</th>
                <th>Marks</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($progress as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['marks']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

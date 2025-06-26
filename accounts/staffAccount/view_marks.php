<?php
session_start();

// Ensure the staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include 'classes/connect.php';

// Get the latest exam_id
$latest_exam_result = $conn->query("SELECT exam_id FROM examinations ORDER BY date DESC LIMIT 1");
$latest_exam_id = null;
if ($latest_exam_result && $latest_exam_result->num_rows > 0) {
    $latest_exam_id = $latest_exam_result->fetch_assoc()['exam_id'];
}

// Function to calculate the grade
function calculate_grade($marks) {
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    if ($marks >= 40) return 'E';
    if ($marks >= 35) return 'S';
    return 'F';
}

// Handle marks update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_marks'])) {
    $marks = $_POST['marks'];
    $exam_id = $_POST['exam_id'];
    $subject = strtoupper($_POST['subject']);
    $class = $_POST['class'];

    if ($exam_id == $latest_exam_id) {
        foreach ($marks as $student_id => $mark) {
            $mark = $conn->real_escape_string(trim($mark));
            $student_id = $conn->real_escape_string($student_id);

            $value = ($mark === '') ? '-' : $mark;

            $conn->query("
                UPDATE class_marks 
                SET marks = '$value' 
                WHERE student_id = '$student_id' 
                AND exam_id = '$exam_id' 
                AND subject_name = '$subject' 
                AND class = '$class'
            ");
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Results Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Staff_Styles/pc.css">
  <link rel="stylesheet" href="Staff_Styles/tablet.css">
  <link rel="stylesheet" href="Staff_Styles/phone.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

<main class="upt">
  <form class="form" method="post">
    <select name="subject" required>
      <option value="">Select Subject</option>
      <?php
        $subjects = ["English", "Kiswahili", "Maths", "Biology", "Physics", "Chemistry", "Geography", "History", "Civics", "Computer"];
        foreach ($subjects as $subj) {
            echo "<option value=\"$subj\">$subj</option>";
        }
      ?>
    </select><br><br>

    <select name="class" required>
      <option value="">Select Class</option>
      <?php
        $classes = ["Form One", "Form Two", "Form Three", "Form Four"];
        foreach ($classes as $cls) {
            echo "<option value=\"$cls\">$cls</option>";
        }
      ?>
    </select><br><br>

    <select name="exam" required>
      <option value="">Select Exam</option>
      <?php
        $exam_result = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC");
        while ($exam = $exam_result->fetch_assoc()) {
            echo "<option value=\"{$exam['exam_id']}\">{$exam['exam_name']}</option>";
        }
      ?>
    </select><br><br>

    <button class="nav-button" type="submit" name="view_marks">View Class Marks</button>
  </form>

  <div class="form">
  <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_marks'])) {
        $subject = strtoupper($_POST['subject']);
        $class = $_POST['class'];
        $exam_id = $_POST['exam'];

        $is_latest_exam = ($exam_id == $latest_exam_id);

        $query = "
            SELECT CM.student_id, S.first_name, S.middle_name, S.last_name, CM.marks 
            FROM class_marks CM
            JOIN students S ON CM.student_id = S.student_id
            WHERE CM.exam_id = '$exam_id' AND CM.subject_name = '$subject' AND CM.class = '$class'
            ORDER BY S.first_name
        ";
        $result = $conn->query($query);

        if ($result) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $row['grade'] = ($row['marks'] !== '-' && $row['marks'] !== '' && $row['marks'] !== null) 
                    ? calculate_grade($row['marks']) : '-';
                $data[] = $row;
            }

            $_SESSION['class_marks'] = $data;
            echo "<h4>CLASS MARKS FOR $subject - $class</h4>";

            echo '<form method="post">';
            echo '<table border="1">';
            echo '<thead><tr><th>Student Name</th><th>Marks</th><th>Grade</th></tr></thead><tbody>';

            foreach ($data as $row) {
                $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                echo "<tr>";
                echo "<td>$fullName</td>";
                echo "<td class='mark'>";
                if ($is_latest_exam) {
                    echo "<input name='marks[{$row['student_id']}]' value='" . htmlspecialchars($row['marks']) . "'>";
                } else {
                    echo htmlspecialchars($row['marks']);
                }
                echo "</td>";
                echo "<td class='mark'>{$row['grade']}</td>";
                echo "</tr>";
            }

            echo '</tbody></table>';
            echo "<input type='hidden' name='exam_id' value='$exam_id'>";
            echo "<input type='hidden' name='subject' value='$subject'>";
            echo "<input type='hidden' name='class' value='$class'><br>";

            if ($is_latest_exam) {
                echo '<button class="nav-button" type="submit" name="update_marks">Update Marks</button>';
            } else {
                echo '<p style="color:red; font-weight:bold;">This exam is locked from editing. You can only view the marks.</p>';
            }

            echo '</form>';
        } else {
            echo "<p>Error fetching marks: " . $conn->error . "</p>";
        }
    }
  ?>
  </div>
</main>
</body>
    <script src="script.js"></script>
</html>

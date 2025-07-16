<?php
session_start();
// Ensure the staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
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
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var currentYear = new Date().getFullYear();
      document.getElementById('current_year').value = currentYear;
    });
  </script>
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
      <input type="hidden" id="current_year" name="current_year">
      
      <select id="subject" name="subject" required>
        <option value="">Select Subject</option>
        <option value="english">English</option>
        <option value="kiswahili">Kiswahili</option>
        <option value="maths">Maths</option>
        <option value="biology">Biology</option>
        <option value="physics">Physics</option>
        <option value="chemistry">Chemistry</option>
        <option value="geography">Geography</option>
        <option value="history">History</option>
        <option value="civics">Civics</option>
        <option value="computer">Computer</option>
      </select><br><br>

      <select id="class" name="class" required>
        <option value="">Select Class</option>
        <option value="Form One">Form One</option>
        <option value="Form Two">Form Two</option>
        <option value="Form Three">Form Three</option>
        <option value="Form Four">Form Four</option>
      </select><br><br>
      <button type="submit" name="fetch_students">Retrieve Students</button>

      <button type="button" name='excel' onclick="location.href='drag.php'">Use Excel File</button>
    </form>
    <div class="form">
      <?php
      include 'classes/connect.php';

      // Fetch the latest exam_id and exam_name
      $exam_result = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC LIMIT 1");

      if ($exam_result && $exam_result->num_rows > 0) {
          $latest_exam = $exam_result->fetch_assoc();
          $exam_id   = $latest_exam['exam_id'];
          $exam_name = $latest_exam['exam_name'];

          // Function to calculate grades
          function calculate_grade($marks) {
              if ($marks >= 80) return 'A';
              if ($marks >= 70) return 'B';
              if ($marks >= 60) return 'C';
              if ($marks >= 50) return 'D';
              if ($marks >= 40) return 'E';
              if ($marks >= 35) return 'S';
              if ($marks >= 0)  return 'F';
              return 'F';
          }

          // Retrieve students for the selected class and subject and display them
          if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fetch_students'])) {
              $class        = $conn->real_escape_string($_POST['class']);
              $subject      = $conn->real_escape_string($_POST['subject']);
              $current_year = $conn->real_escape_string($_POST['current_year']);

              // Use lowercase for consistency
              $subject_lower = strtolower($subject);

              // Check if the subject table exists
              $table_check = $conn->query("SHOW TABLES LIKE '$subject_lower'");
              if ($table_check->num_rows == 0) {
                  echo "Subject table '$subject_lower' does not exist.";
                  exit();
              }

              // Fetch students from the selected class and subject for the current year
              $students_query = $conn->query("
                  SELECT S.student_id, S.first_name, S.middle_name, S.last_name 
                  FROM students S 
                  JOIN $subject_lower SJ ON S.student_id = SJ.student_id 
                  WHERE S.class = '$class' AND SJ.year = '$current_year'
                  ORDER BY S.first_name
              ");

              if ($students_query->num_rows > 0) {
                  echo '<form class="list" method="post">';
                  echo '<input type="hidden" name="subject" value="' . htmlspecialchars($subject) . '">';
                  echo '<input type="hidden" name="class" value="' . htmlspecialchars($class) . '">';
                  echo '<input type="hidden" name="current_year" value="' . htmlspecialchars($current_year) . '">';
                  echo '<table border="1">';
                  echo '<thead>';
                  echo '<tr>';
                  echo '<th>Student Name</th>';
                  echo '<th>Marks</th>';
                  echo '</tr>';
                  echo '</thead>';
                  echo '<tbody>';
                  while ($student = $students_query->fetch_assoc()) {
                      echo '<tr>';
                      echo '<td>';
                      echo '<input type="hidden" name="students[' . $student['student_id'] . '][student_id]" value="' . $student['student_id'] . '">';
                      echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
                      echo '</td>';
                      echo '<td class="mark"><input type="number" name="students[' . $student['student_id'] . '][marks]"></td>';
                      echo '</tr>';
                  }
                  echo '</tbody>';
                  echo '</table>';
                  echo '<br>';
                  echo '<button class="nav-button" type="submit" name="save_results">Save Results</button>';
                  echo '</form>';
              } else {
                  echo 'No students found for the selected class and year.';
              }
          }

          // Save results and display class marks
          if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_results'])) {
              $subject      = $conn->real_escape_string($_POST['subject']);
              $class        = $conn->real_escape_string($_POST['class']);
              $current_year = $conn->real_escape_string($_POST['current_year']);
              $students     = isset($_POST['students']) ? $_POST['students'] : [];

              $subject_lower = strtolower($subject);

              // Check if the subject table exists
              $table_check = $conn->query("SHOW TABLES LIKE '$subject_lower'");
              if ($table_check->num_rows == 0) {
                  echo "Subject table '$subject_lower' does not exist.";
                  exit();
              }

              $error = false;
              // Prepare statement for inserting results
              $stmt = $conn->prepare("INSERT INTO class_marks (student_id, exam_id, subject_name, marks, year, class) VALUES (?, ?, ?, ?, ?, ?)");
              if (!$stmt) {
                  die("Preparation failed: " . $conn->error);
              }

              foreach ($students as $student) {
                  $student_id  = intval($student['student_id']);
                  // Use trim to determine if a value was entered. This avoids treating a valid 0 as empty.
                  $studentMark = trim($student['marks']);

                  // Check if the student already has an entry
                  $check_query = "SELECT * FROM class_marks WHERE student_id = $student_id AND exam_id = '$exam_id' AND subject_name = '$subject_lower'";
                  $result = $conn->query($check_query);

                  if ($result && $result->num_rows > 0) {
                      // Skip if the record already exists
                      continue;
                  } else {
                      // Bind parameters; if mark is empty, we store NULL
                      if ($studentMark === '') {
                          $marks = null;
                      } else {
                          $marks = floatval($studentMark);
                      }
                      // Bind and execute the prepared statement
                      $stmt->bind_param("iissss", $student_id, $exam_id, $subject_lower, $marks, $current_year, $class);
                      if (!$stmt->execute()) {
                          $error = true;
                          echo 'Error inserting marks for student ' . $student_id . ': ' . $stmt->error;
                      }
                  }
              }
              $stmt->close();

              if (!$error) {
                  // Retrieve and display the saved results
                  $results_query = $conn->query("
                      SELECT CM.student_id, S.first_name, S.middle_name, S.last_name, CM.marks 
                      FROM class_marks CM
                      JOIN students S ON CM.student_id = S.student_id
                      WHERE CM.exam_id = '$exam_id' AND CM.subject_name = '$subject_lower' AND CM.year = '$current_year' AND CM.class = '$class'
                      ORDER BY CM.marks DESC
                  ");

                  $subject_upper = strtoupper($subject_lower);

                  if ($results_query->num_rows > 0) {
                      echo '<h4>CLASS MARKS FOR ' . htmlspecialchars($subject_upper) . ' ' . htmlspecialchars($exam_name) . '</h4>';
                      echo '<table border="1">';
                      echo '<thead>';
                      echo '<tr>';
                      echo '<th>Student Name</th>';
                      echo '<th>Marks</th>';
                      echo '<th>Grade</th>';
                      echo '</tr>';
                      echo '</thead>';
                      echo '<tbody>';
                      while ($result = $results_query->fetch_assoc()) {
                          $grade = calculate_grade($result['marks']);
                          echo '<tr>';
                          echo '<td>' . htmlspecialchars($result['first_name'] . ' ' . $result['middle_name'] . ' ' . $result['last_name']) . '</td>';
                          echo '<td class="mark">' . $result['marks'] . '</td>';                          
                          echo '<td class="mark">' . $grade . '</td>';
                          echo '</tr>';
                      }
                      echo '</tbody>';
                      echo '</table>';
                      echo '<br>';
                  } else {
                      echo 'No results found.';
                  }
              } else {
                  echo 'There were errors while saving the results.';
              }
          }

      } else {
          echo "No exam available at this time";
      }
      ?>
    </div>
  </main>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const markInputs = Array.from(document.querySelectorAll('input[type="number"]'));

      markInputs.forEach((input, index) => {
        input.addEventListener('keydown', function (e) {
          const cols = 1; // Only one column for marks
          const total = markInputs.length;

          // Get the current input's index
          const currentIndex = index;

          // Arrow navigation
          if (e.key === "ArrowDown") {
            e.preventDefault();
            const nextIndex = currentIndex + cols;
            if (nextIndex < total) markInputs[nextIndex].focus();
          }

          if (e.key === "ArrowUp") {
            e.preventDefault();
            const prevIndex = currentIndex - cols;
            if (prevIndex >= 0) markInputs[prevIndex].focus();
          }

          if (e.key === "ArrowRight") {
            e.preventDefault();
            const nextIndex = currentIndex + 1;
            if (nextIndex < total) markInputs[nextIndex].focus();
          }

          if (e.key === "ArrowLeft") {
            e.preventDefault();
            const prevIndex = currentIndex - 1;
            if (prevIndex >= 0) markInputs[prevIndex].focus();
          }
        });
      });
    });
  </script>
  <script src="script.js"></script>
</body>
</html>


<?php
session_start();
// Ensure the staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

include '../../classes/connect.php'; // Adjust to your database connection path

// Static subject list
$subjects = [
    'English',
    'Mathematics',
    'Biology',
    'Chemistry',
    'Physics',
    'Geography',
    'History',
    'Kiswahili',
    'Computer'
];


// Get exams from DB, latest first
$exams = [];
$latest_exam_id = '';
$latest_exam_name = '';
$exam_q = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC, exam_id DESC");
if ($exam_q) {
    while ($row = $exam_q->fetch_assoc()) {
        $exams[] = $row;
    }
    if (count($exams) > 0) {
        $latest_exam_id = $exams[0]['exam_id'];
        $latest_exam_name = $exams[0]['exam_name'];
    }
}

$message = '';


// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_class = $_POST['class'] ?? '';
    $selected_subject = $_POST['subject'] ?? '';
    $selected_exam = $_POST['exam'] ?? '';
    // If not set, use latest exam
    if (!$selected_exam && $latest_exam_id) {
        $selected_exam = $latest_exam_id;
    }
    $year = date('Y');

    if (!$selected_class || !$selected_subject || !$selected_exam) {
        if (!$selected_exam && !$latest_exam_id) {
            $message = '<div style="color:red;">No exam available. Please add an exam first.</div>';
        } else {
            $message = '<div style="color:red;">Please select all fields.</div>';
        }
    } elseif (!isset($_FILES['marks_file']) || $_FILES['marks_file']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div style="color:red;">File upload error.</div>';
    } else {
        $file_tmp = $_FILES['marks_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['marks_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'csv'])) {
            $message = '<div style="color:red;">Only .xlsx or .csv files are allowed.</div>';
        } else {
            try {
                $reader = ($ext === 'csv')
                    ? new PhpOffice\PhpSpreadsheet\Reader\Csv()
                    : new PhpOffice\PhpSpreadsheet\Reader\Xlsx();

                $spreadsheet = $reader->load($file_tmp);
                $rows = $spreadsheet->getActiveSheet()->toArray();

                $inserted = 0;
                $skipped_exists = 0;
                $skipped_missing = 0;
                $not_found = 0;

                $subject_lower = strtolower($selected_subject);

                foreach ($rows as $i => $row) {
                    if ($i === 0) continue; // skip header
                    $first = trim($row[0] ?? '');
                    $last = trim($row[2] ?? '');
                    $marks = trim($row[3] ?? '');

                    if ($first === '' || $last === '') {
                        $not_found++;
                        continue;
                    }

                    // Find student_id
                    $stmt = $conn->prepare("SELECT student_id FROM students WHERE first_name=? AND last_name=? AND class=? LIMIT 1");
                    $stmt->bind_param('sss', $first, $last, $selected_class);
                    $stmt->execute();
                    $stmt->bind_result($student_id);

                    if ($stmt->fetch()) {
                        $stmt->close();
                        // Check if mark already exists for this student/subject/exam
                        $check = $conn->prepare("SELECT 1 FROM class_marks WHERE student_id=? AND subject_name=? AND exam_id=? AND class=? AND year=? LIMIT 1");
                        $check->bind_param('isiss', $student_id, $subject_lower, $selected_exam, $selected_class, $year);
                        $check->execute();
                        $check->store_result();
                        if ($check->num_rows > 0) {
                            $skipped_exists++;
                            $check->close();
                            continue;
                        }
                        $check->close();

                        // If marks is empty, store as NULL
                        if ($marks === '' || !is_numeric($marks)) {
                            $skipped_missing++;
                            continue;
                        }

                        $ins = $conn->prepare("INSERT INTO class_marks (student_id, subject_name, marks, class, year, exam_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->bind_param('isissi', $student_id, $subject_lower, $marks, $selected_class, $year, $selected_exam);
                        $ins->execute();
                        $ins->close();
                        $inserted++;
                    } else {
                        $not_found++;
                        $stmt->close();
                    }
                }

                $message = "<div style='color:green;'>Upload complete.<br>Inserted: $inserted.<br>Skipped (already exists): $skipped_exists.<br>Skipped (missing/invalid marks): $skipped_missing.<br>Skipped (student not found): $not_found.</div>";
            } catch (Exception $e) {
                $message = '<div style="color:red;">Error reading file: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
  <title>Upload Marks (Excel/CSV)</title>
  <link rel="stylesheet" href="Staff_Styles/pc.css">
  <link rel="stylesheet" href="Staff_Styles/tablet.css">
  <link rel="stylesheet" href="Staff_Styles/phone.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .upt { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
    .msg { margin-top: 20px; }
    .download-btn { background: #28a745; }
    .form label { margin-top: 15px; display: block; }
    .form input[type="file"], .form select, .form button { width: 100%; padding: 8px; margin-top: 5px; }
    .form button { background: #007bff; color: white; border: none; border-radius: 4px; margin-top: 20px; cursor: pointer; }
    .form button:hover { background: #0056b3; }
    @media (max-width: 700px) { .upt { padding: 10px; } }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var currentYear = new Date().getFullYear();
      var yearInput = document.getElementById('current_year');
      if (yearInput) yearInput.value = currentYear;
    });
    function loadClasses(subject) {
      const classSelect = document.getElementById('class');
      classSelect.innerHTML = '<option>Loading...</option>';
      classSelect.disabled = true;
      const allClasses = ['Form One', 'Form Two', 'Form Three', 'Form Four'];
      setTimeout(() => {
        classSelect.innerHTML = '<option value="">--Select Class--</option>';
        allClasses.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c;
          opt.textContent = c;
          classSelect.appendChild(opt);
        });
        classSelect.disabled = false;
      }, 300);
    }
    function downloadClassList() {
      const subject = document.getElementById('subject').value;
      const className = document.getElementById('class').value;
      if (!subject || !className) {
        alert("Please select both subject and class.");
        return;
      }
      window.location.href = `download_class_list.php?subject=${encodeURIComponent(subject)}&class=${encodeURIComponent(className)}`;
    }
  </script>
</head>
<body>
  <header>
    <?php
      // Fetch data from the parent table for logo/title
      $get_data = $conn->query("SELECT image_id, image, title FROM back_site");
      if ($get_data) {
        $site_data = $get_data->fetch_assoc();
        $title = $site_data['title'];
        $image = $site_data['image'];
      } else {
        $title = 'School';
        $image = '';
      }
    ?>
    <?php if (!empty($image)): ?>
      <img src="../../<?= htmlspecialchars($image) ?>" alt="school logo" class="logo">
    <?php endif; ?>
    <h1 class="schoolName"><?= htmlspecialchars($title) ?></h1>
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
    <form class="form" method="post" enctype="multipart/form-data">
      <input type="hidden" id="current_year" name="current_year">
      <h2>Upload Student Marks (Excel/CSV)</h2>
      <?php if ($message) echo "<div class='msg'>$message</div>"; ?>
      <label for="subject">Subject:</label>
      <select name="subject" id="subject" required onchange="loadClasses(this.value)">
        <option value="">--Select Subject--</option>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_POST['subject']) && $_POST['subject'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
      <label for="class">Class:</label>
      <select name="class" id="class" required disabled>
        <option value="">--Select Class--</option>
      </select>
      <label for="exam">Exam:</label>
      <label for="marks_file">Excel/CSV File (.xlsx or .csv):</label>
      <input type="file" name="marks_file" accept=".xlsx,.csv" required>
      <button type="button" class="download-btn" onclick="downloadClassList()">Download Class List</button>
      <button type="submit">Upload Marks</button>
    </form>
  </main>
  <script src="script.js"></script>
</body>
</html>

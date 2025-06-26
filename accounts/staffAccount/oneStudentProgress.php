<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

include 'classes/connect.php';

function getStudentFullName($row) {
    return htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
}

// Get subjects and class list
$subjects = ['english', 'kiswahili', 'maths', 'biology', 'physics', 'chemistry', 'geography', 'history', 'civics', 'computer'];
$classes = [];
$res = $conn->query("SELECT DISTINCT class FROM students ORDER BY class");
while ($row = $res->fetch_assoc()) {
    $classes[] = $row['class'];
}

$students = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_students'])) {
    $selected_subject = strtolower(trim($_POST['subject']));
    $selected_class = trim($_POST['class']);

    $stmt = $conn->prepare("SELECT * FROM students WHERE class = ?");
    $stmt->bind_param("s", $selected_class);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        .popup { display: none; position: fixed; top: 10%; left: 10%; width: 80%; background: white; border: 2px solid #444; padding: 20px; box-shadow: 0 0 10px #aaa; }
        .popup-header { display: flex; justify-content: space-between; }
        .close-btn { cursor: pointer; color: red; font-weight: bold; }
    </style>
    
    <script>
        function showProgress(studentId, name, subject) {
            fetch('student_progress_data.php?student_id=' + studentId + '&subject=' + subject)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('progress-content').innerHTML = data;
                    document.getElementById('progress-popup').style.display = 'block';
                });
        }

        function closePopup() {
            document.getElementById('progress-popup').style.display = 'none';
        }
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
    <h2>Check Individual Student Progress</h2>
    <form method="POST">
        <label>Select Subject:</label>    
        <select name="subject" required>
            <option value="">--Subject--</option>
            <?php foreach ($subjects as $subj): ?>
                <option value="<?= $subj ?>"><?= ucfirst($subj) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Select Class:</label>
        <select name="class" required>
            <option value="">--Class--</option>
            <?php foreach ($classes as $cls): ?>
                <option value="<?= $cls ?>"><?= $cls ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="get_students" class="btn">Get Student List</button>
    </form>

    <?php if (!empty($students)): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Check Progress</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $stu): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= getStudentFullName($stu) ?></td>
                        <td>
                            <button class="btn" onclick="showProgress('<?= $stu['student_id'] ?>', '<?= getStudentFullName($stu) ?>', '<?= $_POST['subject'] ?>')">Check Progress</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="popup" id="progress-popup">
        <div class="popup-header">
            <h3 id="progress-title"></h3>
            <span class="close-btn" onclick="closePopup()">&times;</span>
        </div>
        <div id="progress-content"></div>
    </div>
    <script src="script.js"></script>
</body>
</html>

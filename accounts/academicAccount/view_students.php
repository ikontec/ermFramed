<?php
session_start();
// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

$error = '';
$success = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Dashboard</title>
    <link rel="stylesheet" href="Academic_Styles/pc.css">
    <link rel="stylesheet" href="Academic_Styles/tablet.css">
    <link rel="stylesheet" href="Academic_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    <main>
        <div class="details">
            <h3>Student Details</h3>

            <?php
            if (isset($_GET['student_id'])) {
                $student_id = intval($_GET['student_id']);

                // Fetch student data
                $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, date_of_birth, gender, class, url, username FROM students WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $student = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                // Fetch parent data
                $stmt = $conn->prepare("SELECT first_name, last_name, gender, relationship, phone, email FROM parent WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $parent = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($student) {
                    // Display student details
                    echo '<p><strong>Name:</strong> ' . htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['middle_name']) . ' ' . htmlspecialchars($student['last_name']) . '</p>';
                    echo '<p><strong>Date of Birth:</strong> ' . htmlspecialchars($student['date_of_birth']) . '</p>';
                    echo '<p><strong>Gender:</strong> ' . htmlspecialchars($student['gender']) . '</p>';
                    echo '<p><strong>Class:</strong> ' . htmlspecialchars($student['class']) . '</p>';
                    echo '<p><strong>Username:</strong> ' . htmlspecialchars($student['username']) . '</p>';
                    echo '<p><strong>Password:</strong> ' . htmlspecialchars($student['url']) . '</p>';

                } else {
                    echo "No details found for the provided student ID.";
                }

                if ($parent) {
                    // Display parent details
                    echo '<h3>Parent Details</h3>';
                    echo '<p><strong>Name:</strong> ' . htmlspecialchars($parent['first_name']) . ' ' . htmlspecialchars($parent['last_name']) . '</p>';
                    echo '<p><strong>Gender:</strong> ' . htmlspecialchars($parent['gender']) . '</p>';
                    echo '<p><strong>Relationship:</strong> ' . htmlspecialchars($parent['relationship']) . '</p>';
                    echo '<p><strong>Phone:</strong> 0' . htmlspecialchars($parent['phone']) . '</p>';
                    echo '<p><strong>Email:</strong> ' . htmlspecialchars($parent['email']) . '</p>';
                    echo '<form action="" method="post" style="display:inline;">
                            <input type="hidden" name="student_id" value="' . htmlspecialchars($student_id) . '">
                        </form>';
                    echo '<a href="edit_student.php?student_id=' . htmlspecialchars($student_id) . '" class="edit">EDIT DETAILS</a>';

                } else {
                    echo "No parent details found for the provided student ID.";
                }
            } else {
                echo "No student ID provided.";
            }

            $conn->close();
            ?>
        </div>
    </main>
</body>
<script src="script.js"></script>
</html>

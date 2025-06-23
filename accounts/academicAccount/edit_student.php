<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}
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
    <style>
        .form-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            color: #007bff;
        }

        .form-sections {
            display: flex;
            gap: 40px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .form-box {
            flex: 1 1 48%;
            min-width: 300px;
        }

        .form-box h3 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #444;
            border-bottom: 2px solid #007bff;
            padding-bottom: 6px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn {
            display: block;
            width: 100%;
            background-color: #007bff;
            color: #fff;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        @media screen and (max-width: 768px) {
            .form-sections {
                flex-direction: column;
            }

            .form-box {
                flex: 1 1 100%;
            }

            .form-container h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<main>
    <div class="form-container">
        <h1>Edit Student Details</h1>
        <?php
        include 'classes/connect.php';

        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['student_id'])) {
            $student_id = intval($_GET['student_id']);

            $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, date_of_birth, gender, class, url FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT first_name, last_name, gender, relationship, phone, email FROM parent WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $parent = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($student && $parent) {
                echo '<form method="post" action="edit_student.php">';
                echo '<input type="hidden" name="student_id" value="' . htmlspecialchars($student_id) . '">';
                echo '<div class="form-sections">';

                echo '<div class="form-box">';
                echo '<h3>Student Details</h3>';
                echo '<div class="form-group"><label>First Name:</label><input type="text" name="student_first_name" value="' . htmlspecialchars($student['first_name']) . '" required></div>';
                echo '<div class="form-group"><label>Middle Name:</label><input type="text" name="student_middle_name" value="' . htmlspecialchars($student['middle_name']) . '"></div>';
                echo '<div class="form-group"><label>Last Name:</label><input type="text" name="student_last_name" value="' . htmlspecialchars($student['last_name']) . '" required></div>';
                echo '<div class="form-group"><label>Date of Birth:</label><input type="date" name="student_date_of_birth" value="' . htmlspecialchars($student['date_of_birth']) . '" required></div>';
                echo '<div class="form-group"><label>Gender:</label><select name="student_gender" required><option value="Male"' . ($student['gender'] == 'Male' ? ' selected' : '') . '>Male</option><option value="Female"' . ($student['gender'] == 'Female' ? ' selected' : '') . '>Female</option></select></div>';
                echo '<div class="form-group"><label>Class:</label><select name="student_class" required><option value="Form One"' . ($student['class'] == 'Form One' ? ' selected' : '') . '>Form One</option><option value="Form Two"' . ($student['class'] == 'Form Two' ? ' selected' : '') . '>Form Two</option><option value="Form Three"' . ($student['class'] == 'Form Three' ? ' selected' : '') . '>Form Three</option><option value="Form Four"' . ($student['class'] == 'Form Four' ? ' selected' : '') . '>Form Four</option></select></div>';
                echo '<div class="form-group"><label>Password:</label><input type="text" name="url" value="' . htmlspecialchars($student['url']) . '" required></div>';
                echo '</div>';

                echo '<div class="form-box">';
                echo '<h3>Parent Details</h3>';
                echo '<div class="form-group"><label>First Name:</label><input type="text" name="parent_first_name" value="' . htmlspecialchars($parent['first_name']) . '" required></div>';
                echo '<div class="form-group"><label>Last Name:</label><input type="text" name="parent_last_name" value="' . htmlspecialchars($parent['last_name']) . '" required></div>';
                echo '<div class="form-group"><label>Gender:</label><select name="parent_gender" required><option value="Male"' . ($parent['gender'] == 'Male' ? ' selected' : '') . '>Male</option><option value="Female"' . ($parent['gender'] == 'Female' ? ' selected' : '') . '>Female</option></select></div>';
                echo '<div class="form-group"><label>Relationship:</label><input type="text" name="parent_relationship" value="' . htmlspecialchars($parent['relationship']) . '" required></div>';
                echo '<div class="form-group"><label>Phone:</label><input type="text" name="parent_phone" value="' . htmlspecialchars($parent['phone']) . '" required></div>';
                echo '<div class="form-group"><label>Email:</label><input type="email" name="parent_email" value="' . htmlspecialchars($parent['email']) . '" required></div>';
                echo '</div>';

                echo '</div>'; // Close form-sections
                echo '<button type="submit" class="btn">Save Changes</button>';
                echo '</form>';
            } else {
                echo "<p>No details found for the provided student ID.</p>";
            }
        } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Process form submission
            $student_id = intval($_POST['student_id']);
            $stmt = $conn->prepare("UPDATE students SET first_name=?, middle_name=?, last_name=?, date_of_birth=?, gender=?, class=?, url=? WHERE student_id=?");
            $stmt->bind_param("sssssssi", $_POST['student_first_name'], $_POST['student_middle_name'], $_POST['student_last_name'], $_POST['student_date_of_birth'], $_POST['student_gender'], $_POST['student_class'], $_POST['url'], $student_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE parent SET first_name=?, last_name=?, gender=?, relationship=?, phone=?, email=? WHERE student_id=?");
            $stmt->bind_param("ssssssi", $_POST['parent_first_name'], $_POST['parent_last_name'], $_POST['parent_gender'], $_POST['parent_relationship'], $_POST['parent_phone'], $_POST['parent_email'], $student_id);
            $stmt->execute();
            $stmt->close();

            header("Location: view_students.php?student_id=" . $student_id);
            exit();
        }

        $conn->close();
        ?>
    </div>
</main>
<script src="script.js"></script>
</body>
</html>

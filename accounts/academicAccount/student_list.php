<?php
session_start();
// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
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
        <li><a href="academicAccount.php"><i class="fas fa-arrow-left"></i> Back</a></li>
    </ul>
</nav>

    <main>
    <div class="contain">
        <h1>Student List</h1>
        <form method="get" action="">
            <label for="class">Choose a class:</label>
            <select id="class" name="class" required>
                <option value="">Select a Class</option>
                <option value="Form One">Form One</option>
                <option value="Form Two">Form Two</option>
                <option value="Form Three">Form Three</option>
                <option value="Form Four">Form Four</option>
            </select>
            <button id="btn2" type="submit">View Students</button>
        </form>

        <?php
        include 'classes/connect.php'; // Include your database connection script

        if (isset($_GET['class']) && !empty($_GET['class'])) {
            $class = $_GET['class'];

            // Fetch students for the selected class
            $stmt = $conn->prepare("SELECT student_id, first_name, middle_name, last_name FROM students WHERE class = ? ORDER BY first_name");
            $stmt->bind_param("s", $class);
            $stmt->execute();
            $students = $stmt->get_result();

            if ($students->num_rows > 0) {
                echo '<table>';
                echo '<tr><th>Name</th><th colspan="2">Action</th></tr>';

                while ($row = $students->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['middle_name']) . ' ' . htmlspecialchars($row['last_name']) . '</td>';
                    echo '<td><a href="view_students.php?student_id=' . htmlspecialchars($row['student_id']) . '" class="btn">View Details</a></td>';
                    echo '<td><a href="discipline.php?student_id=' . htmlspecialchars($row['student_id']) . '" class="btn">Progress</a></td>';
                    echo '</tr>';
                }

                echo '</table>';
            } else {
                echo 'No students found in this class.';
            }

            $stmt->close();
        }

        $conn->close();
        ?>
    </div>
    </main>
    <script src="script.js"></script>
</body>
</html>

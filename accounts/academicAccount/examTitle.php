<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS</title>
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
            <li><a href="messages.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>
<main>
    <!-- side div to display menu buttons content -->
    <div class="side2" id="part">
        <h3 style="font-style: italic; text-align: center;">Examination Title</h3>

        <?php
        session_start(); // Start the session

        // Ensure the admin is logged in
        if (!isset($_SESSION['admin_id'])) {
            header("Location: login.php"); // Redirect to the login page if not logged in
            exit();
        }

        include 'classes/connect.php'; // Include your database connection script

        // Function to safely handle input data
        function sanitizeInput($conn, $data) {
            return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
        }

        // Check if form is submitted for adding an examination
        if (isset($_POST['exam_name'])) {
            $exam_name = sanitizeInput($conn, $_POST['exam_name']);
            $admin_id = $_SESSION['admin_id'];

            $stmt = $conn->prepare("INSERT INTO examinations (exam_name, admin_id) VALUES (?, ?)");
            $stmt->bind_param("si", $exam_name, $admin_id);

            if ($stmt->execute()) {
                echo "Examination added!";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $conn->close();
        ?>

        <div id="extr">
            <h3 style="font-style: italic; text-align: center;">Add Examination</h3>
            <form action="" method="post">
                <label for="exam_name">Exam Name:</label>
                <input type="text" name="exam_name" id="exam_name" required><br>
                <button type="submit" id="btn2">Add Exam</button>
            </form>
        </div>
    </div>
    </main>
    <script src="script.js"></script>
</body>
</html>

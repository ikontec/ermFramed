<?php
session_start();
include 'classes/connect.php';

// Ensure the author is logged in
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
    <main class="upt">
        <form class="form" action="xenderSMS.php" method="post">
            <select id="class" name="class" required>
                <option value="">Select Class</option>
                <option value="form one">Form One</option>
                <option value="form two">Form Two</option>
                <option value="form three">Form Three</option>
                <option value="form four">Form Four</option>
            </select><br><br>

            <select id="exam_id" name="exam_id" required>
                <option value="">Select Examination Season</option>
                <?php
                $exam_result = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC");
                while($exam = $exam_result->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($exam['exam_id']) . '">' . htmlspecialchars($exam['exam_name']) . '</option>';  
                }
                ?>
            </select><br><br>
            <button class="nav-button" type="submit" name="fetch_results">Retrieve Results</button>
        </form>
    </main>
    <script src="script.js"></script>
</body>
</html>

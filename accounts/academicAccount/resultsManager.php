<?php
session_start(); // Start the session

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Manager</title>
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
            <li><a href="academicAccount.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>
    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Manage Students' Results</h2>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="location.href='subject_performance.php'">
                    <i class="fas fa-chart-pie"></i> <!-- changed from calendar-check to chart-pie -->
                    <h3>Subject Performance Analysis</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='oneStudentProgress.php'">
                    <i class="fas fa-user-graduate"></i> <!-- changed from envelope to user-graduate -->
                    <h3>Student Progress</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='generalResults.php'">
                    <i class="fas fa-file-alt"></i> <!-- changed from user-plus to file-alt -->
                    <h3>General Results</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='generalPerformanceAnalysis.php'">
                    <i class="fas fa-chart-line"></i> <!-- changed from calendar-alt to chart-line -->
                    <h3>General Performance Analysis</h3>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
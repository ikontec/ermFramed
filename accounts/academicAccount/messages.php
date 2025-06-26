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
    <title>SMS</title>
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
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-envelope"></i> Message Center</h2>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="location.href='examTitle.php'">
                    <i class="fas fa-clipboard-list"></i> <!-- Set Examination Title -->
                    <h3>Set Examination Title</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='staffNote.php'">
                    <i class="fas fa-paper-plane"></i> <!-- Send Staff Message -->
                    <h3>Send Staff Message</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='selectResults.php'">
                    <i class="fas fa-envelope"></i> <!-- Share Academic Results -->
                    <h3>Share Academic Results Via Email</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='selectResultsSMS.php'">
                    <i class="fas fa-paper-plane"></i> <!-- Share Academic Results -->
                    <h3>Share Academic Results Via SMS</h3>
                </div>                

                <div class="dashboard-card" onclick="location.href=''">
                    <i class="fas fa-users"></i> <!-- School Community -->
                    <h3>School Community</h3>
                </div>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
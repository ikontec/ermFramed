<?php
session_start(); // Start the session

// Function to log out
function logout() {
    // Unset all of the session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to the login page
    header("Location: login.php");
    exit();
}

// Check if the logout button has been clicked
if (isset($_POST['logout'])) {
    logout();
}

// Ensure the author is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

// Fetch data from the admin table
$admin = null;
if ($stmt = $conn->prepare("SELECT name, photo, email FROM admin WHERE admin_id = ?")) {
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close(); // Close the prepared statement
}

    $Name = $admin['name'];
    $email = $admin['email'];
    $image = $admin['photo'];
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
            <li><a href="../../index.php"><i class="fas fa-home"></i>Home</a></li>
            <li><a href="../headOffice_Account/head.php"><i class="fas fa-chalkboard-teacher"></i> Administration Account</a></li>
            <li><a href="helpCenter.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <form method="post" action="">
                <button class="logout-btn" name="logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </ul>
    </nav>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i>Academic Dashboard</h2>
                <div class="user-info">
                    <span id="staffName"><img src="../../<?php echo htmlspecialchars($image); ?>"> <?php echo htmlspecialchars($Name); ?></span>
                </div>

                <div class="user-info">
                    <i class="fas fa-envelope"></i><span class="staffName"><?php echo htmlspecialchars($email); ?></span>
                </div>
            </div>

            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="location.href='student_list.php'">
                    <i class="fas fa-users"></i>
                    <h3>View Students</h3>
                    <p>Manage student records and performance.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='staff.php'">
                    <i class="fas fa-users"></i>
                    <h3>View Staff Members</h3>
                    <p>Manage staff records</p>
                </div>  
                
                <div class="dashboard-card" onclick="location.href='resultsManager.php'">
                    <i class="fas fa-chart-line"></i>
                    <h3>Results Manager</h3>

                </div> 
                
                <div class="dashboard-card" onclick="location.href='addStaff.php'">
                    <i class="fas fa-user-plus"></i>
                    <h3>Register New Staff</h3>
                </div>  
                
                <div class="dashboard-card" onclick="location.href='addStudent.php'">
                    <i class="fas fa-user-plus"></i>
                    <h3>Register New Student</h3>
                </div>                 

                <div class="dashboard-card" onclick="location.href='messages.php'">
                    <i class="fas fa-envelope"></i>
                    <h3>Messages</h3>
                    <p>Check and send messages.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='attendance.html'">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Attendance</h3>
                    <p>Mark and review student attendance.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>School Calender</h3>
                    <p>Account and dashboard settings.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-table"></i>
                    <h3>Timetable</h3>
                    <p>Account and dashboard settings.</p>
                </div>               
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
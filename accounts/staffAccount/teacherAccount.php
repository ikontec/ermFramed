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

// Ensure the staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

$successMessage = "";
$errorMessage = "";

// Fetch data from the staff table (initial fetch or after update)
$staff = null;
if ($stmt = $conn->prepare("SELECT name, photo, gender, title, email, password FROM staff WHERE staff_id = ?")) {
    $stmt->bind_param("i", $_SESSION['staff_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();
}

// If staff data couldn't be fetched (e.g., staff_id in session is invalid), destroy session and redirect
if (!$staff) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Assign fetched data to variables
$Name = $staff['name'];
$title = $staff['title'];
$image = $staff['photo']; // Current photo path from DB
$email = $staff['email'];
$password = $staff['password']; // This should be the hashed password from the DB

// Handle profile update
if (isset($_POST['update_profile'])) {
    $newEmail = trim($_POST['username']);
    $newPassword = $_POST['password']; // This will be the *plain text* new password if provided
    $staffId = $_SESSION['staff_id'];

    $currentPhotoPath = $staff['photo']; // Get current photo path from the re-fetched staff data
    $newPhotoPath = $currentPhotoPath; // Default to existing photo path

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../../uploads/";
        // Ensure the uploads directory exists and is writable
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES["photo"]["name"]); // Generate a unique file name
        $targetFile = $targetDir . $fileName;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if image file is an actual image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if ($check === false) {
            $errorMessage .= "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (500KB)
        if ($_FILES["photo"]["size"] > 500000) {
            $errorMessage .= " Sorry, your file is too large (max 500KB).";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $errorMessage .= " Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            $errorMessage .= " Your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
                $newPhotoPath = "uploads/" . $fileName; // Store relative path from web root (adjust if 'images' is your alias for 'uploads')
                $successMessage = "The file ". htmlspecialchars( basename( $_FILES["photo"]["name"])). " has been uploaded.";
            } else {
                $errorMessage = "Sorry, there was an error uploading your file.";
            }
        }
    }

    if (empty($errorMessage)) {
        // Only update password if a new one is entered
        $updatePassword = false;
        $ourPassword = $password;

        if (!empty($newPassword)) {
            $ourPassword = $newPassword;
            $updatePassword = true;
        }

        // Prepare the SQL statement based on whether the password is being updated
        if ($updatePassword) {
            $stmt = $conn->prepare("UPDATE staff SET email = ?, password = ?, photo = ? WHERE staff_id = ?");
            $stmt->bind_param("sssi", $newEmail, $ourPassword, $newPhotoPath, $staffId);
        } else {
            $stmt = $conn->prepare("UPDATE staff SET email = ?, photo = ? WHERE staff_id = ?");
            $stmt->bind_param("ssi", $newEmail, $newPhotoPath, $staffId);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $_SESSION['update_success'] = "Profile updated successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $errorMessage = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMessage = "Error preparing statement: " . $conn->error;
        }
    }
}

// Check for success message from a previous redirect
if (isset($_SESSION['update_success'])) {
    $successMessage = $_SESSION['update_success'];
    unset($_SESSION['update_success']); // Clear the session message
}

// Determine if the profile section should be shown initially
$showProfileSection = !empty($successMessage) || !empty($errorMessage) || (isset($_GET['profile_updated']) && $_GET['profile_updated'] === 'true');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="Staff_Styles/pc.css">
    <link rel="stylesheet" href="Staff_Styles/tablet.css">
    <link rel="stylesheet" href="Staff_Styles/phone.css">
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
            <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="../academicAccount/academicAccount.php"><i class="fas fa-chalkboard-teacher"></i> Academic Account</a></li>
            <li><a href="helpCenter.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <form method="post" action="" style="background: none; padding: 0; margin: 0;">
                <button type="submit" class="logout-btn" name="logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </ul>
    </nav>

    <div id="staffProfile" class="staffProfile" style="display: <?php echo $showProfileSection ? 'block' : 'none'; ?>;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="document.getElementById('staffProfile').style.display='none'">X</span>

        <?php if (!empty($successMessage)): ?>
            <div class="message success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="message error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <section class="set">
            <h4>Staff Details</h4>
            <img src="../../<?php echo htmlspecialchars($image); ?>" alt="Staff Photo">
            <span><b>Full Name:</b> <?php echo htmlspecialchars($Name); ?></span>
            <span><b>Email:</b> <?php echo htmlspecialchars($email); ?></span>
            <span><b>Title:</b> <?php echo htmlspecialchars($title); ?></span>
        </section>
        <button id="profileUpdateBtn" type="button" style="display: block;" onclick="showEditor()">Update Profile</button>

        <section class="set" id="editor" style="display: none;">
            <h4>Update Profile</h4>
            <form method="post" action="" enctype="multipart/form-data">
                <label for="photo">Profile Photo:</label>
                <input type="file" name="photo" id="photo" accept="image/*"><br><br>

                <label for="username">User Email:</label>
                <input type="email" name="username" id="username_profile" value="<?php echo htmlspecialchars($email); ?>" required><br><br>

                <label for="password">New Password (leave blank to keep current):</label>
                <input type="text" name="password" id="password_profile" value="<?php echo htmlspecialchars($password); ?>"><br><br>

                <button type="submit" name="update_profile">Save Changes</button>
                <button type="button" onclick="hideEditor()">Cancel</button>
            </form>
        </section>
    </div>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Staff Dashboard</h2>
                <div class="user-info">
                    <span id="staffName"><img src="../../<?php echo htmlspecialchars($image); ?>"> <?php echo htmlspecialchars($Name); ?></span>
                </div>
                <div class="user-info">
                    <i class="fas fa-envelope"></i><span class="staffName"><?php echo htmlspecialchars($email); ?></span>
                </div>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="document.getElementById('staffProfile').style.display='block'">
                    <i class="fas fa-id-badge"></i>
                    <h3>My Profile</h3>
                    <p>View and update your personal information.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='students.php'">
                    <i class="fas fa-users"></i>
                    <h3>My Students</h3>
                    <p>Manage student records and performance.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='resultsManager.php'">
                    <i class="fas fa-chart-line"></i>
                    <h3>Results Manager</h3>
                    <p>Account and dashboard settings.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='attendance.html'">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Attendance</h3>
                    <p>Mark and review student attendance.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='messages.html'">
                    <i class="fas fa-envelope"></i>
                    <h3>Messages</h3>
                    <p>Check and send messages.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-user-plus"></i>
                    <h3>Make Registrations</h3>
                    <p>Account and dashboard settings.</p>
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
    <script>
        // Show profile editor
        function showEditor() {
            document.getElementById('editor').style.display = 'block';
            document.getElementById('profileUpdateBtn').style.display = 'none'; // Hide the update button when editor is shown
        }

        // Hide profile editor
        function hideEditor() {
            document.getElementById('editor').style.display = 'none';
            document.getElementById('profileUpdateBtn').style.display = 'block'; // Show the update button when editor is hidden
        }

        // Automatically show profile section if update was attempted or if messages exist
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            // Check for profile_updated flag in URL (from old redirect logic, if any)
            // or if there are any messages to display
            if (<?php echo json_encode($showProfileSection); ?>) {
                document.getElementById('staffProfile').style.display = 'block';
                // Optional: Scroll to the profile section for better UX
                document.getElementById('staffProfile').scrollIntoView({ behavior: 'smooth' });
            }
            // Clear the profile_updated flag from the URL if it exists
            if (urlParams.has('profile_updated')) {
                urlParams.delete('profile_updated');
                window.history.replaceState({}, document.title, "?" + urlParams.toString());
            }
        };
    </script>
    <script src="script.js"></script>
</body>
</html>
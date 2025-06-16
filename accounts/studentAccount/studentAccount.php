<?php
    session_start(); // Start the session
    //database connection
    include 'classes/connect.php';

    // Handle profile update (move this to the top)
    if (isset($_POST['update_profile'])) {
        $updateFields = [];
        $params = [];
        $types = '';

        // Handle username
        if (!empty($_POST['username'])) {
            $updateFields[] = "username = ?";
            $params[] = $_POST['username'];
            $types .= 's';
        }

        // Handle password (store as plain text)
        if (!empty($_POST['password'])) {
            $updateFields[] = "url = ?";
            $params[] = $_POST['password'];
            $types .= 's';
        }

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $targetDir = "student_images/";
            $fileName = basename($_FILES["photo"]["name"]);
            $targetFile = $targetDir . uniqid() . "_" . $fileName;
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], "../../" . $targetFile)) {
                $updateFields[] = "photo = ?";
                $params[] = $targetFile;
                $types .= 's';
            }
        }

        if (!empty($updateFields)) {
            $params[] = $_SESSION['student_id'];
            $types .= 'i';
            $sql = "UPDATE students SET " . implode(", ", $updateFields) . " WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            // Refresh to show updated info
            header("Location: studentAccount.php");
            exit();
        }
    }

    // Check if the logout button has been clicked
    if (isset($_POST['logout'])) {
        $_SESSION = array(); // Unset all of the session variables
        session_destroy(); // Destroy the session
        header("Location: login.php"); // Redirect to the login page
        exit();
    }

    // Ensure the student is logged in
    if (!isset($_SESSION['student_id'])) {
        header("Location: login.php"); // Redirect to the login page if not logged in
        exit();
    }

    // Fetch data from the student table
    $student = null;
    if ($stmt = $conn->prepare("SELECT first_name, middle_name, last_name, photo, date_of_birth, gender, class, username, url FROM students WHERE student_id = ?")) {
        $stmt->bind_param("i", $_SESSION['student_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close(); // Close the prepared statement
    }

    $studentName =  htmlspecialchars($student['first_name']) . " " . htmlspecialchars($student['middle_name']) . " " . htmlspecialchars($student['last_name']);
    $studentPhoto = htmlspecialchars($student['photo']);
    $studentGender = htmlspecialchars($student['gender']);
    $studentClass = htmlspecialchars($student['class']);
    $studentUsername = htmlspecialchars($student['username']);
    $studentPassword = htmlspecialchars($student['url']);

    // Fetch data from the parent table
    $stmt = $conn->prepare("SELECT first_name, last_name, gender, relationship, phone, email FROM parent WHERE student_id = ?");
    $stmt->bind_param("i", $_SESSION['student_id']);
    $stmt->execute();
    $parent_result = $stmt->get_result();
    $student_parent = $parent_result->fetch_assoc();
    $stmt->close(); // Close the prepared statement

    $ParentName =  htmlspecialchars($student_parent['first_name']) . " " . htmlspecialchars($student['last_name']);
    $ParentGender = htmlspecialchars($student_parent['gender']);
    $ParentEmail = htmlspecialchars($student_parent['email']);
    $ParentContact = htmlspecialchars($student_parent['phone']);
    $ParentRelationship = htmlspecialchars($student_parent['relationship']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="Student_Styles/pc.css">
    <link rel="stylesheet" href="Student_Styles/tablet.css">
    <link rel="stylesheet" href="Student_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* Simple styling for the profile editor */
    #editor {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 20px;
        margin-top: 10px;
        border-radius: 8px;
        max-width: 350px;
    }
    #editor label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    #editor input[type="text"],
    #editor input[type="password"],
    #editor input[type="file"] {
        width: 100%;
        padding: 6px;
        margin-bottom: 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    #editor button {
        margin-right: 10px;
        padding: 7px 18px;
        border: none;
        border-radius: 4px;
        background: #007bff;
        color: #fff;
        cursor: pointer;
    }
    #editor button[type="button"] {
        background: #aaa;
    }
    </style>
</head>
<body>
     <!--headding or header div-->
    <?php include 'classes/header.php'; ?> 

    <!--absolute positioned attributes-->
    <!--navigation-->
    <nav class="nav">
        <button class="menuButton" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-chevron-down" id="menuIcon"></i>
        </button>
        <ul>
            <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="helpCenter.html"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <form method="post" action="">
                <button type="submit" class="logout-btn" name="logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </ul>
    </nav>

    <!--profile-->
    <div id="studentProfile" class="studentProfile" style="display: none;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="document.getElementById('studentProfile').style.display='none'">X</span>
        <section class="set">
            <h4>Student Details</h4>
            <img src="../../<?php echo $studentPhoto; ?>">
            <span><b>Full Name:</b> <?php echo $studentName; ?></span>
            <span><b>Username:</b> <?php echo $studentUsername; ?></span>
            <span><b>Gender:</b> <?php echo $studentGender; ?></span>
            <span><b>Password:</b> <?php echo $studentPassword; ?></span>
        </section>
        <section class="set">
            <h4>Parent Details</h4>
            <span><b>Full Name:</b> <?php echo $ParentName; ?></span>
            <span><b>Gender</b>: <?php echo $ParentGender; ?></span>
            <span><b>Relationship</b>: <?php echo $ParentRelationship; ?></span>
            <span><b>Email:</b> <?php echo $ParentEmail; ?></span>
            <span><b>Contact:</b> +255<?php echo $ParentContact; ?></span>
        </section>
        <button id="btn" type="button" style="display: block;" onclick="showEditor()">Update Profile</button>
        <section class="set" id="editor" style="display: none;">
            <form method="post" action="" enctype="multipart/form-data">
                <label for="photo">Profile Photo:</label>
                <input type="file" name="photo" id="photo" accept="image/*"><br><br>

                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo $studentUsername; ?>"><br><br>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password"><br><br>

                <button type="submit" name="update_profile">Save Changes</button>
                <button type="button" onclick="hideEditor()">Cancel</button>
            </form>
        </section>

        <script>
        function showEditor() {
            document.getElementById('btn').style.display = 'none';
            document.getElementById('editor').style.display = 'block';
        }
        function hideEditor() {
            document.getElementById('editor').style.display = 'none';
            document.getElementById('btn').style.display = 'block';
        }
        </script>
    </div>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Student Dashboard</h2>
                <div class="user-info">
                    <span id="staffName"><img src="student_images/default.png"> <?php echo $studentName; ?></span>
                </div>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="document.getElementById('studentProfile').style.display='block'">
                    <i class="fas fa-id-badge"></i>
                    <h3>My Profile</h3>
                    <p>View and update your personal information.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='students.html'">
                    <i class="fas fa-users"></i>
                    <h3>Student's Subjects</h3>
                    <p>Subjets enrolled in this year.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='attendance.html'">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Student Attendance</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='messages.html'">
                    <i class="fas fa-envelope"></i>
                    <h3>Parental Messages & Feedbacks</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Academic Results</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-file-alt"></i>
                    <h3>Academic & Discipline Report</h3>
                </div>

                <div class="dashboard-card" onclick="location.href='settings.html'">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>School Calender</h3>
                    <p>Account and dashboard settings.</p>
                </div>             
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
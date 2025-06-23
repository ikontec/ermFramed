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
    <title>Registation</title>
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
    <main class="boy">
        
        <form class="form" method="post" enctype="multipart/form-data">
            <h3>Staff Registration</h3>
            <?php
            include "classes/connect.php";

            // Check if form is submitted
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Retrieve form data
                $name = htmlspecialchars(strtoupper($_POST['name']));
                $gender = htmlspecialchars($_POST['gender']);
                $title = htmlspecialchars(strtoupper($_POST['title']));
                $phone = htmlspecialchars($_POST['phone']);
                $email = htmlspecialchars($_POST['email']);

                // Check if the staff member already exists based on email to avoid duplicates
                $checkStmt = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    echo "A staff member with this email already exists.";
                } else {
                    // Close the check statement before proceeding to insert
                    $checkStmt->close();

                    // Prepare and bind
                    $stmt = $conn->prepare("INSERT INTO staff (name, gender, title, contact, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $gender, $title, $phone, $email);

                    // Execute statement
                    if ($stmt->execute()) {
                        // Redirect to success page
                        header("Location: success.php");
                        exit();
                    } else {
                        // Error during insertion
                        echo "Error: " . $stmt->error;
                    }

                    // Close statement
                    $stmt->close();
                }

                // Close the connection
                $conn->close();
            }
            ?>

            <input type="text" id="name" name="name" placeholder="Full Name" required><br>

            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>

            <br><br><input type="text" id="title" name="title" placeholder="Title" required><br><br>

            <input type="tel" id="phone" name="phone" placeholder="Phone" required><br><br>

            <input type="email" id="email" name="email" placeholder="Email" required>

            <br><button id="btn2" type="submit">DONE</button>
        </form>
    </main>
    <script src="script.js"></script>
</body>
</html>


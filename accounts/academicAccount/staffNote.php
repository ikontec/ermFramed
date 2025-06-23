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
        <h3 style="font-style: italic; text-align: center;">Staff Message</h3>

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

        // Check if form is submitted for sending a notification
        if (isset($_POST['msg'])) {
            $msg = sanitizeInput($conn, $_POST['msg']);

            $stmt = $conn->prepare("INSERT INTO notification (admin_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $_SESSION['admin_id'], $msg);

            if ($stmt->execute()) {
                echo "Notification sent!";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }

        ?>

        <div id="extr">
            <form action="" method="post">
                <textarea name="msg" placeholder="Enter your message"></textarea><br>
                <button type="submit" id="btn2">Send</button>
            </form>
        </div>
    </div>
    </main>
    <script src="script.js"></script>
</body>
</html>

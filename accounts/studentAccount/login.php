<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="student_Styles/pc.css">
    <link rel="stylesheet" href="student_Styles/tablet.css">
    <link rel="stylesheet" href="student_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
     <!--headding or header div-->
    <?php include 'classes/header.php'; ?> 

    <nav class="nav">
        <button class="menuButton" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-chevron-down" id="menuIcon"></i>
        </button>
        <ul>
            <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </nav>    

    <main>
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            <form action="" method="post" autocomplete="off">

                <?php
                    session_start();

                    // Include the database connection file
                    include 'classes/connect.php';

                    // Check if POST data is received
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        // Sanitize and validate username input
                        $username = $_POST['username'];

                        $url = $_POST['password'];

                        // Prepare SQL statement to fetch url and student_id for the given username
                        $stmt = $conn->prepare("SELECT student_id, url FROM students WHERE username = ?");
                        if (!$stmt) {
                            die("Prepare failed: " . $conn->error);
                        }

                        // Bind parameters and execute query
                        $stmt->bind_param("s", $username);
                        $stmt->execute();

                        // Store result
                        $stmt->store_result();

                        // Check if the username exists in the database
                        if ($stmt->num_rows > 0) {
                            // Bind the result to variables
                            $stmt->bind_result($student_id, $stored_url);
                            $stmt->fetch();

                            // Verify the url (you should use url_verify() for secure url comparison)
                            if ($url === $stored_url) {
                                // url is correct, set session variables
                                $_SESSION['student_id'] = $student_id;
                                $_SESSION['username'] = $username;
                                // Redirect to the dashboard page
                                header("Location: studentAccount.php");
                                exit;
                            } else {
                                echo "<pre style='color: red;'>"."Incorrect url or username!"."</pre>";
                            }
                        } else {
                            echo "<pre style='color: red;'>"."Incorrect url or username!"."</pre>";
                        }

                        // Close statement
                        $stmt->close();
                    }
                    // Close connection
                    $conn->close();
                ?>

                <div>
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit"><i class="fas fa-arrow-right"></i> Login</button>
            </form>
            <div class="links">
                <a href="forgotPassword.html">Forgot Password?</a>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="Staff_Styles/pc.css">
    <link rel="stylesheet" href="Staff_Styles/tablet.css">
    <link rel="stylesheet" href="Staff_Styles/phone.css">
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
            <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </nav>    

    <main>

            <?php
                session_start();

                // Include the database connection file
                include 'classes/connect.php';

                // Check if POST data is received
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    // Sanitize and validate email input
                    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                    if (!$email) {
                        die("Invalid email format");
                    }

                    // Sanitize password input (for illustration, you may need stronger password handling)
                    $password = $_POST['password'];

                    // Prepare SQL statement to fetch password and staff_id for the given email
                    $stmt = $conn->prepare("SELECT staff_id, password FROM staff WHERE email = ?");
                    if (!$stmt) {
                        die("Prepare failed: " . $conn->error);
                    }

                    // Bind parameters and execute query
                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    // Store result
                    $stmt->store_result();

                    // Check if the email exists in the database
                    if ($stmt->num_rows > 0) {
                        // Bind the result to variables
                        $stmt->bind_result($staff_id, $stored_password);
                        $stmt->fetch();

                        // Verify the password (you should use password_verify() for secure password comparison)
                        if ($password === $stored_password) {
                            // Password is correct, set session variables
                            $_SESSION['staff_id'] = $staff_id;
                            $_SESSION['email'] = $email;
                            // Redirect to the dashboard page
                            header("Location: teacherAccount.php");
                            exit;
                        } else {
                            echo "<pre style='color: red;'>"."Incorrect password or email!"."</pre>";
                        }
                    } else {
                        echo "<pre style='color: red;'>"."Incorrect password or email!"."</pre>";
                    }

                    // Close statement
                    $stmt->close();
                }
                // Close connection
                $conn->close();
            ?>
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            <form action="" method="post" autocomplete="off">
                <div>
                    <label for="username"><i class="fas fa-envelope"></i> Email</label>
                    <input type="text" id="username" name="email" required autofocus>
                </div>
                <div>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit"><i class="fas fa-arrow-right"></i> Login</button>
            </form>
            <div class="links">
                <a href="../../forgotPassword.php">Forgot Password?</a>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
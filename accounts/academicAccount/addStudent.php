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

    <main class="boy">
        <h3>Student Registration</h3>
        <form class="form" method="post" enctype="multipart/form-data">

            <?php
            include 'classes/connect.php'; // Include your database connection file

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Collect form data
                $firstName = htmlspecialchars(strtoupper($_POST['first_name']));
                $middleName = htmlspecialchars(strtoupper($_POST['middle_name']));
                $lastName = htmlspecialchars(strtoupper($_POST['last_name']));
                $dob = $_POST['dob'];
                $gender = $_POST['gender'];
                $class = $_POST['class'];

                // Generate URL and username
                $randomDigits = rand(1000, 9999);
                $url = strtolower($middleName) . '@' . $randomDigits;
                $username = strtoupper($firstName[0] . $middleName[0] . $lastName[0].'/'.$randomDigits.'/'.date('Y'));

                // Check if the student already exists in the database
                $checkQuery = "SELECT student_id FROM students WHERE first_name = ? AND middle_name = ? AND last_name = ? AND date_of_birth = ?";
                $stmt = $conn->prepare($checkQuery);
                if (!$stmt) {
                    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                    exit();
                }
                $stmt->bind_param("ssss", $firstName, $middleName, $lastName, $dob);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Student already exists, skip insertion
                    echo "Student already exists.";
                } else {
                    // Close the statement before preparing a new one
                    $stmt->close();

                    // Insert data into the database
                    $insertQuery = "INSERT INTO students (first_name, middle_name, last_name, date_of_birth, gender, class, url, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($insertQuery);
                    if (!$stmt) {
                        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                        exit();
                    }
                    $stmt->bind_param("ssssssss", $firstName, $middleName, $lastName, $dob, $gender, $class, $url, $username);

                    if ($stmt->execute()) {
                        // Fetch the newly inserted student ID
                        $student_id = $stmt->insert_id;

                        // Close the statement
                        $stmt->close();

                        // INSERTING SUBJECT DETAILS
                        $year = date("Y");
                        $subjects = ['maths', 'english', 'kiswahili', 'geography', 'biology', 'chemistry', 'physics', 'computer', 'civics', 'history'];

                        $sql = "";
                        foreach ($subjects as $subject) {
                            $sql .= "INSERT INTO $subject (student_id, class, year) VALUES ($student_id, '$class', $year);";
                        }

                        if ($conn->multi_query($sql)) {
                            // Redirect to success page
                            echo "
                            <div id='copy-section' style='background: #f0f0f0; padding: 15px; border-radius: 8px; text-align: center;'>
                                <p><strong>Student Password and URL:</strong></p>
                                <input type='text' id='studentUrl' value='$url' readonly style='padding: 10px; width: 80%; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 10px;                                 text-align:center; font-weight:bold;'>
                                <br>
                                <button type='button' onclick='copyAndRedirect()' style='margin-bottom: 10px;'>Copy & Continue</button>
                            </div>

                            <script>
                            function copyAndRedirect() {
                                var copyText = document.getElementById('studentUrl');
                                copyText.select();
                                copyText.setSelectionRange(0, 99999);
                                document.execCommand('copy');
                                window.location.href = 'parentdetails.php';
                            }
                            </script>
                            ";
                            exit();
                        } else {
                            echo "Error: " . $conn->error;
                        }

                    } else {
                        echo "Error: " . $stmt->error;
                    }
                }

                $stmt->close();
                $conn->close();
            }
            ?>

            <input type="text" id="first_name" name="first_name" placeholder="First Name" required><br><br>

            <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name" required><br><br>

            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required><br><br>

            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required><br><br>

            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select><br><br>

            <select id="gender" name="class" required>
                <option value="">Select Class</option>
                <option value="Form One">Form One</option>
                <option value="Form Two">Form Two</option>
                <option value="Form Three">Form Three</option>
                <option value="Form Four">Form Four</option>
            </select><br><br>

            <br><button id="btn2" type="submit">NEXT</button>
        </form>
    </main>
    <script src="script.js"></script>
</body>
</html>
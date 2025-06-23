<?php
session_start();
// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php"); // Redirect to the login page if not logged in
    exit();
}
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

    <style>
        .updt {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }

        #tch {
            width: 260px;
            padding: 20px;
            background-color: #f9f9f9;
            border: 2px solid #007bff;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: transform 0.3s ease;
        }

        #tch:hover {
            transform: scale(1.03);
        }

        #profile {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
        }

        #tch p {
            font-size: 13px;
            margin: 6px 0;
            color: #333;
            font-weight: 500;
        }

        #edit, #delete {
            display: inline-block;
            margin-top: 10px;
        }
    </style>    

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
        <li><a href="staff.php"><i class="fas fa-arrow-left"></i> Back</a></li>
    </ul>
</nav>
    <div class="updt">
        <div>
            <?php
            include 'classes/connect.php'; // Include your database connection script

            // Check if staff_id is provided
            if (!isset($_GET['staff_id']) || empty($_GET['staff_id'])) {
                echo "No staff ID provided.";
                exit();
            }

            $staff_id = intval($_GET['staff_id']);

            // Fetch staff data for editing
            $sql = "SELECT staff_id, name, gender, title, contact, email, photo, password FROM staff WHERE staff_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $staff_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();
            $stmt->close();

            if (!$staff) {
                echo "Staff member not found.";
                exit();
            }

            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $name = $_POST['name'];
                $gender = $_POST['gender'];
                $contact = $_POST['contact'];
                $title = $_POST['title'];
                $email = $_POST['email'];
                $password = $_POST['password'];

                // Handle photo upload
                $photo = $staff['photo']; // Default to existing photo
                if (!empty($_FILES['photo']['name'])) {
                    $target_dir = "uploads/";
                    $target_file = $target_dir . basename($_FILES["photo"]["name"]);
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                        $photo = $target_file;
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }

                // Update staff details in the database
                $updateSql = "UPDATE staff SET name = ?, title = ?, gender = ?, contact = ?, email = ?, password = ? WHERE staff_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('ssssssi', $name, $title, $gender, $contact, $email, $password, $staff_id);

                if ($updateStmt->execute()) {
                    header("Location: staff.php");
                } else {
                    echo "Error updating staff details: " . $conn->error;
                }
                $updateStmt->close();
            }

            $conn->close();
            ?>

            <form action="" method="post" enctype="multipart/form-data">

        <?php
        echo '<div id="tch">';

        echo "<form action='' method='post'>";
        
        // Display profile photo
        echo "<img id='profile' src='";
        if (!empty($staff['photo'])) {
            echo htmlspecialchars($staff['photo']);
        } else {
                echo "images/male.png"; // Replace with your default male image path
        }
        echo "' alt='Profile Photo'><br><br>";
        ?>

                <input type="text" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>" placeholder="Name" required>
                <input type="text" name="title" value="<?php echo htmlspecialchars($staff['title']); ?>" placeholder="Tiltle" required>
                <input type="text" name="gender" value="<?php echo htmlspecialchars($staff['gender']); ?>" placeholder="Gender" required>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($staff['contact']); ?>" placeholder="Contact" required>
                <input type="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" placeholder="Email" required>
                <input type="text" name="password" value="<?php echo htmlspecialchars($staff['password']); ?>" placeholder="Password" required>
                <button id="btn2" type="submit">Update</button>
            </form>
        </div>
    </div>
<main>

</main>
<script src="script.js"></script> 
</body>
</html>
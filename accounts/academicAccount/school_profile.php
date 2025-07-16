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
        main {
            padding: 20px;
        }
        .updates1 {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: auto;
        }
        .updates1 h3 {
            margin-bottom: 20px;
        }
        .updates1 label {
            display: block;
            margin-bottom: 5px;
        }
        .updates1 input[type="text"],
        .updates1 textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
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
            <li><a href="academicAccount.php"><i class="fas fa-arrow"></i>Back</a></li>
            <li><a href="helpCenter.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <form method="post" action="">
                <button class="logout-btn" name="logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </ul>
    </nav>

    <main class="boy" style="display: flex; flex-direction: column;">
        <form class="updates1" method="post" enctype="multipart/form-data">
            <h3>Edit School Profile</h3>
            <?php
            // Fetch current values for display
            include '../../classes/connect.php';
            $result = $conn->query("SELECT title, image FROM back_site WHERE image_id = 1");
            $row = $result ? $result->fetch_assoc() : ["title"=>"", "image"=>""];

            // Handle form submission
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
                $title = trim($_POST['title']);

                // Handle logo upload
                $logo = $row['image']; // Default to current
                if (isset($_FILES["budge"]) && !empty($_FILES["budge"]["name"])) {
                    $targetDir = "../../uploads/";
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES["budge"]["name"]);
                    $targetFile = $targetDir . $fileName;
                    $uploadOk = 1;
                    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                    $allowedTypes = ["jpg", "jpeg", "png"];
                    $check = getimagesize($_FILES["budge"]["tmp_name"]);
                    if ($check === false) {
                        echo "File is not an image.";
                        $uploadOk = 0;
                    }
                    if ($_FILES["budge"]["size"] > 5000000) {
                        echo " Sorry, your file is too large (max 5MB).";
                        $uploadOk = 0;
                    }
                    if (!in_array($imageFileType, $allowedTypes)) {
                        echo " Sorry, only JPG, JPEG & PNG files are allowed.";
                        $uploadOk = 0;
                    }
                    if ($uploadOk == 0) {
                        echo " Your file was not uploaded.";
                    } else {
                        if (move_uploaded_file($_FILES["budge"]["tmp_name"], $targetFile)) {
                            $logo = "uploads/" . $fileName;
                        } else {
                            echo "Sorry, there was an error uploading your file.";
                        }
                    }
                }

                // Update all fields in one query
                $stmt = $conn->prepare("UPDATE back_site SET title = ?, image = ? WHERE image_id = 1");
                $stmt->bind_param("ss", $title, $logo);
                if ($stmt->execute()) {
                    echo "<div style='color:green;'>Profile updated successfully!</div>";
                    // Optionally refresh values
                    $row = ["title"=>$title, "image"=>$logo];
                } else {
                    echo "<div style='color:red;'>Error updating profile: " . $stmt->error . "</div>";
                }
                $stmt->close();
            }
            ?>

            <label for="title">School Name:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required><br>

            <label for="budge">School Logo:</label>
            <?php if (!empty($row['image'])): ?>
                <div style="margin-bottom:10px;"><img src="../../<?php echo htmlspecialchars($row['image']); ?>" alt="Current Logo" style="max-width:120px;"></div>
            <?php endif; ?>
            <input type="file" id="budge" name="budge" accept="image/*">

            <button type='submit' name="update_profile" id="btn2">Save All Changes</button>
        </form>
    </main>
</body>
</html>

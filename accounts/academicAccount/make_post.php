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

    <main class="boy" style="display: flex; flex-direction: column;">

        <form class="updates1" action="" method="post" enctype="multipart/form-data">
            <h3>Create Post</h3>

            <?php
            // Check if form is submitted
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post'])) {
                $admin_id = $_SESSION['admin_id'];
                $name = trim($_POST['name']);
                $content = trim($_POST['content']);
                $date = date('Y-m-d');

                $targetFile = null;
                if (isset($_FILES["image"]) && !empty($_FILES["image"]["name"])) {
                    $targetDir = "../../uploads/";
                    // Ensure the uploads directory exists
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
                    $targetFile = $targetDir . $fileName;
                    $uploadOk = 1;
                    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
                    $check = getimagesize($_FILES["image"]["tmp_name"]);
                    if ($check === false) {
                        echo "File is not an image.";
                        $uploadOk = 0;
                    }
                    if ($_FILES["image"]["size"] > 5000000) {
                        echo " Sorry, your file is too large (max 5MB).";
                        $uploadOk = 0;
                    }
                    if (!in_array($imageFileType, $allowedTypes)) {
                        echo " Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                        $uploadOk = 0;
                    }
                    if ($uploadOk == 0) {
                        echo " Your file was not uploaded.";
                        $targetFile = null;
                    } else {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                            // File uploaded successfully
                            $targetFile = "uploads/" . $fileName; // Store relative path for DB
                        } else {
                            $targetFile = null;
                            echo "Sorry, there was an error uploading your file.";
                        }
                    }
                }

                // Prepare and bind for update posting
                $stmt = $conn->prepare("INSERT INTO updates (title, content, image) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $content, $targetFile);

                // Execute statement
                if ($stmt->execute()) {
                    // Upload successful
                    echo "Upload successful!";
                    echo "<script>window.location.href='success3.php';</script>";
                    exit();
                } else {
                    // Error during upload
                    echo "Error: " . $stmt->error;
                }

                // Close statement
                $stmt->close();
            }
            ?>

            <label>Title:</label>
            <input type="text" id="name" name="name" required>

            <label>Content:</label>
            <textarea id="content" name="content" rows="5" required></textarea>

            <label>Image:</label>
            <input type="file" id="image" name="image"><br>

            <button type='submit' name="post" id="btn2">Post</button>
        </form>
    </main>
</body>
</html>

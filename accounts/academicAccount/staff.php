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
    padding: 8px 14px;
    margin: 5px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    font-size: 13px;
}

#edit {
    background-color: #28a745;
    color: white;
}

#edit:hover {
    background-color: #218838;
}

#delete {
    background-color: #dc3545;
    color: white;
}

#delete:hover {
    background-color: #c82333;
}

/* Responsive for small screens */
@media screen and (max-width: 768px) {
    #tch {
        width: 90%;
    }
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
        <li><a href="academicAccount.php"><i class="fas fa-arrow-left"></i> Back</a></li>
    </ul>
</nav>

    <main>
    <div class="updt">
            <div>
                <?php
                include 'classes/connect.php'; // Include your database connection script

                // Handle the delete request
                if (isset($_POST['delete'])) {
                    $staff_id = $_POST['staff_id'];
                    
                    // Prepare and execute the DELETE statement
                    if ($stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?")) {
                        $stmt->bind_param('i', $staff_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Optionally, you might want to refresh the page or provide feedback
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "Error: " . $conn->error;
                    }
                }

                // Handle the edit request
                if (isset($_POST['edit'])) {
                    $staff_id = $_POST['staff_id'];
                    // Redirect to an edit page or handle the edit functionality here
                    header("Location: edit_staff.php?staff_id=" . $staff_id);
                    exit();
                }

                $sql = "SELECT staff_id, name, gender, title, contact, email, photo, password FROM staff ORDER BY name DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<div id="tch">';

                        echo "<form action='' method='post'>";
                        
                        // Display profile photo
                        echo "<img id='profile' src='";
                        if (!empty($row['photo'])) {
                            echo htmlspecialchars($row['photo']);
                        } else {
                                echo "images/male.png"; // Replace with your default male image path
                        }
                        echo "' alt='Profile Photo'><br><br>";
                        
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Name: " . htmlspecialchars($row['name']) . "</p><br>";
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Title: " . htmlspecialchars($row['title']) . "</p><br>";
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Gender: " . htmlspecialchars($row['gender']) . "</p><br>";
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Contact: " . "+255" . htmlspecialchars($row['contact']) . "</p><br>";
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Email: " . htmlspecialchars($row['email']) . "</p><br>";
                        echo "<p style='margin-top: -15px; width; 100%; font-size: 13px; font-weight: bold; text-align: left;'>" . "Password: " . htmlspecialchars($row['password']) . "</p><br>";

                        // Hidden field to hold the staff_id for both actions
                        echo "<input type='hidden' name='staff_id' value='" . htmlspecialchars($row['staff_id']) . "'>";
                        
                        echo "<p style='margin-top: -15px; text-align: center;'>
                                <button type='submit' name='edit' id='edit'>EDIT</button>
                                <button type='submit' name='delete' id='delete'>DELETE</button>  
                              </p>";
                        
                        echo "</form>";
                        echo '</div>';
                    }
                } else {
                    echo "No updates found.";
                }
                ?>


            </div>
    </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
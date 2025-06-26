<?php
session_start(); // Start the session

// Ensure the admin is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Include the database connection file
include 'classes/connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS</title>
    <link rel="stylesheet" href="Staff_Styles/pc.css">
    <link rel="stylesheet" href="Staff_Styles/tablet.css">
    <link rel="stylesheet" href="Staff_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.notify {
    position: fixed;
    top: 200px;
    left: 30%;
    width: 40%;
    height: 60vh;
    background: rgba(246, 246, 246, 0);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.notify #note {
    background: #222;
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
    width: 100%;
    max-height: 70vh;
    overflow-y: auto;
    padding: 20px;
    font-size: 1rem;
    height: 40vh;
}

.notify .notification {
    font-size: 1.1em;
    letter-spacing: 1px;
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
            <li><a href="teacherAccount.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>

        <div class="notify" style="display: none;">
            <div id="note" style="padding: 10px;">
                <?php
                include 'classes/connect.php'; // Include your database connection script

                $sql = "SELECT message, time FROM notification ORDER BY time DESC";
                $result = $conn->query($sql);

                if ($result === false) {
                    echo "Error: " . $conn->error;
                } else {
                    if ($result->num_rows > 0) {
                        $first = true; // Flag to mark the first (latest) message

                        while ($row = $result->fetch_assoc()) {
                            $message = htmlspecialchars($row['message']);
                            $time = htmlspecialchars($row['time']);
                            $sentDate = date('F j, Y - H:i A', strtotime($row['time']));

                            echo '<br><div style="border: solid 1px white; border-radius: 5px; padding: 5px;">';

                            if ($first) {
                                echo '<span class="notification" style="color: red; font-weight: bolder;">LATEST!</span><br>';
                                echo '<p style="margin-top: 1px; text-align: left; color: red;">' . $message;
                                $first = false; // Only mark the first one
                            } else {
                                echo '<p style="margin-top: 1px; text-align: left;">' . $message;
                            }

                            echo '<em style="font-size: 11px;"><br>Sent on: ' . $sentDate . '</em>';
                            echo '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo "No updates found.";
                    }
                }

                $conn->close();
                ?>
            </div>
            <button onclick="document.getElementsByClassName('notify')[0].style.display = 'none';" style="margin-top: 10px; padding: 5px 10px; background-color: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>        
        </div>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-envelope"></i> Message Center</h2>
            </div>
            <div class="dashboard-cards">

                <div class="dashboard-card" onclick="document.getElementsByClassName('notify')[0].style.display = 'block';">
                    <i class="fas fa-paper-plane"></i> <!-- Send Staff Message -->
                    <h3>Staff Messages</h3>
                </div>               

                <div class="dashboard-card" onclick="location.href=''">
                    <i class="fas fa-users"></i> <!-- School Community -->
                    <h3>School Community</h3>
                </div>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
    <script>

    </script>
</body>
</html>
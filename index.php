<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="myStyles/pc.css">
    <link rel="stylesheet" href="myStyles/tablet.css">
    <link rel="stylesheet" href="myStyles/phone.css">
    <!-- Font Awesome CDN for icons -->
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
            <li><a href="accounts/studentAccount/studentAccount.php"><i class="fas fa-user-graduate"></i> Student Account</a></li>
            <li><a href="accounts/staffAccount/teacherAccount.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Account</a></li>
            <li><a href="admission.html"><i class="fas fa-user-plus"></i> Admission</a></li>
            <li><a href="admission.html"><i class="fas fa-users"></i> Our Team</a></li>
            <li><a href="helpCenter.html"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <li><a href="about.html"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li><a href="contact.html"><i class="fas fa-envelope"></i> Contact Us</a></li>
            <li><a href="https://ikonteki.wuaze.com"><i class="fas fa-blog"></i> Developer's Blog</a></li>
        </ul>
    </nav>

    <main>
        <section class="content">
            <h2>News & Updates</h2>
            <div>
                <?php
                    session_start();
                    include_once 'classes/connect.php';

                    // Fetch the latest updates
                    $query = "SELECT id, title, image FROM updates ORDER BY date DESC";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }
                    $updates = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $updates[] = $row;
                    }

                    if (count($updates) > 0) {
                        foreach ($updates as $update) {
                            // Use a placeholder if image is missing
                            $imgSrc = !empty($update['image']) ? htmlspecialchars($update['image']) : 'images/default-news.png';
                            $title = htmlspecialchars($update['title']);
                            $id = (int)$update['id'];
                            echo '<div class="article-preview" style="cursor:pointer;" onclick="location.href=\'article.php?id=' . $id . '\'">';
                            echo '<img src="' . $imgSrc . '" alt="News Image" style="max-width:100px;max-height:100px;">';
                            echo '<h4>' . $title . '</h4>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No news updates available.</p>';
                    }
                ?>
            </div>
        </section>
    </main>    

<script src="script.js"></script>
</body>
</html>
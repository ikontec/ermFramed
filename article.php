<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article</title>
    <link rel="stylesheet" href="myStyles/pc.css">
    <link rel="stylesheet" href="myStyles/tablet.css">
    <link rel="stylesheet" href="myStyles/phone.css">
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
            <li><a href="index.php"><i class="fas fa-arrow-left"></i> Back</a></li>
        </ul>
    </nav>
    <main>

        <section class="articles">
            <article class="article-content">
                <?php
                session_start();
                // Include the database connection file

                    include_once 'classes/connect.php';
                    // Get the article ID from the URL
                    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                    if ($id > 0) {
                        $query = "SELECT title, image, content, date FROM updates WHERE id = $id LIMIT 1";
                        $result = mysqli_query($conn, $query);
                        if ($result && mysqli_num_rows($result) > 0) {
                            $article = mysqli_fetch_assoc($result);
                            $title = htmlspecialchars($article['title']);
                            $imgSrc = !empty($article['image']) ? htmlspecialchars($article['image']) : 'images/default-news.png';
                            $content = nl2br(htmlspecialchars($article['content']));
                            $date = date('F j, Y', strtotime($article['date']));
                            $dateAttr = date('Y-m-d', strtotime($article['date']));
                            echo "<h3>$title</h3>";
                            echo "<img src=\"$imgSrc\" alt=\"Article Image\" class=\"article-image\">";
                            echo "<p>$content</p>";
                            echo "<p>Published on: <time datetime=\"$dateAttr\">$date</time></p>";
                        } else {
                            echo "<h3>Article Not Found</h3><p>The requested article does not exist.</p>";
                        }
                    } else {
                        echo "<h3>No Article Selected</h3><p>Please select an article to view.</p>";
                    }
                ?>
            </article>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
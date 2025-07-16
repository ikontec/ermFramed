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
.success-container {
    text-align: center;
}

.success-box {
    background-color: #c8e6c9;
    padding: 3rem;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 128, 0, 0.2);
    max-width: 500px;
    margin: auto;
}

.success-box h1 {
    color: #2e7d32;
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.success-box p {
    color: #388e3c;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.btn {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background-color: orangered;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.btn:hover {
    background-color: darkorange;
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

    <div class="success-container">
        <div class="success-box">
            <h1>Content Published Successfully</h1>
            <p>Your update has been posted and is now visible on the platform.</p>
            <a href="academicAccount.php" class="btn">OKAY</a>
            <a href="../../index.php" class="btn">VIEW POST</a>
        </div>
    </div>
</body>
</html>

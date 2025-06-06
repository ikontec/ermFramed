<?php
    include 'connect.php';

    // Fetch data from the parent table
    $get_data = $conn->query("SELECT image_id, image, title FROM back_site");
    if (!$get_data) {
        die("Error fetching exam name: " . $conn->error);
    }
    $site_data = $get_data->fetch_assoc();
    $title = $site_data['title'];
    $image = $site_data['image'];
?>

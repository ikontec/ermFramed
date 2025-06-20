<?php
include 'classes/connect.php';

header('Content-Type: application/json'); // Important for proper JSON response

if (isset($_GET['term']) && isset($_GET['class'])) {
    $term = trim($_GET['term']);
    $class = trim($_GET['class']);

    if (!empty($term) && !empty($class)) {
        $like = "%" . $term . "%";

        $query = "SELECT student_id, first_name, middle_name, last_name 
                  FROM students 
                  WHERE class = ? 
                  AND CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ? 
                  LIMIT 10";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ss", $class, $like);
            $stmt->execute();
            $result = $stmt->get_result();

            $suggestions = [];
            while ($row = $result->fetch_assoc()) {
                $fullName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                $suggestions[] = $fullName;
            }

            echo json_encode($suggestions);
            exit;
        } else {
            // Debugging only: echo error (remove in production)
            echo json_encode(["error" => "DB error: " . $conn->error]);
            exit;
        }
    }
}

// Fallback if parameters are not set or empty
echo json_encode([]);
exit;
?>


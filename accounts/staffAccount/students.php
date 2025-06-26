<?php
session_start(); // Start the session

// Include the database connection file
include 'classes/connect.php';

// --- Session and Logout Logic ---
// Ensure the staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php"); // Redirect to the login page if not logged in
    exit();
}

// Function to log out
function logout() {
    $_SESSION = array(); // Unset all of the session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to the login page
    exit();
}

// Check if the logout button has been clicked
if (isset($_POST['logout'])) {
    logout();
}

// --- Initialize Variables and Messages ---
$successMessage = "";
$errorMessage = "";
$selectedSubject = $_POST['subject'] ?? ''; // Keep selected values on form
$selectedClass = $_POST['class'] ?? '';     // Keep selected values on form
$current_year = date('Y'); // Default to current year

// Check for success/error messages from previous redirects (if any are still coming)
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']);
}
if (isset($_SESSION['errorMessage'])) {
    $errorMessage = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']);
}

// --- Logic for Deleting a Student from a Subject (New/Corrected Flow) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $student_id_to_delete = $_POST['student_id_to_delete'] ?? '';
    $subject_to_delete_from = $_POST['subject_to_delete_from'] ?? '';
    $year_to_delete_from = $_POST['year_to_delete_from'] ?? date('Y');

    if (!empty($student_id_to_delete) && !empty($subject_to_delete_from)) {
        $subject_lower_delete = strtolower($subject_to_delete_from);

        if ($stmt = $conn->prepare("DELETE FROM $subject_lower_delete WHERE student_id = ? AND year = ?")) {
            $stmt->bind_param("si", $student_id_to_delete, $year_to_delete_from);
            if ($stmt->execute()) {
                $_SESSION['successMessage'] = "Student successfully removed from " . htmlspecialchars($subject_to_delete_from) . ".";
            } else {
                $_SESSION['errorMessage'] = "Error removing student: " . $conn->error;
            }
            $stmt->close();
        } else {
            $_SESSION['errorMessage'] = "Database error preparing delete statement: " . $conn->error;
        }
    } else {
        $_SESSION['errorMessage'] = "Invalid data for deleting student from subject.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?subject=" . urlencode($subject_to_delete_from) . "&class=" . urlencode($_POST['class_after_delete']) . "&year=" . urlencode($year_to_delete_from));
    exit();
}
// --- Logic for Fetching Students List ---
// Initialize variables for displaying students
$displayStudents = false;
$studentsToDisplay = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fetch_students_list'])) {
    // Re-assign selected values from POST for display and query
    $selectedSubject = $_POST['subject'] ?? '';
    $selectedClass = $_POST['class'] ?? '';
    $current_year = $_POST['current_year'] ?? date('Y');

    $subject_lower = strtolower($selectedSubject);

    // Check if the subject table exists to ensure enrollment can be verified
    $tableExists = false;
    // Check if the connection ($conn) is valid before preparing the statement
    if ($conn && $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1")) {
        $stmt->bind_param("s", $subject_lower);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $tableExists = true;
        }
        $stmt->close();
    } else {
        $errorMessage .= "Database error checking subject table existence: " . ($conn ? $conn->error : "No database connection.");
    }

    if (!$tableExists) {
        $errorMessage .= "Subject table '" . htmlspecialchars($subject_lower) . "' does not exist, or students are not associated with subjects via this method.";
    } else {
        // Prepare the SQL query to fetch students for the given class, who are also in the subject table for the current year
        $students_query_sql = "
            SELECT S.student_id, S.first_name, S.middle_name, S.last_name
            FROM students S
            WHERE S.class = ? AND S.student_id IN (SELECT student_id FROM $subject_lower WHERE year = ?)
            ORDER BY S.first_name ASC
        ";

        if ($conn && $stmt = $conn->prepare($students_query_sql)) {
            $stmt->bind_param("si", $selectedClass, $current_year);
            $stmt->execute();
            $students_result = $stmt->get_result();

            if ($students_result->num_rows > 0) {
                while ($student_row = $students_result->fetch_assoc()) {
                    $studentsToDisplay[] = $student_row;
                }
                $displayStudents = true; // Set flag to display the table
            } else {
                $errorMessage = "No students found in " . htmlspecialchars($selectedClass) . " who are enrolled in " . htmlspecialchars($selectedSubject) . " for " . htmlspecialchars($current_year) . ".";
            }
            $stmt->close();
        } else {
            $errorMessage = "Database error preparing students list query: " . ($conn ? $conn->error : "No database connection.");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['subject'])) {
    // This block handles the redirect after a delete operation
    $selectedSubject = $_GET['subject'] ?? '';
    $selectedClass = $_GET['class'] ?? '';
    $current_year = $_GET['year'] ?? date('Y');

    // Re-run the logic to fetch students based on the GET parameters
    $subject_lower = strtolower($selectedSubject);

    $tableExists = false;
    if ($conn && $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1")) {
        $stmt->bind_param("s", $subject_lower);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $tableExists = true;
        }
        $stmt->close();
    }

    if ($tableExists) {
        $students_query_sql = "
            SELECT S.student_id, S.first_name, S.middle_name, S.last_name
            FROM students S
            WHERE S.class = ? AND S.student_id IN (SELECT student_id FROM $subject_lower WHERE year = ?)
            ORDER BY S.first_name ASC
        ";

        if ($conn && $stmt = $conn->prepare($students_query_sql)) {
            $stmt->bind_param("si", $selectedClass, $current_year);
            $stmt->execute();
            $students_result = $stmt->get_result();

            if ($students_result->num_rows > 0) {
                while ($student_row = $students_result->fetch_assoc()) {
                    $studentsToDisplay[] = $student_row;
                }
                $displayStudents = true;
            }
            $stmt->close();
        }
    }
}

// Ensure the database connection is closed at the end of the script
if ($conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Class Students</title>
    <link rel="stylesheet" href="mystyle.css">
    <link rel="stylesheet" href="Staff_Styles/pc.css">
    <link rel="stylesheet" href="Staff_Styles/tablet.css">
    <link rel="stylesheet" href="Staff_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    <main class="upt">
        <div class="form-container">
            <h2>View Students in Class and Subject</h2>

            <?php if (!empty($errorMessage)): ?>
                <div class="message error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            <?php if (!empty($successMessage)): ?>
                <div class="message success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>

            <form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="current_year" value="<?php echo htmlspecialchars($current_year); ?>">

                <select id="subject" name="subject" class="form-select" required>
                    <option value="">Select Subject</option>
                    <option value="English" <?php echo ($selectedSubject == 'English') ? 'selected' : ''; ?>>English</option>
                    <option value="Kiswahili" <?php echo ($selectedSubject == 'Kiswahili') ? 'selected' : ''; ?>>Kiswahili</option>
                    <option value="Maths" <?php echo ($selectedSubject == 'Maths') ? 'selected' : ''; ?>>Maths</option>
                    <option value="Biology" <?php echo ($selectedSubject == 'Biology') ? 'selected' : ''; ?>>Biology</option>
                    <option value="Physics" <?php echo ($selectedSubject == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                    <option value="Chemistry" <?php echo ($selectedSubject == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                    <option value="Geography" <?php echo ($selectedSubject == 'Geography') ? 'selected' : ''; ?>>Geography</option>
                    <option value="History" <?php echo ($selectedSubject == 'History') ? 'selected' : ''; ?>>History</option>
                    <option value="Civics" <?php echo ($selectedSubject == 'Civics') ? 'selected' : ''; ?>>Civics</option>
                    <option value="Computer" <?php echo ($selectedSubject == 'Computer') ? 'selected' : ''; ?>>Computer</option>
                </select>

                <select id="class" name="class" class="form-select" required>
                    <option value="">Select Class</option>
                    <option value="Form One" <?php echo ($selectedClass == 'Form One') ? 'selected' : ''; ?>>Form One</option>
                    <option value="Form Two" <?php echo ($selectedClass == 'Form Two') ? 'selected' : ''; ?>>Form Two</option>
                    <option value="Form Three" <?php echo ($selectedClass == 'Form Three') ? 'selected' : ''; ?>>Form Three</option>
                    <option value="Form Four" <?php echo ($selectedClass == 'Form Four') ? 'selected' : ''; ?>>Form Four</option>
                </select>
                <button type="submit" name="fetch_students_list" class="form-button">View Students List</button>
            </form>

            <?php if ($displayStudents && !empty($studentsToDisplay)): ?>
                <div class="table-container">
                    <h4>Students Registered in <?php echo htmlspecialchars($selectedClass); ?> - <?php echo htmlspecialchars($selectedSubject); ?></h4>
                    <table class="student-list-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 0; // Initialize the counter outside the loop
                            foreach ($studentsToDisplay as $student):
                                $counter++;
                            ?>
                                <tr>
                                    <td><?php echo $counter; ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Are you sure you want to remove this student from <?php echo htmlspecialchars($selectedSubject); ?>?');">
                                            <input type="hidden" name="delete_student" value="1">
                                            <input type="hidden" name="student_id_to_delete" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                            <input type="hidden" name="subject_to_delete_from" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                                            <input type="hidden" name="year_to_delete_from" value="<?php echo htmlspecialchars($current_year); ?>">
                                            <input type="hidden" name="class_after_delete" value="<?php echo htmlspecialchars($selectedClass); ?>">
                                            <button class="delete-btn" type="submit">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            <?php elseif (isset($_POST['fetch_students_list']) || (isset($_GET['subject']) && empty($errorMessage))): ?>
                <p class="no-data-message">No students found for the selected criteria. Please ensure students are enrolled in this class and subject.</p>
            <?php endif; ?>
        </div>
    </main>
    </div>
    <script src="script.js"></script>
</body>
</html>
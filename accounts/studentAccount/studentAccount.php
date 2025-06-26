<?php
session_start(); // Start the session

// Database connection
include 'classes/connect.php';

// Initialize messages for user feedback (for initial page load or non-AJAX actions)
$successMessage = '';
$errorMessage = '';

// Check if the request is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Function to generate the subject list HTML
function generateSubjectListHtml($conn, $student_id, $studentClass, $subject_tables, $display_year) {
    ob_start(); // Start output buffering to capture HTML
    if ($display_year) {
        echo "<div class='subjects-list'>";
        foreach ($subject_tables as $subject_name => $table_name) {
            $query = "SELECT COUNT(*) as count FROM $table_name WHERE student_id = ? AND year = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("is", $student_id, $display_year);
                $stmt->execute();
                $count = 0;
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    // Student is enrolled in this subject
                    echo "<div>
                              <form class='subject-action-form'>
                                  <input type='hidden' name='year' value='" . htmlspecialchars($display_year) . "'>
                                  <input type='hidden' name='subject_name' value='" . htmlspecialchars($subject_name) . "'>
                                  <input type='hidden' name='action' value='unenroll'>
                                  <button type='submit' id='btn'>" . htmlspecialchars($subject_name) . "</button>
                              </form>
                          </div>";
                } else {
                    // Student is not enrolled in this subject
                    echo "<div>
                              <form class='subject-action-form'>
                                  <input type='hidden' name='year' value='" . htmlspecialchars($display_year) . "'>
                                  <input type='hidden' name='subject_name' value='" . htmlspecialchars($subject_name) . "'>
                                  <input type='hidden' name='action' value='enroll'>
                                  <button type='submit' id='btnx'>" . htmlspecialchars($subject_name) . "</button>
                              </form>
                          </div>";
                }
            } else {
                echo "<p class='error-message'>Error preparing subject check statement for " . htmlspecialchars($subject_name) . ": " . $conn->error . "</p>";
            }
        }
        echo "</div>";
    } else {
        echo "<p class='message'>Enter a year to view and manage subjects.</p>";
    }
    return ob_get_clean(); // Return the captured HTML
}


// Check if the logout button has been clicked
if (isset($_POST['logout'])) {
    $_SESSION = array(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to the login page
    exit();
}

// Ensure the student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

$student_id = $_SESSION['student_id'];
$student = null;
$student_parent = null;

// --- Fetch Student Data ---
if ($stmt = $conn->prepare("SELECT first_name, middle_name, last_name, photo, date_of_birth, gender, class, username, url FROM students WHERE student_id = ?")) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
} else {
    $errorMessage .= "Database error fetching student data: " . $conn->error . "<br>";
}

// If student data isn't found, redirect to login as a safety measure
if (!$student) {
    header("Location: login.php");
    exit();
}

// Sanitize and assign student variables for display
$studentName = htmlspecialchars($student['first_name'] . " " . $student['middle_name'] . " " . $student['last_name']);
$studentPhoto = htmlspecialchars($student['photo']);
$studentGender = htmlspecialchars($student['gender']);
$studentClass = htmlspecialchars($student['class']);
$studentUsername = htmlspecialchars($student['username']);
$studentPassword = htmlspecialchars($student['url']);

// --- Fetch Parent Data ---
if ($stmt = $conn->prepare("SELECT first_name, last_name, gender, relationship, phone, email FROM parent WHERE student_id = ?")) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $parent_result = $stmt->get_result();
    $student_parent = $parent_result->fetch_assoc();
    $stmt->close();
} else {
    $errorMessage .= "Database error fetching parent data: " . $conn->error . "<br>";
}

// Sanitize and assign parent variables for display
$ParentName = htmlspecialchars($student_parent['first_name'] . " " . $student_parent['last_name'] ?? '');
$ParentGender = htmlspecialchars($student_parent['gender'] ?? '');
$ParentEmail = htmlspecialchars($student_parent['email'] ?? '');
$ParentContact = htmlspecialchars($student_parent['phone'] ?? '');
$ParentRelationship = htmlspecialchars($student_parent['relationship'] ?? '');


// --- Handle Profile Update (Remains full page reload for simplicity of file upload) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $file_uploaded = false;
    $upload_dir_server = "../../uploads/"; // Physical path for saving
    $upload_dir_db = "uploads/";           // Path stored in DB
    $final_file_path = $studentPhoto;      // Default to current photo if no new one uploaded

    // Check if a new file was uploaded
    if (!empty($_FILES["photo"]["tmp_name"])) {
        $imageFileType = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Check if it's an actual image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if ($check === false) {
            $errorMessage .= "File is not an image.<br>";
            $uploadOk = 0;
        }

        // Allow only certain file formats
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        if (!in_array($imageFileType, $allowedTypes)) {
            $errorMessage .= "Sorry, only JPG, JPEG & PNG files are allowed.<br>";
            $uploadOk = 0;
        }

        // Generate safe filename to prevent overwriting and malicious names
        $safeFileName = uniqid("profile_", true) . "." . $imageFileType;
        $target_file_server = $upload_dir_server . $safeFileName;
        $final_file_path = $upload_dir_db . $safeFileName; // Path to store in DB

        // Attempt file upload
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file_server)) {
                $file_uploaded = true;
                $successMessage .= "Profile photo uploaded successfully.<br>";
            } else {
                $errorMessage .= "Sorry, there was an error uploading your file.<br>";
            }
        } else {
            $errorMessage .= "Your file was not uploaded.<br>";
        }
    }

    // Collect and sanitize form data for update
    $newUsername = htmlspecialchars($_POST['username']);
    $newPassword = htmlspecialchars($_POST['password']); // WARNING: NOT HASHED

    // Update database based on whether a new photo was uploaded
    if ($file_uploaded) {
        $stmt = $conn->prepare("UPDATE students SET username = ?, url = ?, photo = ? WHERE student_id = ?");
        $stmt->bind_param("sssi", $newUsername, $newPassword, $final_file_path, $student_id);
    } else {
        $stmt = $conn->prepare("UPDATE students SET username = ?, url = ? WHERE student_id = ?");
        $stmt->bind_param("ssi", $newUsername, $newPassword, $student_id);
    }

    if ($stmt->execute()) {
        $successMessage .= "Account updated successfully!";
        // Redirect to refresh the page and display updated data/messages (PRG pattern)
        header("Location: studentAccount.php?profile_updated=true"); // Added parameter for clarity
        exit;
    } else {
        $errorMessage .= "Error updating account: " . $stmt->error;
    }
    $stmt->close();
}


// --- Define Subject Tables ---
$subject_tables = [
    "Maths" => "maths",
    "English" => "english",
    "Kiswahili" => "kiswahili",
    "History" => "history",
    "Geography" => "geography",
    "Biology" => "biology",
    "Chemistry" => "chemistry",
    "Physics" => "physics",
    "Computer" => "computer",
    "Civics" => "civics"
];

// --- Handle AJAX Requests for Class Update & Subject Enrollment/Unenrollment ---
if ($is_ajax) {
    $response = ['status' => 'error', 'message' => 'Invalid request.'];

    // Handle Class Update via AJAX
    if (isset($_POST['update_class_ajax'])) {
        $new_class = htmlspecialchars($_POST['class']);

        if ($stmt = $conn->prepare("UPDATE students SET class = ? WHERE student_id = ?")) {
            $stmt->bind_param("si", $new_class, $student_id);
            if ($stmt->execute()) {
                $studentClass = $new_class; // Update local variable immediately
                $response = ['status' => 'success', 'message' => 'Class updated successfully.', 'new_class' => $new_class];
            } else {
                $response = ['status' => 'error', 'message' => "Error updating class: " . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['status' => 'error', 'message' => "Database error preparing class update: " . $conn->error];
        }
    }
    // Handle Subject Enrollment/Unenrollment via AJAX
    else if (isset($_POST['action']) && isset($_POST['subject_name']) && isset($_POST['year'])) {
        $action = $_POST['action'];
        $subject_name_key = htmlspecialchars($_POST['subject_name']); // Subject key from the form
        $year = htmlspecialchars($_POST['year']);
        $subjectsHtml = ''; // To store the updated subjects HTML

        // Validate subject name against our allowed list
        if (!array_key_exists($subject_name_key, $subject_tables)) {
            $response = ['status' => 'error', 'message' => "Invalid subject selected."];
        } else if (!preg_match('/^\d{4}$/', $year)) {
            $response = ['status' => 'error', 'message' => "Invalid year format. Please enter a valid year (YYYY)."];
        } else {
            $table_name = $subject_tables[$subject_name_key];

            if ($action === 'enroll') {
                // Check if already enrolled to prevent duplicates
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM $table_name WHERE student_id = ? AND year = ?");
                if ($check_stmt) {
                    $check_stmt->bind_param("is", $student_id, $year);
                    $check_stmt->execute();
                    $check_stmt->bind_result($count);
                    $check_stmt->fetch();
                    $check_stmt->close();

                    if ($count > 0) {
                        $response = ['status' => 'error', 'message' => "Already enrolled in " . $subject_name_key . " for the year " . $year . "."];
                    } else {
                        // Enroll the student
                        $stmt = $conn->prepare("INSERT INTO $table_name (student_id, class, year) VALUES (?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iss", $student_id, $studentClass, $year);
                            if ($stmt->execute()) {
                                $response = ['status' => 'success', 'message' => "Successfully enrolled in " . $subject_name_key . "."];
                            } else {
                                $response = ['status' => 'error', 'message' => "Error enrolling in " . $subject_name_key . ": " . $stmt->error];
                            }
                            $stmt->close();
                        } else {
                            $response = ['status' => 'error', 'message' => "Error preparing enrollment statement: " . $conn->error];
                        }
                    }
                } else {
                    $response = ['status' => 'error', 'message' => "Database error preparing enrollment check: " . $conn->error];
                }
            } elseif ($action === 'unenroll') {
                // Unenroll the student
                $stmt = $conn->prepare("DELETE FROM $table_name WHERE student_id = ? AND year = ?");
                if ($stmt) {
                    $stmt->bind_param("is", $student_id, $year);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => "Successfully terminated from " . $subject_name_key . "."];
                    } else {
                        $response = ['status' => 'error', 'message' => "Error terminating from " . $subject_name_key . ": " . $stmt->error];
                    }
                    $stmt->close();
                } else {
                    $response = ['status' => 'error', 'message' => "Error preparing unenrollment statement: " . $conn->error];
                }
            }
        }
        // After any subject enrollment/unenrollment action, regenerate the subject list HTML
        // and include it in the response so JS can update it.
        $subjectsHtml = generateSubjectListHtml($conn, $student_id, $studentClass, $subject_tables, $year);
        $response['subjectsHtml'] = $subjectsHtml;
    }
    // Handle Subject Year Check via AJAX
    else if (isset($_POST['check_subjects_ajax']) && isset($_POST['year'])) {
        $year = htmlspecialchars($_POST['year']);
        if (!preg_match('/^\d{4}$/', $year)) {
            $response = ['status' => 'error', 'message' => "Invalid year format. Please enter a valid year (YYYY)."];
        } else {
            $subjectsHtml = generateSubjectListHtml($conn, $student_id, $studentClass, $subject_tables, $year);
            $response = ['status' => 'success', 'message' => 'Subjects fetched.', 'subjectsHtml' => $subjectsHtml];
        }
    }


    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Stop script execution after sending JSON response
}

// --- Regular Page Load Logic (if not AJAX) ---
// Check for messages passed via URL after redirection (for profile update)
if (isset($_GET['profile_updated'])) {
    if (!empty($successMessage)) {
        // successMessage would be set by the POST handling above if successful
    } elseif (!empty($errorMessage)) {
        // errorMessage would be set by the POST handling above if unsuccessful
    } else {
        // Fallback if no specific message was set by PHP
        $successMessage = "Profile updated successfully!";
    }
}

// Determine the year to display subjects for on initial page load if needed.
// For AJAX, this will be handled dynamically.
$display_year_initial = date("Y"); // Default to current year

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="Student_Styles/pc.css">
    <link rel="stylesheet" href="Student_Styles/tablet.css">
    <link rel="stylesheet" href="Student_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="../../index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="helpCenter.html"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <form method="post" action="">
                <button type="submit" class="logout-btn" name="logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </ul>
    </nav>

    <div id="studentProfile" class="studentProfile" style="display: none;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="document.getElementById('studentProfile').style.display='none'">X</span>

        <?php if (!empty($successMessage) && isset($_GET['profile_updated'])): ?>
            <div class="message success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage) && isset($_GET['profile_updated'])): ?>
            <div class="message error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <section class="set">
            <h4>Student Details</h4>
            <img src="../../<?php echo $studentPhoto; ?>" alt="Student Photo">
            <span><b>Full Name:</b> <?php echo $studentName; ?></span>
            <span><b>Username:</b> <?php echo $studentUsername; ?></span>
            <span><b>Gender:</b> <?php echo $studentGender; ?></span>
            <span><b>Password:</b> <?php echo $studentPassword; ?></span>
        </section>
        <section class="set">
            <h4>Parent Details</h4>
            <span><b>Full Name:</b> <?php echo $ParentName; ?></span>
            <span><b>Gender</b>: <?php echo $ParentGender; ?></span>
            <span><b>Relationship</b>: <?php echo $ParentRelationship; ?></span>
            <span><b>Email:</b> <?php echo $ParentEmail; ?></span>
            <span><b>Contact:</b> +255<?php echo $ParentContact; ?></span>
        </section>
        <button id="profileUpdateBtn" type="button" style="display: block;" onclick="showEditor()">Update Profile</button>

        <section class="set" id="editor" style="display: none;">
            <h4>Update Profile</h4>
            <form method="post" action="" enctype="multipart/form-data">
                <label for="photo">Profile Photo:</label>
                <input type="file" name="photo" id="photo" accept="image/*"><br><br>

                <label for="username">Username:</label>
                <input type="text" name="username" id="username_profile" value="<?php echo htmlspecialchars($studentUsername); ?>" required><br><br>

                <label for="password">Password:</label>
                <input type="text" name="password" id="password_profile" value="<?php echo htmlspecialchars($studentPassword); ?>" required><br><br>

                <button type="submit" name="update_profile">Save Changes</button>
                <button type="button" onclick="hideEditor()">Cancel</button>
            </form>
        </section>
    </div>



    <div id="studentSubjects" class="studentSubjects" style="display: none;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="hideSubjects()">X</span>
        <div class="noti-update1" id="subjects-container">
            <div id="ajax-messages">
                </div>

            <h4>Update Current Class</h4>
            <div class="update-class">
                <form id="updateClassForm" method="POST" action="">
                    <select id="class_select" name="class" required>
                        <option value="<?php echo htmlspecialchars($studentClass); ?>"><?php echo htmlspecialchars($studentClass); ?> (Current)</option>
                        <option value="Form One">Form One</option>
                        <option value="Form Two">Form Two</option>
                        <option value="Form Three">Form Three</option>
                        <option value="Form Four">Form Four</option>
                    </select>
                    <button type="submit">Update Class</button>
                </form>
            </div>

            <div class="enroller">
                <form id="checkSubjectsForm" method="POST" action="" class="year">
                    <label style="font-weight: bold;">My Subjects</label><br>
                    <label for="input_year">Enter Year:</label>
                    <input id="input_year" type="text" name="year" placeholder="YYYY" required value="<?php echo date('Y'); ?>">
                    <button type="submit">Check Subjects</button>
                </form><br>
                <span class="key">
                    <h5>Key</h5>
                    <span class="yes">Enrolled (Click to unenroll)</span>
                    <span class="no">Not Enrolled (Click to enroll)</span>
                </span><br>

                <div id="subjects-list-container">
                    <?php
                        // On initial page load, display subjects for the current year
                        echo generateSubjectListHtml($conn, $student_id, $studentClass, $subject_tables, $display_year_initial);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div id="academicResults" class="academicResults" style="display: none;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="document.getElementById('academicResults').style.display='none'">X</span>
        <h4>Academic Results</h4>
        <section class='results'>
            <form action="" method="post">
                 <select id="class" name="exam_id" required>
                     <option value="">Select Examination Season</option>
                     <?php
                     // Fetch the latest exams ordered by date
                     $exams_query = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC");
                     if (!$exams_query) {
                         die('Error fetching exams: ' . $conn->error);
                     }
                     while ($exam = $exams_query->fetch_assoc()) {
                         echo '<option value="' . $exam['exam_id'] . '">' . htmlspecialchars($exam['exam_name']) . '</option>';
                     }
                     ?>
                </select>
                 <button id="btn" type="submit" name="fetch_results">Retrieve Results</button>
            </form>

            <?php
                // Function to calculate grades
                function calculate_grade($marks) {
                    if ($marks >= 80) return 'A';
                    if ($marks >= 70) return 'B';
                    if ($marks >= 60) return 'C';
                    if ($marks >= 50) return 'D';
                    if ($marks >= 40) return 'E';
                    if ($marks >= 35) return 'S';
                    if ($marks >= 0) return 'F';
                    if ($marks === '-') return '-';
                }

                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fetch_results'])) {
                    $exam_id = $conn->real_escape_string($_POST['exam_id']);
                    $student_id = $_SESSION['student_id'];

                    // Fetching exam name
                    $title_query = $conn->query("SELECT exam_name FROM examinations WHERE exam_id = $exam_id");
                    if (!$title_query) {
                        die('Error fetching exam name: ' . $conn->error);
                    }

                    $name = $title_query->fetch_assoc();
                    $exam_name = $name['exam_name'];

                    // Fetch student marks from the class_marks table
                    $marks_query = $conn->query("SELECT * FROM class_marks WHERE exam_id = $exam_id AND student_id = $student_id");
                    if (!$marks_query) {
                        die('Error occurred when fetching marks: ' . $conn->error);
                    }

                    echo '<div class="tokeo">';
                    echo '<h4>RESULTS FOR ' . htmlspecialchars($exam_name) . '</h4>';
                    echo '<table border="1">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>SUBJECT</th>';
                    echo '<th>MARKS</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    $results = [];
                    $total_marks = 0;
                    $subject_count = 0;
                    $enrolled_subjects = 0;
                    $incomplete = false;

                    while ($output = $marks_query->fetch_assoc()) {
                        $subject = strtoupper($output['subject_name']);
                        $marks = $output['marks'];
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($subject) . '</td>';

                        if (is_numeric($marks)) {
                            echo '<td>' . htmlspecialchars($marks) . '</td>';
                            $results[$subject] = (int)$marks;
                            $total_marks += (int)$marks;
                            $subject_count++;
                        } else {
                            echo '<td>-</td>';
                            $results[$subject] = '-'; // Mark it as empty
                            $incomplete = true;
                        }

                        echo '</tr>';
                        $enrolled_subjects++;
                    }

                    echo '</tbody>';
                    echo '</table>';
                    echo '<br><br>';

                    if ($incomplete || $subject_count != $enrolled_subjects) {
                        $total_marks = '-';
                        $average_marks = '-';
                        $grade = '-';
                        $student_position = 'Incomplete Exam';
                    } else {
                        $average_marks = $subject_count > 0 ? $total_marks / $subject_count : 0;
                        $grade = calculate_grade($average_marks);

                        // Calculate the student's position in the class based on the entire class (no subject count condition)
                        $position_query = $conn->query("
                            SELECT student_id, AVG(marks) as average
                            FROM class_marks
                            WHERE exam_id = $exam_id
                            GROUP BY student_id
                            ORDER BY average DESC
                        ");
                        if (!$position_query) {
                            die("Error calculating position: " . $conn->error);
                        }

                        $position = 1;
                        $student_position = null;

                        // Loop through all students and determine the student's position
                        while ($row = $position_query->fetch_assoc()) {
                            if ($row['student_id'] == $student_id) {
                                $student_position = $position; // Found the student's position
                                break;
                            }
                            $position++;
                        }
                    }
            ?>

            <table border="1">
                <thead>
                    <tr>
                        <th>Evaluation</th>
                        <th>Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total</td>
                        <td><?= htmlspecialchars($total_marks) ?></td>
                    </tr>
                    <tr>
                        <td>Average</td>
                        <td><?= htmlspecialchars(is_numeric($average_marks) ? number_format($average_marks, 1) : '-') ?></td>
                    </tr>
                    <tr>
                        <td>Grade</td>
                        <td><?= htmlspecialchars($grade) ?></td>
                    </tr>
                    <tr>
                        <td>Position</td>
                        <td><?= htmlspecialchars($student_position) ?></td>
                    </tr>
                </tbody>
            </table>
            <?php
                echo '</div>';
            }
            ?>
        </section>
    </div>



    <div id="reports" class="reports" style="display: none;">
        <span style="color: red; font-weight: bolder; margin-left: 95%; cursor: pointer;" onclick="document.getElementById('reports').style.display='none'">X</span>

        <div class="header">
            <h3 class="heading"><?php echo $title; ?></h3><br><br>
            <h4 class="heading">GENERAL REPORT FOR <?= htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['last_name']) ?></h4><br><br>
            <h4 class="heading"><?= htmlspecialchars($student['class']).' '. date('Y');?></h4><br><br>
            <h4 class="heading"><?php  ?></h4> 
        </div>

        <div>
            <h4 style="text-align: center;">STUDENT'S ACADEMIC TREND</h4>
                <div class="results">
                    <?php
                    // Fetch the last three exams for the student
                    $exams_query = $conn->query("SELECT exam_id, exam_name FROM examinations ORDER BY date DESC LIMIT 3");
                    if (!$exams_query) {
                        die('Error fetching exams: ' . $conn->error);
                    }

                    $exam_data = [];

                    while ($exam = $exams_query->fetch_assoc()) {
                        $exam_id = $exam['exam_id'];
                        $exam_name = $exam['exam_name'];

                        // Fetch student marks for each exam
                        $marks_query = $conn->query("SELECT subject_name, marks FROM class_marks WHERE exam_id = $exam_id AND student_id = $student_id");
                        if (!$marks_query) {
                            die('Error occurred when fetching marks: ' . $conn->error);
                        }

                        $marks = [];
                        $total_marks = 0;
                        $subject_count = 0;
                        while ($mark = $marks_query->fetch_assoc()) {
                            $marks[] = [
                                'subject' => strtoupper($mark['subject_name']),
                                'score' => $mark['marks']
                            ];

                            if (is_numeric($mark['marks'])) {
                                $total_marks += $mark['marks'];
                                $subject_count++;
                            }
                        }

                        $average = ($subject_count > 0) ? $total_marks / $subject_count : 0;
                        $exam_data[] = [
                            'exam_name' => $exam_name,
                            'marks' => $marks,
                            'total' => $total_marks,
                            'average' => $average,
                            'grade' => calculate_grade($average)
                        ];
                    }

                    // Reverse the array to show the latest exam first
                    $exam_data = array_reverse($exam_data);

                    foreach ($exam_data as $data) {
                        echo '<div class="test">';
                        echo '<h4>' . htmlspecialchars($data['exam_name']) . '</h4>';
                        echo '<table border="1">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Subject</th>';
                        echo '<th>Marks</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        foreach ($data['marks'] as $mark) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($mark['subject']) . '</td>';
                            echo '<td>' . htmlspecialchars($mark['score']) . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody>';
                        echo '</table>';

                        if ($data['average'] > 0) {
                            echo '<p><strong>Total:</strong> ' . $data['total'] . '</p>';
                            echo '<p><strong>Average:</strong> ' . number_format($data['average'], 1) . '</p>';
                            echo '<p><strong>Grade:</strong> ' . htmlspecialchars($data['grade']) . '</p>';
                        } else {
                            echo '<p>No marks available</p>';
                        }

                        echo '</div><br>'; // Add a space between exam sections
                    }

                    // Prepare the arrays for the progress chart after reversing the exams
                    $averages = array_column($exam_data, 'average');
                    $exam_names = array_column($exam_data, 'exam_name');

                    // Determine the progress trend
                    $remark = 'Undetermined';
                    if (count($averages) === 3) {
                        if ($averages[0] > $averages[1] && $averages[1] > $averages[2]) {
                            $remark = 'Declining';
                        } elseif ($averages[0] < $averages[1] && $averages[1] < $averages[2]) {
                            $remark = 'Improving';
                        } elseif ($averages[0] === $averages[1] && $averages[1] === $averages[2]) {
                            $remark = 'Stagnant';
                        }
                    }
                    ?>
                </div>

            <div class="chart">
                <label>PROGRESS CHART:</label><br>
                <canvas id="progressChart" width="400" height="200"></canvas>
                <script>
                    var ctx = document.getElementById('progressChart').getContext('2d');
                    var progressChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode($exam_names) ?>,
                            datasets: [{
                                label: 'Average Marks',
                                data: <?= json_encode($averages) ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });
                </script>
                <label>REMARK: </label><?= htmlspecialchars($remark) ?>
            </div><br><br>

    <h4 style="text-align: center;">STUDENT'S DISCIPLINE TREND</h4>
            <div class="discipline">
                <?php
                // Query to fetch discipline cases for the student
                $discipline_query = $conn->query("SELECT case_description, action_taken, date_reported FROM discipline_cases WHERE student_id = $student_id ORDER BY date_reported ASC");
                if (!$discipline_query) {
                    die('Error fetching discipline cases: ' . $conn->error);
                }

                // Check if there are any discipline cases reported
                if ($discipline_query->num_rows > 0) {
                    // Display each discipline case
                    while ($case = $discipline_query->fetch_assoc()) {
                        $case_description = $case['case_description'];
                        $action_taken = $case['action_taken'];
                        $date_reported = date('F j, Y', strtotime($case['date_reported'])); // Format the date

                        echo '<div class="case">';
                        echo '<p><strong>Date Reported:</strong> ' . htmlspecialchars($date_reported) . '</p>';
                        echo '<p><strong>Case:</strong> ' . htmlspecialchars($case_description) . '</p>';
                        echo '<p><strong>Action Taken:</strong> ' . htmlspecialchars($action_taken) . '</p>';
                        echo '<hr>'; // Separator for each case
                        echo '</div>';
                    }
                } else {
                    // No cases reported
                    echo '<label>NO CASE REPORTED</label><br>';
                    echo '<label>REMARK: </label>KEEP IT UP';
                }
                ?>
            </div>

        </div>
        
    </div>
    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Student Dashboard</h2>
                <div class="user-info">
                    <span id="staffName"><img src="../../<?php echo $studentPhoto; ?>" alt="User Photo"> <?php echo $studentName; ?></span>
                </div>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="showProfile()">
                    <i class="fas fa-id-badge"></i>
                    <h3>My Profile</h3>
                    <p>View and update your personal information.</p>
                </div>

                <div class="dashboard-card" onclick="showSubjects()">
                    <i class="fas fa-book"></i>
                    <h3>My Subjects</h3>
                    <p>Subjects enrolled in this year.</p>
                </div>

                <div class="dashboard-card" onclick="showAcademicResults()">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Academic Results</h3>
                    <p>View your academic performance.</p>
                </div>                

                <div class="dashboard-card" onclick="showReports()">
                    <i class="fas fa-file-alt"></i>
                    <h3>Academic & Discipline Report</h3>
                    <p>Access your detailed reports.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='attendance.html'">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Student Attendance</h3>
                    <p>Check your attendance records.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='messages.html'">
                    <i class="fas fa-envelope"></i>
                    <h3>Parental Messages & Feedbacks</h3>
                    <p>Communicate with parents and view feedback.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='results.html'">
                    <i class="fas fa-money-bill"></i>
                    <h3>School Fee Payments</h3>
                    <p>View your fee payment details.</p>
                </div>                

                <div class="dashboard-card" onclick="location.href='calendar.html'">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>School Calendar</h3>
                    <p>View important school dates and events.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- Helper Function for Displaying Messages ---
        function displayMessage(type, message) {
            const messageDiv = document.getElementById('ajax-messages');
            messageDiv.innerHTML = `<div class="message ${type}-message">${message}</div>`;
            // Optionally, clear message after a few seconds
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }

        // --- JavaScript for Profile Editor (Remains mostly the same, as it's full page reload) ---
        function showEditor() {
            document.getElementById('profileUpdateBtn').style.display = 'none';
            document.getElementById('editor').style.display = 'block';
        }

        function hideEditor() {
            document.getElementById('editor').style.display = 'none';
            document.getElementById('profileUpdateBtn').style.display = 'block';
        }

        // --- JavaScript for Subjects Section (AJAX Enabled) ---
        function showSubjects() {
            document.getElementById("studentSubjects").style.display = "block";
            // Ensure the input year is set to the current year when opening, if not already set by a previous action
            const inputYear = document.getElementById("input_year");
            if (!inputYear.value) {
                inputYear.value = new Date().getFullYear();
            }

        }

        function hideSubjects() {
            document.getElementById("studentSubjects").style.display = "none";
            // Clear any AJAX messages when closing the modal
            document.getElementById('ajax-messages').innerHTML = '';
        }

        // --- JavaScript for showing/hiding main profile div ---
        function showProfile() {
            document.getElementById("studentProfile").style.display = "block";
        }

        // Function to hide the academic results section
        function showAcademicResults() {
            document.getElementById("academicResults").style.display = "block";
        }

        // Function to show the reports section
        function showReports() {
            document.getElementById("reports").style.display = "block";
        }


        // --- AJAX Form Handlers ---
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Update Class Form Submission
            const updateClassForm = document.getElementById('updateClassForm');
            if (updateClassForm) {
                updateClassForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent default form submission (full page reload)

                    const formData = new FormData(this);
                    formData.append('update_class_ajax', '1'); // Add a flag for PHP to identify this AJAX request

                    fetch('studentAccount.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            displayMessage('success', data.message);
                            document.getElementById('class_select').value = data.new_class;
                        } else {
                            displayMessage('error', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        displayMessage('error', 'An error occurred while updating class.');
                    });
                });
            }

            // Handle result fetching for subjects

            // Handle Check Subjects Form Submission
            const checkSubjectsForm = document.getElementById('checkSubjectsForm');
            if (checkSubjectsForm) {
                checkSubjectsForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent default form submission

                    const formData = new FormData(this);
                    formData.append('check_subjects_ajax', '1'); // Flag for PHP

                    fetch('studentAccount.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('subjects-list-container').innerHTML = data.subjectsHtml;
                            displayMessage('success', data.message);
                        } else {
                            displayMessage('error', data.message);
                            document.getElementById('subjects-list-container').innerHTML = `<p class='message'>${data.message}</p>`; // Clear list on error
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        displayMessage('error', 'An error occurred while fetching subjects.');
                    });
                });
            }

            // Handle Enroll/Unenroll Subject Button Clicks (using event delegation)
            // Listen for clicks on the parent container, because subject forms are dynamically added/removed
            const subjectsListContainer = document.getElementById('subjects-list-container');
            if (subjectsListContainer) {
                subjectsListContainer.addEventListener('submit', function(event) {
                    if (event.target.matches('.subject-action-form')) {
                        event.preventDefault(); // Prevent default form submission

                        const formData = new FormData(event.target);
                        // No additional flag needed, as 'action', 'subject_name', 'year' are already present

                        fetch('studentAccount.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                displayMessage('success', data.message);
                                // Update the subjects list HTML with the fresh list from the server
                                if (data.subjectsHtml) {
                                    document.getElementById('subjects-list-container').innerHTML = data.subjectsHtml;
                                }
                            } else {
                                displayMessage('error', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            displayMessage('error', 'An error occurred during subject action.');
                        });
                    }
                });
            }

            // Initial check for profile update messages from full page reload
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('profile_updated')) {
                // If profile was updated, ensure the profile modal is shown and message displayed
                showProfile();
                urlParams.delete('profile_updated');
                window.history.replaceState({}, document.title, "?" + urlParams.toString());
            }
        });

    </script>
    <script src="script.js"></script>
</body>
</html>
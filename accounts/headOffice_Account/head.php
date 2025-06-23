<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Head Office Dashboard</title>
    <link rel="stylesheet" href="HeadOffice_Styles/pc.css">
    <link rel="stylesheet" href="HeadOffice_Styles/tablet.css">
    <link rel="stylesheet" href="HeadOffice_Styles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header>
        <img src="../../../images/default.png" alt="school logo" class="logo">
        <h1 class="schoolName">KWAUSO SECONDARY SCHOOL</h1>
    </header>

    <nav class="nav">
        <button class="menuButton" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-chevron-down" id="menuIcon"></i>
        </button>
        <ul>
            <li><a href="../../index.html"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="helpCenter.html"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </ul>
    </nav>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Head Office Dashboard</h2>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="location.href='feePayments.html'">
                    <i class="fas fa-money-check-alt"></i>
                    <h3>Track Fee Payments</h3>
                    <p>Monitor and manage student fee payments.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='messages.html'">
                    <i class="fas fa-envelope"></i>
                    <h3>Messages</h3>
                    <p>Send and view messages to/from staff, students, and parents.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='posts.html'">
                    <i class="fas fa-bullhorn"></i>
                    <h3>Make Posts</h3>
                    <p>Publish announcements and updates for the school community.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='editProfile.html'">
                    <i class="fas fa-user-cog"></i>
                    <h3>Edit School Profile</h3>
                    <p>Update school information and profile details.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='staffManagement.html'">
                    <i class="fas fa-users-cog"></i>
                    <h3>Staff Management</h3>
                    <p>View, add, or update staff records and roles.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='studentManagement.html'">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Student Management</h3>
                    <p>View, add, or update student records.</p>
                </div>

                <div class="dashboard-card" onclick="location.href='reports.html'">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Reports & Analytics</h3>
                    <p>View school performance and analytics reports.</p>
                </div>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
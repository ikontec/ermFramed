<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KWAUSO SECONDARY SCHOOL</title>
    <link rel="stylesheet" href="myStyles/pc.css">
    <link rel="stylesheet" href="myStyles/tablet.css">
    <link rel="stylesheet" href="myStyles/phone.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            <li><a href="../../index.html"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="admission.html"><i class="fas fa-user-plus"></i> Admission</a></li>
            <li><a href="admission.html"><i class="fas fa-users"></i> Our Team</a></li>
            <li><a href="helpCenter.html"><i class="fas fa-question-circle"></i> Help Center</a></li>
            <li><a href="about.html"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li><a href="contact.html"><i class="fas fa-envelope"></i> Contact Us</a></li>
            <li><a href="https://ikonteki.wuaze.com"><i class="fas fa-blog"></i> Developer's Blog</a></li>
        </ul>
    </nav>    

    <main>
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            <form action="accounts/staffAccount/teacherAccount.html" method="post" autocomplete="off">
                <div>
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit"><i class="fas fa-arrow-right"></i> Login</button>
            </form>
            <div class="links">
                <a href="forgotPassword.html">Forgot Password?</a>
            </div>
        </div>
    </main>
    <script src="script.js"></script>
</body>
</html>
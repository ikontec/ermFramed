        // Toggle menu and arrow icon
        const menuButton = document.getElementById('menuToggle');
        const menuIcon = document.getElementById('menuIcon');
        const navList = document.querySelector('.nav ul');

        menuButton.addEventListener('click', function() {
            navList.classList.toggle('show');
            if (navList.classList.contains('show')) {
                menuIcon.classList.remove('fa-chevron-down');
                menuIcon.classList.add('fa-chevron-up');
            } else {
                menuIcon.classList.remove('fa-chevron-up');
                menuIcon.classList.add('fa-chevron-down');
            }
        });
        
/**
 * common.js
 * Global scripts for SCC-CCS Syllabus Portal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global Logout Confirmation
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const logoutUrl = this.getAttribute('href') || '../logout.php';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Sign Out?',
                    text: "Are you sure you want to log out?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff8800',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Logout',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = logoutUrl;
                    }
                });
            } else {
                if (confirm("Are you sure you want to log out?")) {
                    window.location.href = logoutUrl;
                }
            }
        });
    });
});

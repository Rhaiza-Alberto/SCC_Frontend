/**
 * common.js
 * Global scripts for SCC-CCS Syllabus Portal
 */

document.addEventListener('DOMContentLoaded', function () {
    // Global Logout Confirmation
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function (e) {
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
                    cancelButtonText: 'Cancel',
                    customClass: {
                        popup: 'scc-swal-popup'
                    }
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

    // Mobile sidebar — close on outside click
    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (sidebar && sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            (!toggle || !toggle.contains(e.target))) {
            sidebar.classList.remove('open');
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.remove('active');
        }
    });

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });
});

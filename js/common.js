/**
 * common.js
 * Global scripts for SCC-CCS Syllabus Portal
 * Includes: Logout confirm, sidebar close, alert dismiss, inactivity timeout
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

    // ── Auto Session Timeout ─────────────────────────────────────────────────
    // Session expires after TIMEOUT_MS of inactivity; warns at WARN_MS before
    const TIMEOUT_MS  = 30 * 60 * 1000; // 30 minutes
    const WARN_MS     =  2 * 60 * 1000; //  2 minutes warning before logout
    const LOGOUT_URL  = (function () {
        // Resolve the logout URL relative to the current depth
        const depth = (window.location.pathname.match(/\//g) || []).length - 1;
        return depth > 1 ? '../logout.php' : 'logout.php';
    })();

    let idleTimer, warnTimer, warnShown = false;

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        clearTimeout(warnTimer);
        warnShown = false;

        // Schedule the warning 2 min before timeout
        warnTimer = setTimeout(function () {
            if (typeof Swal !== 'undefined' && !warnShown) {
                warnShown = true;
                let countdown = Math.floor(WARN_MS / 1000);
                let swalTimer;

                Swal.fire({
                    title: 'Session Expiring Soon',
                    html: `You will be logged out due to inactivity in <b id="scc-countdown">${countdown}</b> seconds.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff8800',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Keep me logged in',
                    cancelButtonText: 'Logout now',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: WARN_MS,
                    timerProgressBar: true,
                    didOpen: function () {
                        swalTimer = setInterval(function () {
                            countdown--;
                            const el = document.getElementById('scc-countdown');
                            if (el) el.textContent = countdown;
                            if (countdown <= 0) clearInterval(swalTimer);
                        }, 1000);
                    },
                    willClose: function () {
                        clearInterval(swalTimer);
                    }
                }).then(function (result) {
                    if (result.isDismissed && result.dismiss === Swal.DismissReason.timer) {
                        // Timer ran out — force logout
                        window.location.href = LOGOUT_URL;
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = LOGOUT_URL;
                    } else if (result.isConfirmed) {
                        // User clicked keep-in — reset timers
                        resetIdleTimer();
                    }
                });
            }
        }, TIMEOUT_MS - WARN_MS);

        // Hard logout after full timeout
        idleTimer = setTimeout(function () {
            window.location.href = LOGOUT_URL;
        }, TIMEOUT_MS);
    }

    // Track any meaningful user activity
    ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetIdleTimer, { passive: true });
    });

    // Start the timer on page load
    resetIdleTimer();
});

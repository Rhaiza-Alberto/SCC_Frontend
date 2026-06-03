/**
 * theme.js — Dark mode toggle + localStorage persistence
 */
(function() {
    const STORAGE_KEY = 'scc-theme';

    function getPreferred() {
        return localStorage.getItem(STORAGE_KEY) || 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        
        // Update all toggle buttons on the page
        const btns = document.querySelectorAll('#themeToggleBtn');
        btns.forEach(btn => {
            btn.innerHTML = theme === 'dark'
                ? '<i class="bi bi-sun-fill"></i>'
                : '<i class="bi bi-moon-fill"></i>';
            btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        });
    }

    // Apply saved theme immediately
    applyTheme(getPreferred());

    document.addEventListener('DOMContentLoaded', function() {
        let btn = document.getElementById('themeToggleBtn');
        
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'themeToggleBtn';
            btn.className = 'theme-toggle';
            btn.type = 'button';
            
            // Placement Strategy:
            const headerActions = document.querySelector('.scc-main .d-flex.align-items-center.gap-3, #navbarActions');
            if (headerActions) {
                headerActions.prepend(btn);
            } else {
                const sidebarBrand = document.querySelector('.sidebar-brand');
                if (sidebarBrand) {
                    sidebarBrand.appendChild(btn);
                    // Minimalist styling for sidebar placement
                    btn.style.marginTop = "10px";
                    btn.style.width = "32px";
                    btn.style.height = "32px";
                    btn.style.fontSize = "0.9rem";
                } else {
                    // Fallback for pages with neither
                    document.body.appendChild(btn);
                    btn.style.position = 'fixed';
                    btn.style.top = '20px';
                    btn.style.right = '20px';
                    btn.style.zIndex = '1050';
                }
            }
        }

        applyTheme(getPreferred());
        
        // Use event delegation or re-bind if manually added
        document.addEventListener('click', function(e) {
            if (e.target.closest('#themeToggleBtn')) {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            }
        });
    });
})();

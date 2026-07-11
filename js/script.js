// script.js - Client-Side Interactivity, Theme Toggling, and UI Controllers
// Conforms to spec.pdf requirements

// Theme Initialization (executed immediately to prevent color flashes)
(function() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    if (savedTheme === 'light') {
        document.body.classList.remove('dark-theme');
        document.body.classList.add('light-theme');
    } else {
        document.body.classList.remove('light-theme');
        document.body.classList.add('dark-theme');
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 2. Accra Live Clock (Africa/Accra Timezone)
    const clockElement = document.getElementById('accra-time');
    if (clockElement) {
        const updateAccraTime = () => {
            const options = {
                timeZone: 'Africa/Accra',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            const formatter = new Intl.DateTimeFormat('en-US', options);
            clockElement.textContent = formatter.format(new Date());
        };
        updateAccraTime();
        setInterval(updateAccraTime, 1000);
    }

    // 3. Desktop & Mobile Collapsible Sidebar
    const sidebarToggle = document.getElementById('sidebar-toggle'); // Mobile
    const sidebarCollapseBtn = document.getElementById('sidebar-collapse-btn'); // Desktop
    const sidebar = document.getElementById('app-sidebar');
    const layout = document.querySelector('.app-layout');
    
    // Read persisted sidebar state
    if (localStorage.getItem('sidebar-collapsed') === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
        if (layout) layout.classList.add('sidebar-collapsed');
        updateCollapseIcon(true);
    }

    function updateCollapseIcon(isCollapsed) {
        const icon = document.getElementById('collapse-icon');
        if (icon) {
            icon.setAttribute('data-lucide', isCollapsed ? 'chevron-right' : 'chevron-left');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    if (sidebarCollapseBtn && sidebar) {
        sidebarCollapseBtn.addEventListener('click', () => {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            if (layout) layout.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            updateCollapseIcon(isCollapsed);
        });
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            sidebar.classList.toggle('open');
            e.stopPropagation();
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 4. Dark & Light Theme Toggle
    const themeBtn = document.getElementById('theme-toggle-btn');
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    const themeLabel = document.getElementById('theme-label');

    function updateThemeUI(theme) {
        if (theme === 'light') {
            document.body.classList.remove('dark-theme');
            document.body.classList.add('light-theme');
            if (sunIcon) sunIcon.style.display = 'inline-block';
            if (moonIcon) moonIcon.style.display = 'none';
            if (themeLabel) themeLabel.textContent = 'Light';
        } else {
            document.body.classList.remove('light-theme');
            document.body.classList.add('dark-theme');
            if (sunIcon) sunIcon.style.display = 'none';
            if (moonIcon) moonIcon.style.display = 'inline-block';
            if (themeLabel) themeLabel.textContent = 'Dark';
        }
    }

    // Initialize UI elements based on current theme
    const activeTheme = localStorage.getItem('theme') || 'dark';
    updateThemeUI(activeTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const isLight = document.body.classList.contains('light-theme');
            const newTheme = isLight ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        });
    }

    // 5. Custom Deletion Confirmation Dialog Interceptor
    window.confirmDelete = function(formElement, title = "Confirm Deletion", message = "Are you sure you want to delete this record?") {
        const modal = document.getElementById('custom-confirm-modal');
        if (!modal) return true; // Fallback to submit if modal not found

        const titleEl = document.getElementById('confirm-modal-title');
        const msgEl = document.getElementById('confirm-modal-message');
        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;

        modal.classList.add('open');

        const cancelBtn = document.getElementById('confirm-modal-cancel');
        const approveBtn = document.getElementById('confirm-modal-approve');

        const cleanUpListeners = () => {
            modal.classList.remove('open');
            cancelBtn.removeEventListener('click', onCancel);
            approveBtn.removeEventListener('click', onApprove);
        };

        function onCancel() {
            cleanUpListeners();
        }

        function onApprove() {
            cleanUpListeners();
            formElement.submit(); // submit form programmatically
        }

        cancelBtn.addEventListener('click', onCancel);
        approveBtn.addEventListener('click', onApprove);

        return false; // prevent default submit
    };

    // 6. Generic Modal Handler Helper Actions
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('open');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('open');
        }
    };

    // Close modals on clicking overlay background
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('open');
            }
        });
    });

    // 7. Toast Notification Engine
    window.showToast = function(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let iconName = 'info';
        if (type === 'success') iconName = 'check-circle';
        if (type === 'error') iconName = 'alert-triangle';
        if (type === 'warning') iconName = 'alert-circle';

        toast.innerHTML = `
            <i data-lucide="${iconName}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons({
                attrs: { class: 'toast-icon' }
            });
        }

        // Trigger transition slide-in
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Slide-out and remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 4000);
    };

    // Trigger URL parameter status notifications
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msg = urlParams.get('msg');
        const type = urlParams.get('type') || 'success';
        window.showToast(msg, type);
        
        // Clean only 'msg' and 'type' query parameters from URL history without reloading
        urlParams.delete('msg');
        urlParams.delete('type');
        const newSearch = urlParams.toString();
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (newSearch ? '?' + newSearch : '');
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
});

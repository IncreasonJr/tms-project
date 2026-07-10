// script.js - Client-Side Interactivity and UI Controllers
// Conforms to spec.pdf requirements

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

    // 3. Collapsible Sidebar Drawer (Mobile/Tablet Viewports)
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            sidebar.classList.toggle('open');
            e.stopPropagation();
        });

        // Close sidebar when clicking outside of it on mobile
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 4. Modal Handler Helper Actions
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

    // 5. Toast Toast Notification Engine
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

    // Trigger URL parameter status notifications (e.g. success messages after redirections)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msg = urlParams.get('msg');
        const type = urlParams.get('type') || 'success';
        window.showToast(msg, type);
        
        // Clean query parameters from URL history without reloading
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
});

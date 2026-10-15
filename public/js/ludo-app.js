/**
 * FSALUDOCLUB TOURNAMENT PLATFORM — CLIENT APPLICATION LOGIC
 * Handles Theme Toggling, Drawer Slideout, Clipboard Copy, and Interactive Dialogs
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Dark Mode Persistence & Initialization
    const currentTheme = localStorage.getItem('ludo_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);

    const themeToggleBtns = document.querySelectorAll('.js-ludo-theme-toggle');
    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const activeTheme = document.documentElement.getAttribute('data-theme');
            const nextTheme = activeTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('ludo_theme', nextTheme);
            showLudoToast(`Switched to ${nextTheme === 'dark' ? 'Dark' : 'Light'} Mode`);
        });
    });

    // 2. Navigation Drawer Logic
    const drawer = document.getElementById('ludoDrawer');
    const backdrop = document.getElementById('ludoDrawerBackdrop');
    const hamburgerBtn = document.getElementById('ludoHamburgerBtn');
    const closeBtn = document.getElementById('ludoDrawerCloseBtn');

    function openDrawer() {
        if (drawer && backdrop) {
            drawer.classList.add('open');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (drawer && backdrop) {
            drawer.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);

    // 3. Referral Code Copy to Clipboard
    const copyBtns = document.querySelectorAll('.js-copy-code');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                const textToCopy = targetEl.innerText || targetEl.value;
                navigator.clipboard.writeText(textToCopy.trim()).then(() => {
                    showLudoToast('Referral code copied to clipboard! 📋');
                }).catch(() => {
                    showLudoToast('Failed to copy code. Please copy manually.');
                });
            }
        });
    });

    // 4. Toast Notification Utility
    window.showLudoToast = function (message) {
        let toast = document.getElementById('ludoToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'ludoToast';
            toast.className = 'ludo-toast';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<span>✨</span> <span>${message}</span>`;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2800);
    };

    // 5. Install App (PWA prompt placeholder)
    const installBtns = document.querySelectorAll('.js-install-app');
    installBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            showLudoToast('To install, tap browser menu and choose "Add to Home Screen"');
        });
    });

    // 6. Ensure Bootstrap modals are attached directly to document.body
    // This eliminates local stacking context traps, keeping backdrops strictly behind modal content
    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('modal')) {
            if (e.target.parentElement !== document.body) {
                document.body.appendChild(e.target);
            }
        }
    });

    // 7. Signature Curly Bottom Navigation Interactive Feedback
    const bottomNavItems = document.querySelectorAll('.ludo-bottom-nav .ludo-nav-item');
    bottomNavItems.forEach(item => {
        item.addEventListener('click', function () {
            bottomNavItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // 8. Global Fetch 401 Unauthenticated Interceptor
    // Smoothly redirects to login without showing raw "Unauthenticated." error text
    if (window.fetch) {
        const nativeFetch = window.fetch;
        window.fetch = async function (...args) {
            const response = await nativeFetch.apply(this, args);
            if (response.status === 401) {
                try {
                    const clone = response.clone();
                    const data = await clone.json();
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                        return response;
                    }
                } catch (e) {}
                window.location.href = '/showlogin';
            }
            return response;
        };
    }
});

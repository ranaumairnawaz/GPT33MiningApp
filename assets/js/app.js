// PWA Installation Handler
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Show install buttons if any exist
    document.querySelectorAll('.pwa-install-btn').forEach(btn => btn.style.display = 'flex');
});

window.installApp = async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    if (outcome === 'accepted') {
        deferredPrompt = null;
        document.querySelectorAll('.pwa-install-btn').forEach(btn => btn.style.display = 'none');
    }
};

const initApp = () => {

    // SPA Router
    const rootPath = '/';
    window.navigateTo = url => {
        if (!url.startsWith('/') && !url.startsWith('http')) {
            url = rootPath + url;
        }
        history.pushState(null, null, url);
        window.router();
    };

    window.router = async () => {
        try {
            const path = window.location.pathname;
            const rootPath = '/';
            let route = path.replace(rootPath, '');

            // Handle admin prefix
            const isAdmin = route.startsWith('admin');
            if (isAdmin) {
                route = route.replace('admin/', '') || 'dashboard';
            } else {
                route = route || 'home';
            }

            // Update body classes for shell visibility
            document.body.classList.forEach(cls => {
                if (cls.startsWith('route-')) document.body.classList.remove(cls);
            });
            document.body.classList.add('route-' + route);

            const appContent = document.getElementById('app-content');
            const preloader = document.getElementById('global-preloader');
            let preloaderShown = false;
            let preloaderTimeout = null;

            // Only show preloader if the fetch takes longer than 150ms (prevents annoying flickers on fast tabs)
            if (preloader) {
                preloaderTimeout = setTimeout(() => {
                    preloaderShown = true;
                    appContent.style.opacity = '0.5';
                    preloader.style.opacity = '0';
                    preloader.style.display = 'flex';
                    preloader.offsetHeight; // trigger reflow
                    preloader.style.opacity = '1';
                }, 150);
            }

            // Always append ajax=1 to ensure server returns view only
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', '1');
            url.searchParams.set('_', Date.now()); // Cache buster

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (preloaderTimeout) clearTimeout(preloaderTimeout);

            if (!response.ok) throw new Error('HTTP ' + response.status);

            const html = await response.text();

            // Check for JSON redirect
            try {
                if (html.startsWith('{')) {
                    const data = JSON.parse(html);
                    if (data.redirect) {
                        if (data.redirect.includes('login')) {
                            document.body.classList.remove('is-logged-in');
                            document.body.classList.add('is-logged-out');
                        }
                        if (preloader) preloader.style.display = 'none';
                        navigateTo(data.redirect);
                        return;
                    }
                }
            } catch (e) { }

            // Prevent nesting: if response contains the shell, don't inject it
            if (html.includes('id="app"')) {
                console.error('Nesting detected! Server returned full shell.');
                window.location.reload(); // Full reload to recover
                return;
            }

            appContent.innerHTML = html;
            appContent.style.opacity = '1';

            // Re-run scripts
            const scripts = appContent.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update Header Title
            updateHeader(route);

            initLiveCounter();
            updateActiveNav();

            if (preloader) {
                if (preloaderShown || preloader.style.display === 'flex') {
                    preloader.style.opacity = '0';
                    setTimeout(() => {
                        preloader.style.display = 'none';
                        preloader.style.opacity = '1';
                    }, 150); // Snappy fade-out matched with 150ms CSS transition
                } else {
                    preloader.style.display = 'none';
                }
            }
        } catch (error) {
            if (typeof preloaderTimeout !== 'undefined' && preloaderTimeout) clearTimeout(preloaderTimeout);
            const preloader = document.getElementById('global-preloader');
            if (preloader) preloader.style.display = 'none';
            console.error('Routing error:', error);
            appContent.innerHTML = `
                <div class="card" style="text-align: center; padding: 40px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 3rem; color: #ef4444; margin-bottom: 20px;"></i>
                    <h2>Connection Error</h2>
                    <p style="color: var(--text-muted);">We couldn't load the page. Please check your internet connection.</p>
                    <button class="btn" onclick="window.router()" style="margin-top: 20px; width: auto; padding: 10px 30px;">Retry</button>
                </div>
            `;
            appContent.style.opacity = '1';
        }
    };

    const updateHeader = (route) => {
        const headerTitle = document.querySelector('.logo');
        const headerActions = document.querySelector('.header-actions');
        if (!headerTitle) return;

        if (route === 'dashboard' || route === 'home') {
            headerTitle.innerHTML = '<img src="/assets/img/logo.png" style="height: 28px; width: auto; border-radius: 4px; margin-right: 8px;"> GPT33';
            if (headerActions) headerActions.style.display = 'flex';
        } else {
            const titleMap = {
                'deposit': 'Deposit',
                'withdraw': 'Withdraw',
                'invest': 'AI Plans',
                'investments': 'My Investments',
                'referrals': 'Referrals',
                'transactions': 'Transactions',
                'profile': 'My Profile',
                'menu': 'Account',
                'support': 'Support'
            };
            const pageTitle = titleMap[route] || 'GPT33';
            headerTitle.innerHTML = `<a href="javascript:history.back()" style="color: var(--text-main); margin-right: 15px; font-size: 1rem;"><i class="fa-solid fa-arrow-left"></i></a> ${pageTitle}`;
            if (headerActions) headerActions.style.display = 'none';
        }
    };

    // Intercept clicks
    window.addEventListener('popstate', router);

    document.body.addEventListener('click', e => {
        if (e.target.matches('[data-link]') || e.target.closest('[data-link]')) {
            e.preventDefault();
            const link = e.target.matches('[data-link]') ? e.target : e.target.closest('[data-link]');
            navigateTo(link.href);
        }
    });

    // Update bottom nav active state
    const updateActiveNav = () => {
        const path = window.location.pathname;
        const navItems = document.querySelectorAll('.nav-item, .nav-link');
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });
    };

    // --- Live Earning Counter Logic ---
    let counterInterval;
    const initLiveCounter = () => {
        const counterEl = document.querySelector('.live-counter');
        if (!counterEl) return;

        // Data from data attributes (populated by PHP)
        let balance = parseFloat(counterEl.dataset.balance) || 0;
        const hourlyRate = parseFloat(counterEl.dataset.hourlyRate) || 0;
        const investedAmount = parseFloat(counterEl.dataset.invested) || 0;

        if (investedAmount === 0 || hourlyRate === 0) return;

        // Calculate increment per millisecond
        // Profit per hour = investedAmount * (hourlyRate / 100)
        // Profit per ms = (Profit per hour) / (60 * 60 * 1000)
        const profitPerHour = investedAmount * (hourlyRate / 100);
        const incrementPerMs = profitPerHour / 3600000;

        if (counterInterval) clearInterval(counterInterval);

        const startTime = Date.now();
        const initialBalance = balance;

        counterInterval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const currentBalance = initialBalance + (incrementPerMs * elapsed);
            counterEl.textContent = '$' + currentBalance.toFixed(8);
        }, 50); // Update every 50ms for smooth animation
    };

    // Initial load
    initLiveCounter();
    updateActiveNav();

    // --- Global Form Delegation ---
    document.body.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form.hasAttribute('data-ajax-form')) return;

        e.preventDefault();
        // Priority: data-action attribute, then action attribute, then current URL
        const apiEndpoint = form.getAttribute('data-action') || form.getAttribute('action') || window.location.href;

        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.oldHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
        }

        let data;
        try {
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Invalid JSON response:', text);
                showToast('Server returned an invalid response', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.oldHtml;
                }
                return;
            }

            if (data.status === 'success') {
                if (data.message) showToast(data.message, 'success');

                // Handle redirects or navigation
                if (data.redirect) {
                    if (form.id === 'login-form' || form.id === 'register-form') {
                        document.body.classList.remove('is-logged-out');
                        document.body.classList.add('is-logged-in');
                        window.location.href = data.redirect;
                    } else {
                        navigateTo(data.redirect);
                    }
                } else if (form.dataset.callback) {
                    // Execute specific callback if defined in a data attribute
                    window[form.dataset.callback](data);
                } else {
                    // Default behavior for some forms
                    if (form.id === 'login-form' || form.id === 'register-form') {
                        window.location.href = rootPath + 'dashboard';
                    }
                }
            } else {
                showToast(data.message || 'Action failed', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            showToast('Connection error. Please try again.', 'error');
        } finally {
            // Re-enable button unless we are redirecting away
            const isRedirecting = data && data.status === 'success' && (data.redirect || form.id === 'login-form' || form.id === 'register-form');
            if (submitBtn && !isRedirecting) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.oldHtml;
            }
        }
    });

    // --- User Notifications Logic ---
    const fetchUserNotifications = async () => {
        if (!document.body.classList.contains('is-logged-in')) return;
        try {
            // Update Badge Count
            const countResp = await fetch('/api/notifications?action=get_count');
            const countData = await countResp.json();
            const badge = document.getElementById('notif-badge');
            if (badge) {
                if (countData.status === 'success' && countData.count > 0) {
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Get Unread for Toast/Push
            const resp = await fetch('/api/notifications?action=get_unread');
            const data = await resp.json();
            if (data.status === 'success' && data.notifications.length > 0) {
                data.notifications.forEach(notif => {
                    showToast(notif.message, 'info');
                    if (window.Notification && Notification.permission === 'granted') {
                        new Notification('GPT33 Intelligence Alert', {
                            body: notif.message,
                            icon: '/assets/img/logo.png'
                        });
                    }
                });

                // If on support page, mark chat notifications as read
                const path = window.location.pathname;
                if (path.includes('support')) {
                    fetch('/api/notifications?action=mark_read&type=chat');
                }
            }
        } catch (e) { }
    };

    // Request Notification permission
    if (window.Notification && Notification.permission !== 'granted') {
        Notification.requestPermission();
    }

    // Initial fetch and polling
    setInterval(fetchUserNotifications, 15000);
    fetchUserNotifications();

};

if (document.readyState !== 'loading') {
    initApp();
} else {
    document.addEventListener('DOMContentLoaded', initApp);
}

// Toast Notification System
const showToast = (message, type = 'success') => {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

    toast.innerHTML = `
        <i class="fa-solid ${icon}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

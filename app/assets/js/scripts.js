/**
 * Service Worker registration for PWA functionality
 * Registers a service worker to enable offline capabilities and PWA features
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(() => {
                // Service Worker registered successfully
            })
            .catch(() => {
                // Service Worker registration failed
            });
    });
}

/**
 * Header toggle menus
 */
document.addEventListener('DOMContentLoaded', function () {
    // Remove toasty elements when clicked
    document.addEventListener('click', (e) => {
        const toastyElement = e.target.closest('.toasty');
        if (toastyElement) {
            toastyElement.remove();
        }
    });

    // Toggle header open class when open-nav is clicked
    document.addEventListener('click', (e) => {
        const openNavElement = e.target.closest('.open-nav');
        if (openNavElement) {
            const header = document.querySelector('header');
            if (header.classList.contains('open')) {
                header.classList.remove('open');
            } else {
                header.classList.add('open');
            }
        }
    });

    // Paste button functionality
    const pasteButton = document.getElementById('paste');
    const urlInput = document.getElementById('url');

    if (pasteButton && urlInput) {
        pasteButton.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const clipboardText = await navigator.clipboard.readText();
                urlInput.value = clipboardText.trim();
            } catch (err) {
                console.error('Failed to read clipboard contents', err);
            }
        });
    }

    // Dark mode 
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    // Form submission - encode URL to preserve protocol slashes
    const urlForm = document.getElementById('urlForm');
    if (urlForm) {
        urlForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const url = urlInput.value.trim();
            if (url) {
                window.location.href = '/p/' + encodeURIComponent(url);
            }
        });
    }
});
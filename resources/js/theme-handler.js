/**
 * Flutter App Theme Handler
 * Handles theme synchronization between Flutter app and Laravel web app
 */

(function() {
    'use strict';

    const THEME_STORAGE_KEY = 'userThemePreference';
    const SYSTEM_THEME_KEY = 'systemTheme';

    /**
     * Apply theme to the document
     */
    function applyTheme(theme) {
        const html = document.documentElement;

        if (theme === 'dark') {
            html.classList.add('dark');
            html.style.colorScheme = 'dark';
        } else {
            html.classList.remove('dark');
            html.style.colorScheme = 'light';
        }

        console.log('Theme applied:', theme);
    }

    /**
     * Get theme from GET parameter
     */
    function getThemeFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const appTheme = urlParams.get('app_theme');

        if (appTheme === 'dark' || appTheme === 'light') {
            // Save system theme for future reference
            sessionStorage.setItem(SYSTEM_THEME_KEY, appTheme);
            return appTheme;
        }

        // Try to get from session storage
        return sessionStorage.getItem(SYSTEM_THEME_KEY);
    }

    /**
     * Get saved user preference
     */
    function getSavedTheme() {
        return localStorage.getItem(THEME_STORAGE_KEY);
    }

    /**
     * Save user theme preference
     */
    function saveThemePreference(theme) {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
        console.log('Theme preference saved:', theme);
    }

    /**
     * Initialize theme on page load
     */
    function initializeTheme() {
        // Priority: User preference > System theme from app > Browser default
        const savedTheme = getSavedTheme();
        const systemTheme = getThemeFromUrl();
        const browserTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

        const theme = savedTheme || systemTheme || browserTheme;

        console.log('Theme initialization:', {
            savedTheme,
            systemTheme,
            browserTheme,
            finalTheme: theme
        });

        applyTheme(theme);
    }

    /**
     * Listen for theme events from Flutter app
     */
    function listenForFlutterTheme() {
        window.addEventListener('themeModeReady', (event) => {
            const systemTheme = event.detail.themeMode;
            sessionStorage.setItem(SYSTEM_THEME_KEY, systemTheme);

            // Only apply if user hasn't set a preference
            if (!getSavedTheme()) {
                applyTheme(systemTheme);
            }

            console.log('Flutter theme event received:', systemTheme);
        });
    }

    /**
     * Expose theme toggle function for UI
     */
    window.toggleTheme = function() {
        const html = document.documentElement;
        const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        applyTheme(newTheme);
        saveThemePreference(newTheme);

        return newTheme;
    };

    /**
     * Get current theme
     */
    window.getCurrentTheme = function() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    };

    /**
     * Reset to system theme
     */
    window.resetToSystemTheme = function() {
        localStorage.removeItem(THEME_STORAGE_KEY);
        const systemTheme = getThemeFromUrl() || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        applyTheme(systemTheme);
        console.log('Reset to system theme:', systemTheme);
    };

    // Initialize immediately to prevent flash
    initializeTheme();

    // Listen for Flutter events when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', listenForFlutterTheme);
    } else {
        listenForFlutterTheme();
    }

    console.log('Theme handler initialized');
})();

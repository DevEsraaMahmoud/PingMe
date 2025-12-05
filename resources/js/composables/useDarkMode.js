import { ref } from 'vue';

// Shared state across all components
const isDark = ref(false);
let mediaQuery = null;
let handleChange = null;

// Load dark mode preference from localStorage
const loadDarkMode = () => {
    if (typeof window === 'undefined') return;
    
    const saved = localStorage.getItem('darkMode');
    if (saved !== null) {
        isDark.value = saved === 'true';
    } else {
        // Check system preference
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyDarkMode();
};

// Apply dark mode to document
const applyDarkMode = () => {
    if (typeof document === 'undefined') return;
    
    const html = document.documentElement;
    if (isDark.value) {
        html.classList.add('dark');
        html.style.colorScheme = 'dark';
    } else {
        html.classList.remove('dark');
        html.style.colorScheme = 'light';
    }
};

// Toggle dark mode
const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('darkMode', isDark.value.toString());
    }
    applyDarkMode();
};

// Initialize dark mode immediately when module loads
if (typeof window !== 'undefined') {
    loadDarkMode();
}

export function useDarkMode() {
    // Setup system preference watcher
    const setupSystemPreferenceWatcher = () => {
        if (typeof window === 'undefined') return () => {};
        
        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        handleChange = (e) => {
            // Only auto-switch if user hasn't manually set a preference
            if (localStorage.getItem('darkMode') === null) {
                isDark.value = e.matches;
                applyDarkMode();
            }
        };
        mediaQuery.addEventListener('change', handleChange);
        
        // Return cleanup function
        return () => {
            if (mediaQuery && handleChange) {
                mediaQuery.removeEventListener('change', handleChange);
            }
        };
    };

    return {
        isDark,
        toggleDarkMode,
        setupSystemPreferenceWatcher,
    };
}


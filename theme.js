// theme.js - Dark/Light Mode Toggle

// Function to apply theme
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    var btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.textContent = theme === 'dark' ? '☀️' : '🌙';
        btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }
    
    console.log('Theme applied:', theme);
}

// Toggle theme function
function toggleTheme() {
    var html = document.documentElement;
    var currentTheme = html.getAttribute('data-theme') || 'dark';
    var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(newTheme);
}

// Load saved theme on page load
document.addEventListener('DOMContentLoaded', function() {
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        applyTheme('dark');
    }
});

// Also run immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        var savedTheme = localStorage.getItem('theme');
        applyTheme(savedTheme || 'dark');
    });
} else {
    var savedTheme = localStorage.getItem('theme');
    applyTheme(savedTheme || 'dark');
}
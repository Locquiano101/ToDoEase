<!DOCTYPE html>
<html lang="en" class="<?= ($_COOKIE['theme'] ?? '') === 'dark' ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8" />
  <title>To Do Ease</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            // Custom Dark Mode Palette
            dark: {
              bg: '#0f172a',        // Main background - deep navy
              surface: '#1e293b',   // Cards/surfaces - slate
              elevated: '#334155',  // Elevated elements - lighter slate
              border: '#475569',    // Borders - medium slate
              
              text: {
                primary: '#f1f5f9',   // Main text - near white
                secondary: '#cbd5e1', // Secondary text - light slate
                muted: '#94a3b8',     // Muted text - medium slate
              },
              
              accent: {
                blue: '#3b82f6',      // Primary blue
                purple: '#8b5cf6',    // Secondary purple
                green: '#10b981',     // Success green
                red: '#ef4444',       // Error/overdue red
                yellow: '#f59e0b',    // Warning yellow
              }
            }
          }
        }
      }
    };
  </script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Enhanced Theme Toggle Script -->
  <script>
    function toggleTheme() {
      const html = document.documentElement;
      const isDark = html.classList.toggle('dark');
      document.cookie = "theme=" + (isDark ? "dark" : "light") + "; path=/; max-age=31536000";
      
      // Smooth transition effect
      html.style.transition = 'background-color 0.3s ease';
    }
    
    // Initialize theme on load
    document.addEventListener('DOMContentLoaded', () => {
      const theme = document.cookie.split('; ').find(row => row.startsWith('theme='));
      if (theme && theme.split('=')[1] === 'dark') {
        document.documentElement.classList.add('dark');
      }
    });
  </script>

  <style>
    /* Smooth transitions for theme switching */
    * {
      transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }
    
    /* Custom scrollbar for dark mode */
    ::-webkit-scrollbar {
      width: 12px;
    }
    
    ::-webkit-scrollbar-track {
      @apply bg-gray-100 dark:bg-dark-bg;
    }
    
    ::-webkit-scrollbar-thumb {
      @apply bg-gray-300 dark:bg-dark-elevated rounded-full;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      @apply bg-gray-400 dark:bg-dark-border;
    }
  </style>
</head>

<body class="bg-gray-100 dark:bg-dark-bg text-gray-900 dark:text-dark-text-primary min-h-screen transition-colors duration-300">
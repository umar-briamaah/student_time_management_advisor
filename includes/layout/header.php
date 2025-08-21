<?php 
if (session_status() === PHP_SESSION_NONE) session_start(); 
require_once __DIR__ . '/../functions.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Time Management Advisor</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="icon" href="data:,">
    <meta name="description" content="Manage your academic tasks, track progress, and stay motivated with our student time management system">
    <meta name="theme-color" content="#3b82f6">
    
    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Custom CSS for enhanced UI -->
    <style>
      /* Smooth scrolling */
      html { scroll-behavior: smooth; }
      
      /* Custom scrollbar */
      ::-webkit-scrollbar { width: 8px; }
      ::-webkit-scrollbar-track { background: #f1f5f9; }
      ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
      ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
      
      /* Focus styles for accessibility */
      .focus-ring:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
      }
      
      /* Loading animation */
      .loading { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
      @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
      
      /* Hover effects */
      .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
      .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    </style>
  </head>
  <body class="bg-gray-50 text-gray-900 antialiased">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo/Brand -->
          <div class="flex items-center">
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="flex items-center space-x-2 focus-ring rounded-lg p-2">
              <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">S</span>
              </div>
              <span class="font-bold text-xl text-gray-900 hidden sm:block">Student Time Advisor</span>
              <span class="font-bold text-xl text-gray-900 sm:hidden">STA</span>
            </a>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-1">
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
              </svg>
              Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/tasks.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              Tasks
            </a>
            <a href="<?php echo APP_URL; ?>/calendar.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Calendar
            </a>
            <a href="<?php echo APP_URL; ?>/motivation.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
              Motivation
            </a>
            <a href="<?php echo APP_URL; ?>/reports.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              Reports
            </a>
            <a href="<?php echo APP_URL; ?>/icon_test.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2m-6 0h6m-6 0a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2"></path>
              </svg>
              Icon Test
            </a>
            <a href="<?php echo APP_URL; ?>/demo.php" class="nav-link">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
              </svg>
              Demo
            </a>
          </div>

          <!-- User Menu / Auth -->
          <div class="flex items-center space-x-4">
            <?php if(isset($_SESSION['user'])): ?>
              <!-- User dropdown -->
              <div class="relative" id="userDropdown">
                <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 text-sm rounded-full focus-ring p-2 hover:bg-gray-100 transition-colors">
                  <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-medium text-sm"><?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?></span>
                  </div>
                  <span class="hidden sm:block text-gray-700"><?php echo h($_SESSION['user']['name'] ?? 'User'); ?></span>
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                <!-- Dropdown menu -->
                <div id="userDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden">
                  <a href="<?php echo APP_URL; ?>/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                  <a href="<?php echo APP_URL; ?>/tasks.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Tasks</a>
                  <a href="<?php echo APP_URL; ?>/motivation.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Badges</a>
                  <div class="border-t border-gray-100"></div>
                  <a href="<?php echo APP_URL; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign Out</a>
                </div>
              </div>
            <?php else: ?>
              <a href="<?php echo APP_URL; ?>/login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium focus-ring">
                Sign In
              </a>
            <?php endif; ?>
          </div>

          <!-- Mobile menu button -->
          <div class="md:hidden">
            <button onclick="toggleMobileMenu()" class="text-gray-500 hover:text-gray-700 focus-ring p-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <div id="mobileMenu" class="md:hidden border-t border-gray-200 bg-white hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <a href="<?php echo APP_URL; ?>/dashboard.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
            </svg>
            Dashboard
          </a>
          <a href="<?php echo APP_URL; ?>/tasks.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Tasks
          </a>
          <a href="<?php echo APP_URL; ?>/calendar.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Calendar
          </a>
          <a href="<?php echo APP_URL; ?>/motivation.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Motivation
          </a>
          <a href="<?php echo APP_URL; ?>/reports.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Reports
          </a>
          <a href="<?php echo APP_URL; ?>/icon_test.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2m-6 0h6m-6 0a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2"></path>
            </svg>
            Icon Test
          </a>
          <a href="<?php echo APP_URL; ?>/demo.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            Demo
          </a>
          <?php if(isset($_SESSION['user'])): ?>
            <div class="border-t border-gray-200 pt-2">
              <a href="<?php echo APP_URL; ?>/logout.php" class="mobile-nav-link text-red-600">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Sign Out
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
      
      <!-- Custom CSS for navigation -->
      <style>
        .nav-link {
          @apply flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 transition-colors focus-ring;
        }
        
        .mobile-nav-link {
          @apply flex items-center px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 transition-colors;
        }
        
        .nav-link.active, .mobile-nav-link.active {
          @apply bg-blue-50 text-blue-700;
        }
      </style>

      <!-- JavaScript functions for navigation -->
      <script>
        function toggleUserDropdown() {
          const dropdown = document.getElementById('userDropdownMenu');
          dropdown.classList.toggle('hidden');
        }

        function toggleMobileMenu() {
          const menu = document.getElementById('mobileMenu');
          menu.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
          const userDropdown = document.getElementById('userDropdown');
          const mobileMenu = document.getElementById('mobileMenu');
          
          if (!userDropdown.contains(event.target)) {
            document.getElementById('userDropdownMenu').classList.add('hidden');
          }
          
          if (!event.target.closest('#mobileMenu') && !event.target.closest('button[onclick="toggleMobileMenu()"]')) {
            mobileMenu.classList.add('hidden');
          }
        });
      </script>
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
    <!-- Top Bar -->
    <div class="bg-gradient-to-r from-green-700 to-green-600 h-2"></div>
    
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
          <!-- Logo/Brand -->
          <div class="flex items-center">
            <a href="<?php echo APP_URL; ?>/dashboard.php" class="flex items-center space-x-3 focus-ring rounded-lg p-2 hover:bg-gray-50 transition-colors">
              <div class="w-12 h-12 bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                <span class="text-white font-bold text-xl">S</span>
              </div>
              <div class="hidden sm:block">
                <div class="flex flex-col">
                  <span class="font-bold text-2xl text-gray-900 leading-tight">Student Time</span>
                  <span class="font-bold text-2xl text-gray-900 leading-tight">Advisor</span>
                </div>
              </div>
              <div class="sm:hidden">
                <span class="font-bold text-xl text-gray-900">STA</span>
              </div>
            </a>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-2">
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Reports
          </a>
          <a href="<?php echo APP_URL; ?>/email_test.php" class="nav-link">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email Test
          </a>

          </div>

          <!-- User Menu / Auth -->
          <div class="flex items-center space-x-4">
            <?php if(isset($_SESSION['user'])): ?>
              <!-- User dropdown -->
              <div class="relative" id="userDropdown">
                <button onclick="toggleUserDropdown()" class="flex items-center space-x-3 text-sm rounded-xl focus-ring p-3 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-300 border border-transparent hover:border-gray-200">
                  <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-white font-semibold text-sm"><?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?></span>
                  </div>
                  <span class="hidden sm:block text-gray-700 font-medium"><?php echo h($_SESSION['user']['name'] ?? 'User'); ?></span>
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                <!-- Dropdown menu -->
                <div id="userDropdownMenu" class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl py-2 z-50 hidden border border-gray-100">
                  <a href="<?php echo APP_URL; ?>/dashboard.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-200">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    Dashboard
                  </a>
                  <a href="<?php echo APP_URL; ?>/tasks.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-200">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    My Tasks
                  </a>
                  <a href="<?php echo APP_URL; ?>/motivation.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-200">
                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                    My Badges
                  </a>
                  <div class="border-t border-gray-100 my-2"></div>
                  <a href="<?php echo APP_URL; ?>/logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200">
                    <svg class="w-4 h-4 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Sign Out
                  </a>
                </div>
              </div>
            <?php else: ?>
              <a href="<?php echo APP_URL; ?>/login.php" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all duration-300 font-semibold focus-ring shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                Sign In
              </a>
            <?php endif; ?>
          </div>

          <!-- Mobile menu button -->
          <div class="md:hidden">
            <button onclick="toggleMobileMenu()" class="text-gray-600 hover:text-gray-800 focus-ring p-3 rounded-xl hover:bg-gray-100 transition-all duration-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation -->
      <div id="mobileMenu" class="md:hidden border-t border-gray-200 bg-white hidden shadow-lg">
        <div class="px-4 pt-4 pb-6 space-y-2">
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
          <a href="<?php echo APP_URL; ?>/email_test.php" class="mobile-nav-link">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email Test
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
          @apply flex items-center px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-300 focus-ring border border-transparent hover:border-gray-200;
        }
        
        .mobile-nav-link {
          @apply flex items-center px-4 py-3 rounded-xl text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-300 border border-transparent hover:border-gray-200;
        }
        
        .nav-link.active, .mobile-nav-link.active {
          @apply bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 border-blue-200;
        }
        
        .nav-link:hover, .mobile-nav-link:hover {
          transform: translateY(-1px);
        }
        
        /* Enhanced focus styles */
        .focus-ring:focus {
          outline: 2px solid #3b82f6;
          outline-offset: 2px;
          box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        /* Smooth transitions for all interactive elements */
        * {
          transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
          transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
          transition-duration: 150ms;
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
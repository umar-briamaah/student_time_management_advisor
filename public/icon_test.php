<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8">Icon Sizing Test</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Test different icon sizes -->
        <div class="bg-white p-6 rounded-xl shadow-lg border">
            <h3 class="text-lg font-semibold mb-4">Small Icons (w-6 h-6)</h3>
            <div class="flex items-center space-x-4">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Add Task</span>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg border">
            <h3 class="text-lg font-semibold mb-4">Medium Icons (w-8 h-8)</h3>
            <div class="flex items-center space-x-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Completed</span>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg border">
            <h3 class="text-lg font-semibold mb-4">Large Icons (w-12 h-12)</h3>
            <div class="flex items-center space-x-4">
                <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span>Reports</span>
            </div>
        </div>
        
        <!-- Test stats cards with background icons -->
        <div class="stats-card bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-lg border border-blue-200">
            <h3 class="text-lg font-semibold mb-4">Stats Card Test</h3>
            <div class="flex items-center justify-between">
                <div class="flex items-center icon-foreground">
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-600 font-medium">Test Tasks</p>
                        <p class="text-2xl font-bold text-blue-800">25</p>
                    </div>
                </div>
                <div class="icon-background text-blue-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Test decorative elements -->
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-6 text-white relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white bg-opacity-10 rounded-full -ml-8 -mb-8"></div>
                <div class="absolute top-1/2 right-1/4 w-12 h-12 bg-white bg-opacity-5 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <h3 class="text-lg font-semibold mb-4">Decorative Elements Test</h3>
                <p>This tests the background decorative circles</p>
            </div>
        </div>
        
        <!-- Test icon containers -->
        <div class="bg-white p-6 rounded-xl shadow-lg border">
            <h3 class="text-lg font-semibold mb-4">Icon Container Test</h3>
            <div class="icon-container w-16 h-16 bg-gray-100 rounded-lg">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="mt-8 text-center">
        <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>

/**
 * Student Time Management Advisor - Main JavaScript Application
 * Provides enhanced functionality, UI interactions, and performance optimizations
 */

(function() {
    'use strict';

    // Main Application Class
    class StudentTimeAdvisor {
        constructor() {
            this.state = {
                isLoading: false,
                currentTheme: 'light',
                notifications: [],
                modals: new Map()
            };
            this.init();
        }

        init() {
            // Ensure state is properly initialized
            if (!this.state) {
                this.state = {
                    isLoading: false,
                    currentTheme: 'light',
                    notifications: [],
                    modals: new Map()
                };
            }
            
            this.setupEventListeners();
            this.initializeComponents();
            this.setupPerformanceMonitoring();
            this.setupThemeDetection();
            this.setupIntersectionObserver();
        }

        setupEventListeners() {
            document.addEventListener('DOMContentLoaded', this.onDOMReady.bind(this));
            window.addEventListener('resize', this.debounce(this.onResize.bind(this), 250));
            document.addEventListener('submit', this.handleFormSubmit.bind(this));
            document.addEventListener('click', this.handleModalClicks.bind(this));
        }

        // Utility method for debouncing
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        initializeComponents() {
            this.initializeModals();
            this.initializeTooltips();
            this.initializeNotifications();
            this.initializeLazyLoading();
            this.initializeSearch();
        }

        // Modal Management
        initializeModals() {
            // Initialize modals in state if not already done
            if (!this.state.modals) {
                this.state.modals = new Map();
            }
            
            document.querySelectorAll('[data-modal]').forEach(trigger => {
                const modalId = trigger.dataset.modal;
                const modal = document.getElementById(modalId);
                
                if (modal) {
                    this.state.modals.set(modalId, { element: modal, trigger: trigger });
                    trigger.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.openModal(modalId);
                    });
                }
            });
        }

        openModal(modalId) {
            const modalData = this.state.modals.get(modalId);
            if (!modalData) return;

            const modal = modalData.element;
            modal.classList.add('show');
            
            // Focus management
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        }

        closeModal(modalId) {
            const modalData = this.state.modals.get(modalId);
            if (!modalData) return;

            const modal = modalData.element;
            modal.classList.remove('show');
            
            if (modalData.trigger) {
                modalData.trigger.focus();
            }
        }

        // Tooltip System
        initializeTooltips() {
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                const tooltip = this.createTooltip(element.dataset.tooltip);
                
                element.addEventListener('mouseenter', () => this.showTooltip(tooltip, element));
                element.addEventListener('mouseleave', () => this.hideTooltip(tooltip));
            });
        }

        createTooltip(text) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-text';
            tooltip.textContent = text;
            tooltip.style.cssText = 'position: absolute; z-index: 1000; display: none;';
            document.body.appendChild(tooltip);
            return tooltip;
        }

        showTooltip(tooltip, element) {
            const rect = element.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            tooltip.style.display = 'block';
        }

        hideTooltip(tooltip) {
            tooltip.style.display = 'none';
        }

        // Notification System
        initializeNotifications() {
            this.notificationContainer = document.createElement('div');
            this.notificationContainer.className = 'notification-container';
            this.notificationContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            document.body.appendChild(this.notificationContainer);
        }

        showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} notification fade-in`;
            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <span>${message}</span>
                    <button class="ml-auto" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            this.notificationContainer.appendChild(notification);
            
            if (duration > 0) {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, duration);
            }
        }

        // Lazy Loading
        initializeLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }

        // Search Functionality
        initializeSearch() {
            document.querySelectorAll('.search-input').forEach(input => {
                input.addEventListener('input', this.debounce((e) => {
                    this.performSearch(e.target.value, e.target.dataset.searchTarget);
                }, 300));
            });
        }

        performSearch(query, target) {
            if (!query.trim()) {
                this.showAllResults(target);
                return;
            }

            const searchableElements = document.querySelectorAll(target);
            const queryLower = query.toLowerCase();

            searchableElements.forEach(element => {
                const text = element.textContent.toLowerCase();
                const isMatch = text.includes(queryLower);
                element.style.display = isMatch ? '' : 'none';
                
                if (isMatch) {
                    element.classList.add('search-highlight');
                } else {
                    element.classList.remove('search-highlight');
                }
            });
        }

        showAllResults(target) {
            document.querySelectorAll(target).forEach(element => {
                element.style.display = '';
                element.classList.remove('search-highlight');
            });
        }

        // Form Handling
        handleFormSubmit(e) {
            const form = e.target;
            const submitButton = form.querySelector('[type="submit"]');
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner"></span> Processing...';
            }
            
            form.classList.add('loading');
            
            setTimeout(() => {
                form.classList.remove('loading');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.dataset.originalText || 'Submit';
                }
            }, 2000);
        }

        // Modal Click Handling
        handleModalClicks(e) {
            if (e.target.classList.contains('modal-close') || 
                e.target.classList.contains('modal-backdrop')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    const modalId = modal.id;
                    this.closeModal(modalId);
                }
            }
        }

        // Utility Functions
        // debounce(func, wait) {
        //     let timeout;
        //     return function executedFunction(...args) {
        //         const later = () => {
        //             clearTimeout(timeout);
        //             func(...args);
        //         };
        //         clearTimeout(timeout);
        //         timeout = setTimeout(later, wait);
        //     };
        // }

        // Event Handlers
        onDOMReady() {
            this.setupActiveNavigation();
            this.initializeAnimations();
        }

        onResize() {
            this.updateLayout();
        }

        setupActiveNavigation() {
            const currentPath = window.location.pathname;
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                }
            });
        }

        initializeAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            });

            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
        }

        updateLayout() {
            const isMobile = window.innerWidth < 768;
            document.body.classList.toggle('mobile', isMobile);
        }

        setupPerformanceMonitoring() {
            if ('performance' in window) {
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        this.logPerformanceMetrics();
                    }, 100); // Small delay to ensure all metrics are available
                });
            }
        }

        logPerformanceMetrics() {
            try {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (navigation) {
                    // Total page load time (from start to load complete)
                    const totalLoadTime = navigation.loadEventEnd - navigation.navigationStart;
                    
                    // DOM content loaded time
                    const domContentLoaded = navigation.domContentLoadedEventEnd - navigation.navigationStart;
                    
                    // Time to first byte
                    const ttfb = navigation.responseStart - navigation.requestStart;
                    
                    // Resource loading time
                    const resourceLoadTime = navigation.loadEventEnd - navigation.responseEnd;
                    
                    console.log('📊 Performance Metrics:');
                    console.log(`   Total Load Time: ${totalLoadTime.toFixed(2)}ms`);
                    console.log(`   DOM Ready: ${domContentLoaded.toFixed(2)}ms`);
                    console.log(`   Time to First Byte: ${ttfb.toFixed(2)}ms`);
                    console.log(`   Resource Loading: ${resourceLoadTime.toFixed(2)}ms`);
                    
                    // Log to page if debug mode is enabled
                    if (window.location.search.includes('debug=true')) {
                        this.displayPerformanceOnPage(totalLoadTime, domContentLoaded, ttfb);
                    }
                }
            } catch (error) {
                console.log('Performance metrics unavailable:', error.message);
            }
        }

        displayPerformanceOnPage(totalLoad, domReady, ttfb) {
            const perfDiv = document.createElement('div');
            perfDiv.style.cssText = `
                position: fixed; top: 10px; right: 10px; 
                background: rgba(0,0,0,0.8); color: white; 
                padding: 15px; border-radius: 8px; font-family: monospace; 
                font-size: 12px; z-index: 9999; max-width: 250px;
            `;
            perfDiv.innerHTML = `
                <div style="margin-bottom: 10px; font-weight: bold;">🚀 Performance</div>
                <div>Total: ${totalLoad.toFixed(0)}ms</div>
                <div>DOM: ${domReady.toFixed(0)}ms</div>
                <div>TTFB: ${ttfb.toFixed(0)}ms</div>
                <button onclick="this.parentElement.remove()" style="margin-top: 10px; padding: 5px 10px; background: #666; border: none; color: white; border-radius: 4px; cursor: pointer;">×</button>
            `;
            document.body.appendChild(perfDiv);
        }

        setupThemeDetection() {
            // Ensure state is initialized
            if (!this.state) {
                this.state = { currentTheme: 'light' };
            }
            
            // Check for system theme preference
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            this.state.currentTheme = mediaQuery.matches ? 'dark' : 'light';
            
            // Listen for theme changes
            mediaQuery.addEventListener('change', (e) => {
                this.state.currentTheme = e.matches ? 'dark' : 'light';
                this.updateTheme();
            });
        }

        setupIntersectionObserver() {
            // Check if IntersectionObserver is supported
            if ('IntersectionObserver' in window) {
                // Lazy loading for images
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                // Observe all lazy images
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });

                // Animation on scroll
                const animationObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                        }
                    });
                }, { threshold: 0.1 });

                // Observe elements with animation classes
                document.querySelectorAll('.animate-on-scroll').forEach(el => {
                    animationObserver.observe(el);
                });
            }
        }

        updateTheme() {
            if (this.state && this.state.currentTheme) {
                document.documentElement.setAttribute('data-theme', this.state.currentTheme);
                // Add theme-specific classes or update CSS variables
            }
        }

        // Modern loading state management
        setLoading(loading) {
            this.state = this.state || {};
            this.state.isLoading = loading;
            document.body.classList.toggle('loading', loading);
            
            if (loading) {
                this.showLoadingSpinner();
            } else {
                this.hideLoadingSpinner();
            }
        }

        showLoadingSpinner() {
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            spinner.innerHTML = `
                <div class="spinner-ring"></div>
                <div class="spinner-text">Loading...</div>
            `;
            document.body.appendChild(spinner);
        }

        hideLoadingSpinner() {
            const spinner = document.querySelector('.loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
    }

    // Initialize the application
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.staApp = new StudentTimeAdvisor();
        });
    } else {
        window.staApp = new StudentTimeAdvisor();
    }

    // Global utility functions
    window.STAUtils = {
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        },

        formatDuration: function(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            
            if (hours > 0) {
                return `${hours}h ${mins}m`;
            }
            return `${mins}m`;
        },

        showLoading: function(element) {
            element.classList.add('loading');
            element.innerHTML = '<span class="spinner"></span> Loading...';
        },

        hideLoading: function(element, originalContent) {
            element.classList.remove('loading');
            element.innerHTML = originalContent;
        }
    };

})();

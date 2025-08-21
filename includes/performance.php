<?php
/**
 * Performance Optimization and Monitoring for Student Time Management Advisor
 */

// Performance monitoring
class PerformanceMonitor {
    private static $startTime;
    private static $queries = [];
    private static $memoryPeak;
    
    public static function start() {
        self::$startTime = microtime(true);
        self::$memoryPeak = memory_get_peak_usage(true);
    }
    
    public static function end() {
        $executionTime = microtime(true) - self::$startTime;
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'execution_time' => round($executionTime * 1000, 2), // milliseconds
            'memory_usage' => self::formatBytes($memoryUsage),
            'memory_peak' => self::formatBytes($memoryPeak),
            'query_count' => count(self::$queries),
            'queries' => self::$queries
        ];
    }
    
    public static function addQuery($sql, $params, $executionTime) {
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => round($executionTime * 1000, 2)
        ];
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Advanced caching system
class AdvancedCache {
    private static $cache = [];
    private static $cacheDir;
    
    public static function init($directory = null) {
        self::$cacheDir = $directory ?? sys_get_temp_dir() . '/sta_cache';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    public static function get($key, $default = null) {
        $file = self::getCacheFile($key);
        
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                return $data['value'];
            } else {
                unlink($file); // Expired, remove file
            }
        }
        
        return $default;
    }
    
    public static function set($key, $value, $ttl = 300) {
        $file = self::getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public static function delete($key) {
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public static function clear() {
        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    public static function getCacheFile($key) {
        $hash = md5($key);
        return self::$cacheDir . '/' . $hash . '.cache';
    }
    
    public static function getStats() {
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                $validCount++;
            } else {
                $expiredCount++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validCount,
            'expired_files' => $expiredCount,
            'total_size' => self::formatBytes($totalSize)
        ];
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Database query optimization
class QueryOptimizer {
    private static $slowQueryThreshold = 100; // milliseconds
    
    public static function optimizeQuery($sql) {
        // Add hints for common patterns
        $sql = self::addIndexHints($sql);
        $sql = self::optimizeJoins($sql);
        $sql = self::optimizeWhereClause($sql);
        
        return $sql;
    }
    
    private static function addIndexHints($sql) {
        // Add FORCE INDEX hints for common queries
        if (stripos($sql, 'WHERE user_id = ?') !== false) {
            $sql = str_replace('FROM tasks', 'FROM tasks FORCE INDEX (idx_user_due)', $sql);
        }
        
        return $sql;
    }
    
    private static function optimizeJoins($sql) {
        // Ensure proper join order
        if (stripos($sql, 'JOIN') !== false) {
            // Add hints for join optimization
            $sql = str_replace('JOIN', 'INNER JOIN', $sql);
        }
        
        return $sql;
    }
    
    private static function optimizeWhereClause($sql) {
        // Optimize WHERE clauses
        if (stripos($sql, 'WHERE') !== false) {
            // Ensure proper order of conditions (most selective first)
            // This is a simplified example - in practice, you'd analyze execution plans
        }
        
        return $sql;
    }
    
    public static function isSlowQuery($executionTime) {
        return $executionTime > self::$slowQueryThreshold;
    }
    
    public static function logSlowQuery($sql, $params, $executionTime) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true)
        ];
        
        error_log('SLOW_QUERY: ' . json_encode($log));
    }
}

// Asset optimization
class AssetOptimizer {
    public static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $css);
        
        return trim($css);
    }
    
    public static function minifyJS($js) {
        // Basic JS minification (remove comments and unnecessary whitespace)
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        $js = preg_replace('/\/\/.*$/m', '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
    
    public static function getAssetVersion($file) {
        $path = __DIR__ . '/../public' . $file;
        if (file_exists($path)) {
            return filemtime($path);
        }
        return time();
    }
    
    public static function generateAssetTag($file, $type = 'css') {
        $version = self::getAssetVersion($file);
        
        if ($type === 'css') {
            return "<link rel='stylesheet' href='{$file}?v={$version}'>";
        } elseif ($type === 'js') {
            return "<script src='{$file}?v={$version}'></script>";
        }
        
        return '';
    }
}

// Lazy loading for images and content
class LazyLoader {
    public static function image($src, $alt, $class = '', $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E') {
        return "<img src='{$placeholder}' data-src='{$src}' alt='{$alt}' class='lazy {$class}' loading='lazy'>";
    }
    
    public static function content($content, $threshold = 0.1) {
        return "<div class='lazy-content' data-threshold='{$threshold}'>{$content}</div>";
    }
    
    public static function getLazyLoadScript() {
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('img.lazy');
            const lazyContent = document.querySelectorAll('.lazy-content');
            
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });
            
            const contentObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const content = entry.target;
                        content.style.opacity = '1';
                        content.style.transform = 'translateY(0)';
                        observer.unobserve(content);
                    }
                });
            });
            
            lazyImages.forEach(img => imageObserver.observe(img));
            lazyContent.forEach(content => contentObserver.observe(content));
        });
        </script>";
    }
}

// Database connection pooling (simplified)
class ConnectionPool {
    private static $connections = [];
    private static $maxConnections = 10;
    private static $currentConnections = 0;
    
    public static function getConnection() {
        if (self::$currentConnections < self::$maxConnections) {
            self::$currentConnections++;
            return DB::conn();
        }
        
        // Return existing connection if available
        return DB::conn();
    }
    
    public static function releaseConnection() {
        if (self::$currentConnections > 0) {
            self::$currentConnections--;
        }
    }
    
    public static function getStats() {
        return [
            'max_connections' => self::$maxConnections,
            'current_connections' => self::$currentConnections,
            'available_connections' => self::$maxConnections - self::$currentConnections
        ];
    }
}

// Initialize performance features
AdvancedCache::init();

// Helper functions
function start_performance_monitoring() {
    PerformanceMonitor::start();
}

function end_performance_monitoring() {
    return PerformanceMonitor::end();
}

function cache_get($key, $default = null) {
    return AdvancedCache::get($key, $default);
}

function cache_set($key, $value, $ttl = 300) {
    return AdvancedCache::set($key, $value, $ttl);
}

function cache_delete($key) {
    return AdvancedCache::delete($key);
}

function get_asset_version($file) {
    return AssetOptimizer::getAssetVersion($file);
}

function lazy_image($src, $alt, $class = '') {
    return LazyLoader::image($src, $alt, $class);
}

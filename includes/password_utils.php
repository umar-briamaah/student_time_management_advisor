<?php
/**
 * Password Utilities for Student Time Management Advisor
 * Provides configurable password validation and strength calculation
 */

class PasswordValidator {
    // Configurable validation rules
    private static $config = [
        'min_length' => 8,
        'max_length' => 128,
        'require_lowercase' => true,
        'require_uppercase' => true,
        'require_numbers' => true,
        'require_special' => false, // Set to true for stricter requirements
        'prevent_common' => true,
        'prevent_personal_info' => true,
        'strength_threshold' => 4 // Minimum strength score (0-7)
    ];
    
    /**
     * Set validation configuration
     */
    public static function setConfig($config) {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Get current configuration
     */
    public static function getConfig() {
        return self::$config;
    }
    
    /**
     * Validate password with comprehensive checks
     */
    public static function validate($password, $password_confirm = null, $user_data = []) {
        $errors = [];
        
        // Check password confirmation if provided
        if ($password_confirm !== null && $password !== $password_confirm) {
            $errors[] = 'Passwords do not match';
        }
        
        // Length validation
        if (strlen($password) < self::$config['min_length']) {
            $errors[] = sprintf('Password must be at least %d characters long', self::$config['min_length']);
        }
        
        if (strlen($password) > self::$config['max_length']) {
            $errors[] = sprintf('Password must be less than %d characters', self::$config['max_length']);
        }
        
        // Character variety validation
        if (self::$config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (self::$config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (self::$config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (self::$config['require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Common password prevention
        if (self::$config['prevent_common']) {
            $common_passwords = self::getCommonPasswords();
            if (in_array(strtolower($password), $common_passwords)) {
                $errors[] = 'Password is too common, please choose a stronger password';
            }
        }
        
        // Personal information prevention
        if (self::$config['prevent_personal_info'] && !empty($user_data)) {
            $personal_errors = self::checkPersonalInfo($password, $user_data);
            $errors = array_merge($errors, $personal_errors);
        }
        
        // Strength validation
        $strength = self::calculateStrength($password);
        if ($strength['score'] < self::$config['strength_threshold']) {
            $errors[] = 'Password is too weak. Please choose a stronger password.';
        }
        
        return $errors;
    }
    
    /**
     * Calculate password strength score (0-7)
     */
    public static function calculateStrength($password) {
        $score = 0;
        $feedback = [];
        
        // Length bonus
        if (strlen($password) >= self::$config['min_length']) $score += 1;
        if (strlen($password) >= 12) $score += 1;
        if (strlen($password) >= 16) $score += 1;
        
        // Character variety bonus
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[0-9]/', $password)) $score += 1;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 1;
        
        // Generate feedback
        if (strlen($password) < 12) $feedback[] = 'Make it longer';
        if (!preg_match('/[a-z]/', $password)) $feedback[] = 'Add lowercase letters';
        if (!preg_match('/[A-Z]/', $password)) $feedback[] = 'Add uppercase letters';
        if (!preg_match('/[0-9]/', $password)) $feedback[] = 'Add numbers';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $feedback[] = 'Add special characters';
        
        return [
            'score' => $score,
            'max_score' => 7,
            'percentage' => round(($score / 7) * 100),
            'feedback' => $feedback,
            'level' => self::getStrengthLevel($score)
        ];
    }
    
    /**
     * Get strength level description
     */
    private static function getStrengthLevel($score) {
        if ($score >= 6) return 'Very Strong';
        if ($score >= 4) return 'Strong';
        if ($score >= 3) return 'Good';
        if ($score >= 2) return 'Fair';
        if ($score >= 1) return 'Weak';
        return 'Very Weak';
    }
    
    /**
     * Get strength level color for UI
     */
    public static function getStrengthColor($score) {
        if ($score >= 6) return 'bg-green-500';
        if ($score >= 4) return 'bg-green-500';
        if ($score >= 3) return 'bg-blue-500';
        if ($score >= 2) return 'bg-yellow-500';
        if ($score >= 1) return 'bg-orange-500';
        return 'bg-red-500';
    }
    
    /**
     * Check if password contains personal information
     */
    private static function checkPersonalInfo($password, $user_data) {
        $errors = [];
        
        if (!empty($user_data['name'])) {
            $name_parts = explode(' ', strtolower($user_data['name']));
            foreach ($name_parts as $part) {
                if (strlen($part) > 2 && stripos($password, $part) !== false) {
                    $errors[] = 'Password should not contain your name';
                    break;
                }
            }
        }
        
        if (!empty($user_data['email'])) {
            $email_username = explode('@', $user_data['email'])[0];
            if (strlen($email_username) > 2 && stripos($password, $email_username) !== false) {
                $errors[] = 'Password should not contain your email username';
            }
        }
        
        if (!empty($user_data['username'])) {
            if (stripos($password, $user_data['username']) !== false) {
                $errors[] = 'Password should not contain your username';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get list of common weak passwords
     */
    private static function getCommonPasswords() {
        return [
            'password', '123456', '123456789', 'qwerty', 'abc123', 'password123',
            'admin', 'letmein', 'welcome', 'monkey', 'dragon', 'master',
            'hello', 'freedom', 'whatever', 'qazwsx', 'trustno1', 'jordan',
            'joshua', 'maggie', 'enter', 'shadow', 'baseball', 'football',
            'michael', 'michelle', 'superman', 'batman', 'spiderman', 'starwars'
        ];
    }
    
    /**
     * Generate a secure random password
     */
    public static function generateSecurePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';
        
        // Ensure at least one character from each required category
        if (self::$config['require_lowercase']) {
            $password .= chr(rand(97, 122)); // a-z
        }
        if (self::$config['require_uppercase']) {
            $password .= chr(rand(65, 90)); // A-Z
        }
        if (self::$config['require_numbers']) {
            $password .= chr(rand(48, 57)); // 0-9
        }
        if (self::$config['require_special']) {
            $password .= '!@#$%^&*'[rand(0, 7)];
        }
        
        // Fill the rest randomly
        $remaining_length = $length - strlen($password);
        for ($i = 0; $i < $remaining_length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }
    
    /**
     * Get password requirements for display
     */
    public static function getRequirements() {
        $requirements = [];
        
        if (self::$config['min_length']) {
            $requirements[] = sprintf('At least %d characters', self::$config['min_length']);
        }
        if (self::$config['require_lowercase']) {
            $requirements[] = 'Lowercase letters (a-z)';
        }
        if (self::$config['require_uppercase']) {
            $requirements[] = 'Uppercase letters (A-Z)';
        }
        if (self::$config['require_numbers']) {
            $requirements[] = 'Numbers (0-9)';
        }
        if (self::$config['require_special']) {
            $requirements[] = 'Special characters (!@#$%^&*)';
        }
        
        return $requirements;
    }
}

/**
 * Helper functions for backward compatibility
 */

function validate_password($password, $password_confirm = null, $user_data = []) {
    return PasswordValidator::validate($password, $password_confirm, $user_data);
}

function calculate_password_strength($password) {
    return PasswordValidator::calculateStrength($password);
}

function get_password_requirements() {
    return PasswordValidator::getRequirements();
}

function generate_secure_password($length = 16) {
    return PasswordValidator::generateSecurePassword($length);
}

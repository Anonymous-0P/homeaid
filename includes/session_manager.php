<?php
/**
 * Session Management Utility
 * Handles session timeout and auto-logout functionality
 */

class SessionManager {
    // Session timeout duration in seconds (10 minutes = 600 seconds)
    const TIMEOUT_DURATION = 600; // 10 minutes
    
    /**
     * Initialize session with timeout
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session timeout if not already set
        if (!isset($_SESSION['login_time'])) {
            $_SESSION['login_time'] = time();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if session is valid (not expired)
     * @return bool True if session is valid, false if expired
     */
    public static function isSessionValid() {
        if (!isset($_SESSION['last_activity']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        $current_time = time();
        $time_since_activity = $current_time - $_SESSION['last_activity'];
        
        // Check if session has expired (no activity for 10 minutes)
        if ($time_since_activity > self::TIMEOUT_DURATION) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Update session activity timestamp
     */
    public static function updateActivity() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    /**
     * Get remaining session time in seconds
     * @return int Remaining time in seconds
     */
    public static function getRemainingTime() {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        $current_time = time();
        $time_since_activity = $current_time - $_SESSION['last_activity'];
        $remaining = self::TIMEOUT_DURATION - $time_since_activity;
        
        return max(0, $remaining);
    }
    
    /**
     * Get remaining time in human readable format
     * @return string Formatted time (e.g., "5 minutes 30 seconds")
     */
    public static function getRemainingTimeFormatted() {
        $remaining = self::getRemainingTime();
        
        if ($remaining <= 0) {
            return "Session expired";
        }
        
        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;
        
        if ($minutes > 0) {
            return "{$minutes} minute" . ($minutes > 1 ? 's' : '') . 
                   ($seconds > 0 ? " {$seconds} second" . ($seconds > 1 ? 's' : '') : '');
        } else {
            return "{$seconds} second" . ($seconds > 1 ? 's' : '');
        }
    }
    
    /**
     * Check if user is logged in and session is valid
     * @param string $required_role Required user role (customer, provider, admin)
     * @return bool True if user is properly authenticated
     */
    public static function checkAuth($required_role = null) {
        self::startSession();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            return false;
        }
        
        // Check session validity
        if (!self::isSessionValid()) {
            self::logout();
            return false;
        }
        
        // Check role if specified
        if ($required_role && $_SESSION['role'] !== $required_role) {
            return false;
        }
        
        // Update activity timestamp
        self::updateActivity();
        
        return true;
    }
    
    /**
     * Destroy session and logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
    
    /**
     * Set login session data
     * @param array $user_data User data from database
     */
    public static function setLoginSession($user_data) {
        self::startSession();
        
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['name'] = $user_data['name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set backward compatibility session variables
        if ($user_data['role'] === 'customer') {
            $_SESSION['customer_id'] = $user_data['id'];
        } elseif ($user_data['role'] === 'provider') {
            $_SESSION['provider_id'] = $user_data['id'];
        } elseif ($user_data['role'] === 'admin') {
            $_SESSION['admin_id'] = $user_data['id'];
        }
    }
    
    /**
     * Get session info for debugging
     * @return array Session information
     */
    public static function getSessionInfo() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['status' => 'No active session'];
        }
        
        return [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'Not set',
            'role' => $_SESSION['role'] ?? 'Not set',
            'name' => $_SESSION['name'] ?? 'Not set',
            'login_time' => isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Not set',
            'last_activity' => isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Not set',
            'remaining_time' => self::getRemainingTimeFormatted(),
            'is_valid' => self::isSessionValid() ? 'Yes' : 'No'
        ];
    }
}
?>

<?php
class Auth {
    public static function check() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        return isset($_SESSION['user_id']);
    }
    
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function user() {
        return $_SESSION['user'] ?? null;
    }
    
    public static function attempt($username, $password) {
        // Sample authentication - replace with your actual database check
        $valid_user = 'admin';
        $valid_pass = 'password123'; // In real app, store hashed passwords
        
        if ($username === $valid_user && $password === $valid_pass) {
            $_SESSION['user_id'] = 1;
            $_SESSION['user'] = [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'avatar' => 'assets/default-avatar.png'
            ];
            return true;
        }
        return false;
    }
    
    public static function logout() {
        session_unset();
        session_destroy();
    }
}
?>
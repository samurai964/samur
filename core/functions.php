<?php
// Final Max CMS - Core Functions

/**
 * Redirect to a specified URL.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Get a configuration setting.
 * @param string $key The configuration key.
 * @return mixed The configuration value or null if not found.
 */
function config($key) {
    global $GLOBALS;
    return isset($GLOBALS["config"][$key]) ? $GLOBALS["config"][$key] : null;
}

/**
 * Get the database PDO object.
 * @return PDO The PDO database object.
 */
function db() {
    global $GLOBALS;
    return $GLOBALS["pdo"];
}

/**
 * Sanitize input data.
 * @param string $data The input data to sanitize.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Hash a password.
 * @param string $password The password to hash.
 * @return string The hashed password.
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against a hash.
 * @param string $password The plain password.
 * @param string $hash The hashed password.
 * @return bool True if the password matches, false otherwise.
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a random string.
 * @param int $length The length of the string.
 * @return string The random string.
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get current user data from session.
 * @return array|null Current user data or null if not logged in.
 */
function current_user() {
    return isset($_SESSION["user"]) ? $_SESSION["user"] : null;
}

/**
 * Check if user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

/**
 * Check if current user has a specific role.
 * @param string $role The role to check against (e.g., 'admin', 'moderator').
 * @return bool True if user has the role, false otherwise.
 */
function has_role($role) {
    $user = current_user();
    return $user && $user["role"] === $role;
}

/**
 * Render a view file.
 * @param string $view The view file path (e.g., 'home/index').
 * @param array $data Data to pass to the view.
 */
function view($view, $data = []) {
    extract($data);
    $viewPath = ROOT_PATH . 
        (strpos($view, 'admin/') === 0 ? '/templates/admin/' : '/templates/frontend/') .
        str_replace('.', '/', $view) . 
        '.php';
    
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        die("View file not found: " . $viewPath);
    }
}

/**
 * Display a flash message.
 * @param string $message The message content.
 * @param string $type The message type (e.g., 'success', 'error', 'warning').
 */
function flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

/**
 * Get and clear flash message.
 * @return array|null The flash message data or null if none.
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Add more global helper functions as needed

?>


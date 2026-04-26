<?php
// Final Max CMS - Core Controller

class Controller {
    protected $model;

    public function __construct() {
        // يمكن تحميل النموذج هنا بشكل افتراضي أو في المتحكمات الفرعية
    }

    /**
     * Load a model.
     * @param string $modelName The name of the model to load.
     * @return object The loaded model instance.
     */
    protected function loadModel($modelName) {
        $modelPath = ROOT_PATH . 
            (strpos($modelName, 'admin/') === 0 ? '/modules/admin/' : '/modules/') .
            str_replace('.', '/', $modelName) . 
            'Model.php';
        
        if (file_exists($modelPath)) {
            require_once $modelPath;
            $modelClass = $modelName . 'Model';
            if (class_exists($modelClass)) {
                return new $modelClass();
            }
        }
        die("Model file not found or class not defined: " . $modelPath);
    }

    /**
     * Render a view.
     * @param string $viewName The name of the view file (e.g., 'home/index').
     * @param array $data Data to pass to the view.
     */
    protected function view($viewName, $data = []) {
        view($viewName, $data);
    }

    /**
     * Redirect to a specific URL.
     * @param string $url The URL to redirect to.
     */
    protected function redirect($url) {
        redirect($url);
    }

    /**
     * Set a flash message.
     * @param string $message The message content.
     * @param string $type The message type (e.g., 'success', 'error', 'warning').
     */
    protected function flash($message, $type = 'success') {
        flash_message($message, $type);
    }
}

?>


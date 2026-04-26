<?php

class PluginManager {

    private $pluginsPath;

    public function __construct() {
        $this->pluginsPath = __DIR__ . '/../plugins/';
    }

    public function loadPlugins() {

global $pdo;

if (!is_dir($this->pluginsPath)) return;

$stmt = $pdo->query("SELECT name FROM plugins WHERE status='active'");
$activePlugins = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($activePlugins as $plugin) {

    $boot = $this->pluginsPath . $plugin . '/boot.php';

    if (file_exists($boot)) {
        require_once $boot;
    }
}

}

}

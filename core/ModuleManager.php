<?php

require_once ROOT_PATH . '/core/Module.php';

class ModuleManager
{
    private array $modules = [];
    private string $modulesPath;

    public function __construct($modulesPath)
    {
        $this->modulesPath = $modulesPath;
        $this->loadModules();
    }

    private function loadModules()
    {
        if (!is_dir($this->modulesPath)) {
            return;
        }

        $dirs = scandir($this->modulesPath);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $moduleConfigPath = $this->modulesPath . '/' . $dir . '/module.json';

            if (file_exists($moduleConfigPath)) {
                $config = json_decode(file_get_contents($moduleConfigPath), true);

                $module = new Module($config);
                $this->modules[$module->name] = $module;

                if ($module->enabled) {
                    $this->bootModule($dir);
                }
            }
        }
    }

    private function bootModule($dir)
    {
        $bootFile = $this->modulesPath . '/' . $dir . '/boot.php';

        if (file_exists($bootFile)) {
            require_once $bootFile;
        }
    }

    public function getModules()
    {
        return $this->modules;
    }
}
<?php

class Module
{
    public string $name;
    public string $version;
    public string $description;
    public bool $enabled;

    public function __construct($config)
    {
        $this->name = $config['name'] ?? '';
        $this->version = $config['version'] ?? '1.0.0';
        $this->description = $config['description'] ?? '';
        $this->enabled = $config['enabled'] ?? false;
    }

    public function boot()
    {
        // يتم تنفيذه عند تشغيل الموديول
    }
}
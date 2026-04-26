<?php

class Cache
{
    private $path = __DIR__ . '/../cache/';

    public function __construct()
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function set($key, $data, $ttl = 60)
    {
        $file = $this->path . md5($key);

        $payload = [
            'expire' => time() + $ttl,
            'data' => $data
        ];

        file_put_contents($file, serialize($payload));
    }

    public function get($key)
    {
        $file = $this->path . md5($key);

        if (!file_exists($file)) return null;

        $payload = unserialize(file_get_contents($file));

        if ($payload['expire'] < time()) {
            unlink($file);
            return null;
        }

        return $payload['data'];
    }
}
?>

<?php

class JWT {

    private static $secret = "MY_SECRET_KEY";

    public static function encode($payload) {

        $header = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));
        $payload = base64_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$header.$payload", self::$secret);

        return "$header.$payload.$signature";
    }

    public static function decode($token) {

        $parts = explode('.', $token);

        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;

        $valid = hash_hmac('sha256', "$header.$payload", self::$secret);

        if ($signature !== $valid) return false;

        return json_decode(base64_decode($payload), true);
    }
}
?>

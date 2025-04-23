<?php

/**
 * @description JSON Web Token (JWT) Class
 * @author Sharky
 * @date 2025-3-25
 * @version 1.3.0
 */

namespace Sharky\Libs;
use Exception;

class JWT {
    private $secretKey;
    private $algorithm;

    public function __construct($secretKey, $algorithm = 'HS256') {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
    }

    public function encode($payload, $exp = 3600) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload['exp'] = time() + $exp;
        $payload = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        $signature = self::sign($this->secretKey,$base64UrlHeader . "." . $base64UrlPayload, $this->algorithm);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $signature;
    }

    public function decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token');
        }

        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        $signature = $parts[2];

        if (self::sign($this->secretKey,$parts[0] . "." . $parts[1], $header['alg']) !== $signature) {
            throw new Exception('Invalid signature');
        }

        if ($payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    public function refresh($jwt, $exp = 3600) {
        $payload = $this->decode($jwt);
        unset($payload['exp']);
        return $this->encode($payload, $exp);
    }

    private static function sign($secretKey, $data, $algorithm) {
        return self::base64UrlEncode(hash_hmac(self::getAlgorithm($algorithm), $data, $secretKey, true));
    }

    private static function getAlgorithm($algorithm) {
        $algorithms = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];
        if (!isset($algorithms[$algorithm])) {
            throw new Exception('Unsupported algorithm');
        }
        return $algorithms[$algorithm];
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

<?php

/**
 * @description JSON Web Token (JWT)
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Libs;

use Sharky\Exceptions\InvalidSignatureException;
use Sharky\Exceptions\InvalidTokenException;
use Sharky\Exceptions\TokenExpiredException;
use Sharky\Exceptions\UnsupportedTypeException;

class JWT {
    private $secretKey;
    private $algorithm;

    public function __construct($secretKey, $algorithm = 'HS256') {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
    }

    public function encode($payload, $exp = 3600): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload['exp'] = time() + $exp;
        $payload = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        $signature = self::sign($this->secretKey,$base64UrlHeader . "." . $base64UrlPayload, $this->algorithm);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $signature;
    }

    public static function decode($token): array | bool{
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        $signature = $parts[2];
        
        return [$header, $payload, $signature];
    }

    public function verify(string $token, bool $checkExp = true) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidTokenException('无效的Token');
        }
        [$header, $payload, $signature] = self::decode($token);

        if (self::sign($this->secretKey,$parts[0] . "." . $parts[1], $header['alg']) !== $signature) {
            throw new InvalidSignatureException('无效的签名');
        }

        if ($checkExp && $payload['exp'] < time()) {
            throw new TokenExpiredException('Token已过期');
        }

        return $payload;
    }

    public function refresh($token, $exp = 3600): string {
        $payload = $this->decode($token);
        unset($payload['exp']);
        return $this->encode($payload, $exp);
    }

    private static function sign($secretKey, $data, $algorithm): string {
        return self::base64UrlEncode(hash_hmac(self::getAlgorithm($algorithm), $data, $secretKey, true));
    }

    private static function getAlgorithm($algorithm): string {
        $algorithms = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];
        if (!isset($algorithms[$algorithm])) {
            throw new UnsupportedTypeException('不支持的类型，应该为[HS256|HS384|HS512]');
        }
        return $algorithms[$algorithm];
    }

    private static function base64UrlEncode($data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data): bool|string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

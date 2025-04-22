<?php
/**
 * @description Multi-Factor Authentication (MFA) Class
 * @author Sharky
 * @date 2025-3-25
 * @version 1.3.0
 */

namespace Sharky\Libs;
use InvalidArgumentException;

class MFA
{
    private $secret;
    private $secretKey;

    private $slice;
    private $faultTol = 1;

    /**
     * @param string $secret Base32 编码的密钥
     * @throws InvalidArgumentException 如果密钥无效
     */
    public function __construct($secret, $slice = 30)
    {
        if (empty($secret) || !is_string($secret) || strlen($secret) < 16) {
            throw new InvalidArgumentException('密钥必须是一个非空字符串，且长度至少为16个字符。');
        }
        if (!preg_match('/^[A-Z2-7=]+$/', $secret)) {
            throw new InvalidArgumentException('密钥必须是一个 Base32 编码的字符串。');
        }
        
        $this->secret = $secret;
        $this->secretKey = $this->base32Decode($secret);
        if ($this->secretKey === false) {
            throw new InvalidArgumentException('无效的 Base32 编码密钥。');
        };

        if ($slice <= 0 || !is_int($slice)) {
            throw new InvalidArgumentException('时间片长度必须是一个大于0的整数。');
        }
        $this->slice = $slice;
    }

    /**
     * 生成一个随机的 Base32 编码共享密钥。
     * @param int $length 密钥长度（默认是16）
     * @return string Base32 编码的共享密钥
     */
    public static function generateSharedSecret($length = 16)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    /**
     * 为给定的密钥和标签生成一个二维码 URL。
     * @param string $account 二维码的用户标签（通常是用户名或电子邮件地址）
     * @param string $issuer 二维码的发行者（可选）
     * @return string 二维码 URL
     */
    public function getQRCodeUrl($account, $issuer = 'SharkyPHP')
    {
        $secret = $this->secret;
        $issuer = urlencode($issuer);
        $label = urlencode($account);
        $label = "{$issuer}:{$label}";
        $url = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
        return $url;
    }
   
    /**
     * 解码一个 Base32 编码的字符串。
     * @param string $secret Base32 编码的字符串
     * @return string|false 解码后的二进制字符串，失败时返回 false
     */
    private static function base32Decode($secret)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; $i++) {
            if (
                $paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])
            ) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $binaryString = '';
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $x = '';
            if (!in_array($secret[$i], str_split($alphabet))) {
                return false;
            }
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(strpos($alphabet, $secret[$i + $j]), 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= chr(base_convert($eightBits[$z], 2, 10));
            }
        }
        return $binaryString;
    }

    /**
     * 基于密钥和时间间隔生成一个 HOTP 令牌。
     * @param string $secretKey 密钥
     * @param int $intervalsNo 时间间隔编号
     * @return string OTP 令牌
     */
    private static function getHOTPToken($secretKey, $intervalsNo)
    {
        $msg = pack('N*', 0) . pack('N*', $intervalsNo);
        $hash = hash_hmac('sha1', $msg, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;
        $binary = (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff);
        $token = $binary % 1000000;
        return str_pad($token, 6, '0', STR_PAD_LEFT);
    }

    /**
     * 基于当前时间和密钥生成一个 TOTP 令牌。
     * @param int $faultTol 容错值，表示允许的时间片偏移量（默认为1）
     * @return array 包含 TOTP 令牌的数组和距离下一个令牌的时间
     */
    public function getTOTPToken($faultTol = 1)
    {
        if (!is_int($faultTol) || abs($faultTol) > 5) {
            throw new InvalidArgumentException('容错值必须是一个整数，且绝对值不能超过5。');
        }

        $ctime = time();
        $slice = $this->slice ?: 30; // 默认时间片长度为30秒
        $timeSlice = floor($ctime / $slice); // 当前时间片
        $rest = $slice - ($ctime % $slice); // 距离下一个令牌的时间
        for ($i = - abs($faultTol); $i <= abs($faultTol); $i++) {
            $totpSlice = $timeSlice + $i;
            $totps[] = self::getHOTPToken($this->secretKey, $totpSlice);
        }

        return [
             // "token" => $totps[intdiv(count($totps), 2)],
            "token" => $totps,
            "rest" => $rest
        ];
    }
}

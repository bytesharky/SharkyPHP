<?PHP

/**
 * @description 图形验证码
 * @author Sharky
 * @date 2025-5-7
 * @version 1.4.0
 */

namespace Sharky\Libs;

use Sharky\Core\Container;

class Captcha
{
    private $secretKey = "";
    private $timeout;
    private $characters;
    private $isDebug;
    private $fontName;

    public function __construct()
    {
        // 从配置文件中读取
        $config = Container::getInstance()->make("config");
        $this->secretKey = $config->get('config.captcha.safeKey', 'sharkyphp');
        $this->timeout = $config->get('config.captcha.timeout', 600);
        $this->characters = $config->get('config.captcha.characters', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $this->isDebug = $config->get('config.isdebug', false);
        $this->fontName = realpath(SHARKY_ROOT . '/font/arial.ttf');
    }

    public function verify($captcha, $uuid)
    {
        $bin = hex2bin(str_replace('-', '', $uuid));
        if (strlen($bin) !== 16) {
            return false;
        }
        $timeHex = bin2hex(substr($bin, 0, 6));
        $timestamp = hexdec(substr($timeHex, 0, 8));
        if (time() > $timestamp) {
            return false;
        }
        $expected = $this->generateUUID($captcha, $timestamp);
        return strtolower($expected) === strtolower($uuid);
    }

    private function generateUUID($captcha, $timestamp)
    {
        $timeHex = dechex($timestamp);
        $hash = hash_hmac('sha256', $captcha . $timeHex, $this->secretKey);
        $customA = hex2bin(substr($timeHex . substr($hash, 0, 4), 0, 12));
        $customB = hex2bin(substr($hash, 4, 8));
        $customC = hex2bin(substr($hash, 8, 20));
        return UUIDGenerator::UUIDv8($customA, $customB, $customC);
    }

    // 生成验证码图形
    public function makeCaptcha($captcha = "", $base64 = true)
    {
        $fontPath = $this->fontName;
        if (!$this->isFontUsable($fontPath)) {
            $converted = iconv("UTF-8", "GBK//IGNORE", $fontPath);
            if ($converted !== false && $converted !== $fontPath) {
                if (!$this->isFontUsable($fontPath = $converted)) {
                    return response("该字体路径不可用(不推荐使用中文路径)", 500);
                }
            } else {
                return response("该字体路径不可用(不推荐使用中文路径)", 500);
            }
        }

        if (($support = $this->supportGD()) !== true) {
            return $support;
        }

        $imgw = 100;
        $imgh = 40;
        $linecount = 5;
        $pointCount = 200;

        $image = imagecreatetruecolor($imgw, $imgh);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 20, 20, $color);

        $captcha = empty($captcha) ? $this->getCaptcha() : $captcha;
        $characters = str_split($captcha);

        foreach ($characters as $i => $chr) {
            $fontSize = 15;
            $x = rand(5, 5) + $i * $imgw / 4;
            $y = rand(5, $imgh - $fontSize - 5) + $fontSize;
            $color = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 120));
            imagettftext($image, $fontSize, rand(-45, 45), $x, $y, $color, $fontPath, $chr);
        }

        for ($i = 0; $i < $pointCount; $i++) {
            $pointColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
            imagesetpixel($image, rand(0, $imgw), rand(0, $imgh), $pointColor);
        }
        for ($i = 0; $i < $linecount; $i++) {
            $linePoint = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
            imageline($image, rand(10, 50), rand(10, $imgh - 10), rand($imgw - 50, $imgw - 10), rand(10, $imgh - 10), $linePoint);
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);
        $buffer = ob_get_clean();

        // base64编码
        if ($base64) {
            $base64 = base64_encode($buffer);
            $base64 = 'data:' . "image/png" . ';base64,' . $base64;

            $respone = [
                "base64" => $base64,
                "uuid" => $this->generateUUID($captcha, time() + $this->timeout),
            ];

            // 开启调试时同时返回验证码，方便测试
            $this->isDebug && ($respone["captcha"] = $captcha);
            return $respone;
        }
        
        return response($buffer, 200)->header(['Content-Type' => 'image/png']);
    }

    // 生成验证码
    public function getCaptcha($length = 4)
    {
        $captcha = '';
        for ($i = 0; $i < $length; $i++) {
            $string = substr($this->characters, rand(0, strlen($this->characters) - 1), 1);
            $captcha .= $string;
        }
        return $captcha;
    }

    private function isFontUsable($fontPath)
    {
        if (!file_exists($fontPath))
            return false;

        $usable = true;
        // 临时错误处理器
        set_error_handler(function ($errno, $errstr) use (&$usable) {
            if (stripos($errstr, 'Could not find/open font') !== false) {
                $usable = false;
            }
            // 阻止默认错误处理（避免输出警告）
            return true;
        });
        // 尝试绘制
        $im = imagecreatetruecolor(1, 1);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagettftext($im, 10, 0, 0, 10, $black, $fontPath, "test");
        imagedestroy($im);
        // 恢复默认处理器
        restore_error_handler();
        return $usable;
    }

    private function supportGD()
    {
        if (!extension_loaded('gd')) {
            return response("GD 库不没有正确安装。", 500);
        } else {
            $gdInfo = gd_info();
            if (!$gdInfo['FreeType Support']) {
                return response("GD 库不支持 TrueType 字体。", 500);
            }
        }
        return true;
    }
}
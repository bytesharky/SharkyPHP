<?PHP

namespace Sharky\Libs;

class Captcha
{
    private $secretKey = "";
    private $timeout;
    private $characters;

    public function __construct()
    {
        // 从配置文件中读取
        $this->secretKey = '$safeKey';
        $this->timeout = 300;
        $this->characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ1234567890';
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

    // 生成验证码
    public function getCaptcha($length = 4)
    {

        $imgw = 100;
        $imgh = 40;
        $linecount = 5;
        $pointCount = 200;

        $image = imagecreatetruecolor($imgw, $imgh);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 20, 20, $color);

        $captcha = '';
        for ($i = 0; $i < 4; $i++) {
            $fontSize = 15;
            $x = rand(5, 5) + $i * $imgw / 4;
            $y = rand(5, $imgh - $fontSize - 5) + $fontSize;
            $string = substr($this->characters, rand(0, strlen($this->characters) - 1), 1);
            $captcha .= $string;
            $color = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 120));
            imagettftext($image, $fontSize, rand(-45, 45), $x, $y, $color, './arial.ttf', $string);
        }

        for ($i = 0; $i < $pointCount; $i++) {
            $pointColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
            imagesetpixel($image, rand(0, $imgw), rand(0, $imgh), $pointColor);
        }
        for ($i = 0; $i < $linecount; $i++) {
            $linePoint = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
            imageline($image, rand(10, 50), rand(10, $imgh - 10), rand($imgw - 50, $imgw - 10), rand(10, $imgh - 10), $linePoint);
        }

        // base64编码
        ob_start();
        imagepng($image);
        imagedestroy($image);
        $buffer = ob_get_clean();
        $base64 = base64_encode($buffer);
        $base64 = 'data:' . "image/png" . ';base64,' . $base64;

        return [
            "base64" => $base64,
            "uuid" => $this->generateUUID($captcha, time() + $this->timeout)
        ];

    }
}
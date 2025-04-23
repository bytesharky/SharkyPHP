# `MFA` 类使用说明

## 一、概述

 `MFA` 类位于 `Sharky\Libs` 命名空间下，是一个用于 `MFA` 多因素身份验证的工具类，它提供了生成共享密钥，计算密码，生成二维码链接等方法，方便实现多因素身份验证。

## 二、主要方法说明

### 1. `__construct($secret, $slice = 30)` 方法

- **功能**：构造函数，用于实例化一个 `MFA` ，参数 `$secret` 为生成令牌的共享密钥； 参数 `$slice` 为时间片段(秒)，通常是30秒，这里默认是 `30`。

### 2. `generateSharedSecret()` 方法

- **功能**：这是一个静态方法，用于随机生成一个共享密钥。

### 3. `getQRCodeUrl($account, $issuer = 'SharkyPHP')` 方法

- **功能**：生成当前令牌的二维码url，参数 `$account` 为用户标签，参数 `$issuer` 为令牌发行者，这里默认是 `SharkyPHP`。

- **例如**：使用 `getQRCodeUrl('MFA 测试');` 将得到 `otpauth://totp/SharkyPHP:MFA+%E6%B5%8B%E8%AF%95?secret=JBSWY3DPEHPK3PXP&issuer=SharkyPHP`;

### 4. `getTOTPToken($faultTol = 1)` 方法

- **功能**：用于获取当时时间片段的动态密码，参数 `$faultTol` 为容差值，额外返回前后N个时间片段的令牌，以避免服务器和客户的时间误差导致的校验失败。

- **例如**：使用 `getTOTPToken();`  将得到下面的结果：

```php
array(2) {
  ["token"] => array(3) {
    [0] => string(6) "373936"
    [1] => string(6) "908853"
    [2] => string(6) "167074"
  }
  ["rest"] => int(25)
}

// token 是可用的密码数组，
// rest  是密码重置剩余时间
```

---

在使用过程中，如果您发现了任何问题或者有疑问，可以通过 new issues 的方式反馈，我们会及时处理。感谢您的理解与支持。

修订：2025-4-23 12点

[返回目录](/SharkyPHP.md)

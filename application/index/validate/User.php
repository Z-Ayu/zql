<?php
namespace app\index\validate;

use think\Validate;

class User extends Validate
{
    // 验证规则
    protected $rule = [
        'username|用户名' => 'require|length:3,12',
        'password|用户密码' => 'require|confirm:repassword|length:6,16',
        'email|邮箱' => 'require|email',
        'code|验证码' => 'require|captcha',
    ];

    protected $message = [
        'username.require' => '请输入用户名',
        'username.length' => '用户名长度应该为3-12个字符串',
        'password.require' => '请输入6-16位密码',
        'password.confirm' => '两次密码不一致',
        'email.require' => '请输入有效邮箱',
        'code.require' => '请输入验证码',
    ];
}
<?php
namespace app\index\validate;

use think\Validate;

class Ask extends Validate
{
    protected $rule = [
        'title|标题' => 'require|max:50',
        'content|内容' => 'require|min:20',

    ];
    protected $massage = [
        'title.require' => '标题不能为空',
        'title.max' => '标题长度不能大于50',
        'content.require' => '问题内容不能为空',
        'content.length' => '内容长度必须不能小于20',
    ];
}
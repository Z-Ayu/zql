<?php
namespace app\index\validate;

use think\Validate;

class Article extends Validate
{
    protected $rule = [
        'title|标题' => 'require|max:300',
        'content|内容' => 'require|min:20',
        'destination|目的地' => 'require',
        'spendtime|历经时间' => 'require',
        'year|出发的年份' => 'require',
        'month' => 'require',
        'day' => 'require',

    ];
    protected $massage = [
        'title.require' => '标题不能为空',
        'title.max' => '标题长度不能大于300',
        'content.require' => '问题内容不能为空',
        'content.length' => '内容长度必须不能小于20',
        'year.require' => '出发年月不能为空',
        'month.require' => '出发年月不能为空',
        'day.require' => '出发年月日不能为空',
        'destination.require' => '目的地不能为空',

    ];
}
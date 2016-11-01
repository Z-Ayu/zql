<?php
namespace app\index\validate;
use think\Validate;
class Peers extends Validate
{
    protected $rule = [
        'title|标题' 	=> 'require|max:300',
        'origin|出发地'  => 'require',
        'destination|目的地'   => 'require',
        'content|内容' 	=> 'require|min:20',
        'start_time|出发时间' => 'require',
        'end_time|截止时间' => 'require',
        'spendtime|历经时间'     => 'require',
        'content|公告内容' => 'require',

    ];
    protected $massage = [
        'title.require'   => '标题不能为空',
        'title.max'       => '标题长度不能大于300',
        'content.require' => '公告内容不能为空',
        'origin.require'  => '出发地不能为空',
        'destination.require'=>'目的地不能为空',
        'startime.require' => '出发时间不能为空',
        'endtime.require' => '截止时间不能为空',
        'spendtime.require' => '历经时间不能为空',
    ];
}
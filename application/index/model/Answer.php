<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;
use think\Request;

class Answer extends Model
{
    //软删除
    use SoftDelete;
    //自动处理时间
    protected static $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';

    //自动存入IP；
    protected $auto = ['create_ip'];

    protected function setCreateIpAttr()
    {
        return Request::instance()->ip();
    }

}
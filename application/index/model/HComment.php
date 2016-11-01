<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;
use think\Request;

class HComment extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';
    /*自动处理create_ip*/
    protected $auto = ['create_ip'];


    protected function setCreateIpAttr()
    {
        return Request::instance()->ip();

    }

}

<?php
namespace app\index\model;
use think\Model;
use traits\model\SoftDelete;
use think\Request;
class Peers extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';
    protected $auto = ['create_ip'];
    //自动处理发表公告的ip
    protected function setCreateIpAttr()
    {
        return Request::instance()->ip();

    }

}
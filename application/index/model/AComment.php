<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class AComment extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';
    /*自动处理create_ip*/
}
<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class Article extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';
}
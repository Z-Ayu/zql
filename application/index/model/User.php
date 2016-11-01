<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';
    protected $insert = ['password'];

    public function role()
    {
        return $this->belongsToMany('Role');
    }

    public function setPasswordAttr($value)
    {
        return md5($value);
    }

    public function setUsernameAttr($value)
    {
        return strtolower($value);
    }

}

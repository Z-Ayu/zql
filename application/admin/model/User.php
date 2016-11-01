<?php 
namespace app\admin\model;

use think\Model;

use think\Request;

use traits\model\SoftDelete;

class User extends Model
{
	 use SoftDelete;
	 
	protected static $deleteTime = 'delete_time';
	
	//return 
}
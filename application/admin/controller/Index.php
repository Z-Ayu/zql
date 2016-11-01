<?php
namespace app\admin\controller;

use think\Controller;

use think\Db;
use think\Session;

class Index extends Controller
{
    public function index()
    {
        if (!empty(Session::get('admin'))) {
            $list = Db::name('user')->order('create_time', 'desc')->paginate(1);

            $title = '后台中心';

            $page = $list->render();
            //dump($page);
            return View('', compact('title', 'list', 'page'));
        } else {
            return $this->error('还没登录哦，请登录！', 'user/login');
        }
    }

    public function webInfo()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $str = explode(',', rtrim(input('post.head'), ','));

            $result = Db::name('article')->where('id', 'in', $str)->select();

            $arr = '';
            $img = '';
            foreach ($result as $val) {
                $arr[$val['id']] = $val['title'];
                $pre = '#<img(.*)src="(.*)"(.*)/>#imsU';
                preg_match($pre, $val['content'], $match);
                if (!empty($match[2])) {
                    $img .= $match[2] . ',';
                }
            }

            $update = Db::name('config')->where('id', 1)->update(['carousel' => json_encode($arr), 'img' => $img]);

            if ($update) {
                return $this->success('修改成功！');
            } else {
                return $this->error('修改失败！');
            }
        }
    }

    public function friend()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $url = input('post.')['url'];
            $title = input('post.')['title'];
            $arr = array_combine($url, $title);
            unset($arr['']);
            $friend = var_export($arr, true);
            file_put_contents('friend.php', "<?php\nreturn " . $friend . ';?>');
            return $this->success('成功修改友情链接！');
        }
    }
}

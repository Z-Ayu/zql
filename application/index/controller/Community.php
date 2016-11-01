<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Loader;
use think\Db;
use app\index\model\Peers;

class Community extends Controller
{
    // 同行界面
    public function peers(Peers $peers)
    {
        //查找最新发布的几张图片；
        $imgdata = $peers->field('first_img')->order('pid', 'desc')->limit(4)->select();
        //查找所有已经发布过的报道
        $postdata = $peers->where('status', 0)->order('pid', 'desc')->paginate(5);

        $local = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->order('good desc')->field('id,destination')->select();
        $title = '同行';
        $page = $postdata->render();
        return view('', compact('title', 'postdata', 'imgdata', 'page', 'local'));
    }

    // 发布公告
    public function post()
    {
        $title = '发布公告';
        return view('', compact('title'));
    }

    public function index()
    {
        return $this->redirect('community/peers');
    }

    public function detail()
    {
        $data = Db::name('peers')->where('pid', input('param.id'))->select();

        if (strtotime($data[0]['end_time']) < time()) {
            Db::name('peers')->where('pid', input('param.id'))->update(['status' => 1]);
            $data[0]['status'] = 1;
        }

        if ($data[0]['user_id'] == Session('uid')) {
            if (!empty($data[0]['jion_user'])) {
                $join = explode(',', trim($data[0]['jion_user'], ','));
                $joinUser = array();
                foreach ($join as $id) {
                    $re = Db::name('user')->where('uid', $id)->field('username')->select();
                    $joinUser[$id] = $re[0]['username'];
                }
                $this->assign('joinUser', $joinUser);
            }
        }
        $this->assign('data', $data[0]);

        $this->assign('title', $data[0]['title']);

        return $this->fetch();
    }

    public function search()
    {
        $time = input('post.year') . '-' . input('post.month') . '-' . input('post/day');
        $result = Db::name('peers')->where('origin', 'like', '%' . input('post.title') . '%')
            ->where('destination', 'like', '%' . input('post.destination') . '%')
            ->where('end_time', '<', $time)->select();
        $this->assign('re', $result);
        $this->assign('title', '结伴去旅行');
        return $this->fetch();
    }

    public function join()
    {
        if (empty(Session('uid'))) {
            return $this->error('请登录！', 'user/signin');
        } else {
            if (in_array(input('param.id'), explode(',', Session('user.joined')))) {
                if (input('param.a' == 'cancel')) {
                    $r = Db::name('peers')->where('pid', input('param.id'))->select();
                    $a = array_unique(explode(',', trim($r[0]['jion_user'], ',')));
                    foreach ($a as $k => $v) {
                        if ($v == Session('uid'))
                            unset($a[$k]);
                    }
                    Db::name('peers')->where('pid', input('param.id'))->update(['jion_user' => join(',', $a)]);

                    $arr = explode(',', trim(Session('user.joined')));
                    foreach ($arr as $k => $v) {
                        if ($v == input('param.id'))
                            unset($arr[$k]);
                    }

                    $re = Db::name('user')->where('uid', Session('uid'))->update(['joined' => join(',', $arr)]);
                    if ($re) {
                        Session::set('user.joined', join(',', $arr));
                        return $this->success('成功取消报名！');
                    } else {
                        return $this->error('取消报名失败！');
                    }
                } else {
                    return $this->error('你已经报过名了！');
                }

            } else if (input('param.a') == 'join') {
                $r = Db::name('peers')->where('pid', input('param.id'))->field('jion_user')->select();
                $a = array_unique(explode(',', trim($r[0]['jion_user'], ',')));
                $re1 = Db::name('peers')->where('pid', input('param.id'))
                    ->update(['join_num' => input('param.join') + 1, 'jion_user' => join(',', $a) . ',' . Session('uid')]);
                $re2 = Db::name('user')->where('uid', Session('uid'))->update(['joined' => Session('user')['joined'] . ',' . input('param.id')]);
                if ($re1 && $re2) ;
                {
                    Session::set('user.joined', Session('user.joined') . ',' . input('param.id'));
                    return $this->success('报名成功！');
                }
            }
        }
    }

    public function postNotice(Peers $peers)
    {
        $uid = Session('uid');
        if (empty($uid)) {
            $this->error('请登录再发布公告');
        } else {
            $prev_url = $_SERVER['HTTP_REFERER'];
            $validate = Loader::validate('Peers');
            if (!$validate->check($_POST)) {
                $this->error($validate->getError(), $prev_url);
            }
            $data = [];
            $data = input('post.');
            $prettern = '/<img(.*)?src="(.*)"/imUs';

            preg_match($prettern, $data['content'], $matches);
            if (!empty($matches[2]))
                $data['first_img'] = $matches[2];
            $username = session('user')['username'];
            $data['user_id'] = $uid;
            $data['username'] = $username;
            $peers->data($data);
            $result = $peers->save();
            if ($result) {
                $this->success('发布成功', 'community/peers');
            } else {
                $this->error('发布失败');
            }
        }
    }
}

<?php
namespace app\admin\controller;

use think\Controller;
use think\Session;
use think\Db;
use app\admin\model\User as UserModel;

class User extends Controller
{
    protected $count;

    public function index()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $list = Db::name('user')->order('uid', 'asc')->paginate(10);

            $page = $list->render();
            //查询申请导游的信息
            $guidedata = Db::name('apply')->order('status asc')->select();
            $allCount = Db::name('apply')->count();
            $title = '后台中心';
            return View('', compact('list', 'page', 'title', 'guidedata', 'allCount'));
        }
    }

    /*导游申请处理*/
    public function guide($id)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $title = '导游申请处理';
            $guidedata = Db::name('apply')->where('id', $id)->select();
            $data = $guidedata[0];
            $front = str_replace('\\', '/', $data['front']);
            $back = str_replace('\\', '/', $data['back']);
            if ($data['guide']) {
                $guide = str_replace('\\', '/', $data['guide']);
            } else {
                $guide = '';
            }
            return view('', compact('title', 'data', 'front', 'back', 'guide'));
        }
    }

    public function delete($uid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            if (UserModel::destroy($uid)) {

                $this->redirect('admin/User/index');

            } else {
                $this->error('删除失败');
            }
        }
    }

    /**
     * 搜索用户
     * @author zhaoyuzhi
     * */
    public function searchUser()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $keywords = input('post.keywords');
            $user = Db::name('user')->field('uid')->where('uid', $keywords)->whereOr('username', $keywords)->select();
            if (empty($user)) {
                $this->errr('没有搜索到该用户');
            }
            $uid = $user[0]['uid'];
            //一页中有几个数据
            if ($uid) {
                $page = ceil($uid / 10);
                echo '<script>location.href="/admin/user/index.html?page=' . $page . '";</script>';
            } else {
                $this->error('没有搜索到该用户');
            }

        }

    }

    /**
     * 恢复已删除用户
     * @author zhaouzhi
     */
    public function resetUser($uid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $user = UserModel::withTrashed()->find($uid);
            $user->delete_time = '0000-00-00 00:00:00';
            $result = $user->save();
            if ($result) {
                $this->redirect('admin/user/index');
            } else {
                $this->error('恢复失败');
            }
        }

    }

    public function count()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $this->count = Db::name('user')->count();

            $count = $this->count;

            return View('', compact('count'));
        }
    }

    public function login()
    {
        if (empty(Session::get('admin'))) {
            $a1 = Db::name('user')->where('uid', 1)->select();
            $a2 = Db::name('user')->where('uid', 2)->select();
            if ($a1 && $a2) {
                $this->assign('title', '登录后台');
                $this->assign('url', 'signin');
                return $this->fetch();
            } else {
                $this->assign('title', '管理员注册');
                $this->assign('url', 'signup');
                return $this->fetch();
            }

        } else {
            return $this->error('你已经登录过了！', 'index/index');
        }
    }

    public function signin()
    {
        if (empty(Session::get('admin'))) {
            $result = Db::name('user')->field('uid,username')->where(['is_admin' => 1, 'username' => input('post.u'), 'password' => md5(input('post.p'))])->select();
            if ($result) {
                Session::set('admin', $result[0]['uid']);
                return $this->redirect('index/index');
            } else {
                return $this->error('你好像忘记了什么吧', 'user/login');
            }
        } else {
            return $this->error('你已经登录过了！', 'index/index');
        }
    }

    public function signup()
    {
        if (empty(Session::get('admin'))) {
            $a1 = Db::name('user')->where('uid', 1)->select();
            $a2 = Db::name('user')->where('uid', 2)->select();
            if ($a1 && $a2) {
                $this->assign('title', '登录后台');
                $this->assign('url', 'signin');
                return $this->fetch();
            } else {
                $res = Db::name('user')->insert(['username' => input('post.u'),
                    'password' => md5(input('post.p')),
                    'is_admin' => 1,
                    'email' => input('post.u') . '@zql.zcsttation.cn',
                    'create_ip' => Request::instance()->ip(),
                    'last_ip' => Request::instance()->ip(),
                    'active_code' => 1
                ]);
                if ($res) {
                    return $this->redirect('index/index');
                } else {
                    return $this->error('出错了……', 'user/login');
                }
            }

        } else {
            return $this->error('你已经登录过了！', 'index/index');
        }
    }

    public function detail($uid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $title = '后台中心';
            $user = Db::name('user')->where('uid', $uid)->select();
            $userdata = $user[0];
            //参团
            if (empty($userdata['joined'])) {
                $joinnum = 0;
            } else {
                $joinnum = count(explode(',', trim($userdata['joined'], ',')));
            }
            //组团数
            $peernum = Db::name('peers')->where('user_id', $uid)->count();
            //粉丝数
            $fansarr = json_decode($userdata['fans'], true);
            $fansnum = count($fansarr);
            //关注好友的个数
            $follownum = count(json_decode($userdata['follow_uid'], true));
            //好友的个数
            $friendsnum = count(json_decode($userdata['friends'], true));
            //收藏的文章，
            $keeparr = explode(',', trim($userdata['keep_article'], ','));
            if (count($keeparr) == 1 && $keeparr[0] == '') {
                $keepnum = 0;
            } else {
                $keepnum = count($keeparr);
            }

            //查找用户发文
            $article = Db::name('article')->where('uid', $uid)->select();
            $articlenum = count($article);
            //查看该用户的发布的问题
            $askdata = Db::name('ask')->where('user_id', $uid)->select();
            $asknum = count($askdata);
            //查看回答问题的数量
            $answernum = Db::name('answer')->where('user_id', $uid)->select();
            $answernum = count($answernum);
            return View('', compact('title', 'userdata', 'fansnum', 'follownum', 'friendsnum', 'articlenum',
                'article', 'keepnum', 'joinnum', 'peernum', 'answernum', 'asknum', 'askdata'));
        }
    }


    /*同意导游申请*/
    public function guideagree($id, $uid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $result1 = Db::name('user')
                ->where('uid', $uid)
                ->setField('is_guide', 1);
            $result2 = Db::name('apply')->where('id', $id)->update(['status' => 1]);
            if ($result1 && $result2) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }

    }

    /*驳回导游申请*/
    public function guidereject($id, $uid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $result = Db::name('apply')->where('id', $id)->update(['status' => 2]);
            if ($result) {
                $this->success('已成功驳回');
            } else {
                $this->error('失误，请重试');
            }
        }
    }

    /*用户查询*/
    public function select()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $time = time();

            $starttime = $time - 3600 * 24 * 7;

            $weekNum = Db::name('user')->where('create_time', '>', $starttime)->field('uid,create_time')->select();
            //$weekNum = Db::name('user')->whereTime('create_time','week')->select();
            //dump($weekNum);
            $dataArr = [];
            $one = 0;
            $two = 0;
            $three = 0;
            $four = 0;
            $five = 0;
            $six = 0;
            $seven = 0;
            $onetime = date('Y/m/d', time());
            $twotime = date('Y/m/d', strtotime('-1 day'));
            $threetime = date('Y/m/d', strtotime('-2 day'));
            $fourtime = date('Y/m/d', strtotime('-3 day'));
            $fivetime = date('Y/m/d', strtotime('-4 day'));
            $sixtime = date('Y/m/d', strtotime('-5 day'));
            $seventime = date('Y/m/d', strtotime('-6 day'));
            $datatime = [$seventime, $sixtime, $fivetime, $fourtime, $threetime, $twotime, $onetime];

            foreach ($weekNum as $data) {

                $dataTime = strtotime($data['create_time']);

                $nowTime = ceil((time() - $dataTime) / (3600 * 24));

                switch ($nowTime) {

                    case 1:
                        $one++;
                        break;
                    case 2:
                        $two++;
                        break;
                    case 3:
                        $three++;
                        break;
                    case 4:
                        $four++;
                        break;
                    case 5:
                        $five++;
                        break;
                    case 6:
                        $six++;
                        break;
                    case 7:
                        $seven++;
                        break;
                }
            }
            //$dataArr = ['series','xAxis'];
            $data = [$seven, $six, $five, $four, $three, $two, $one];
            $dataArr = [
                'series' => [
                    'data' => $data,
                    'name' => '用户数量',
                    'type' => 'line',
                ],
                'aXias' => [
                    'type' => 'category',
                    'data' => $datatime,
                ]
            ];
            //List<String> legend = new ArrayList<String>(Arrays.asList(new String[] { "销量"}));
            echo json_encode($dataArr);
        }
    }

    /*用户类型查询*/
    public function styleselect()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $data = [];

            $guideNum = Db::name('user')->where('is_guide', 1)->count();

            $normal = Db::name('user')->where('is_guide', 0)->count();

            $data = [

                [
                    'value' => $guideNum,
                    'name' => '导游',
                ],
                [
                    'value' => $normal,
                    'name' => '普通游客',
                ],
            ];
            echo json_encode($data);
        }
    }
}













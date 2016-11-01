<?php
namespace app\index\controller;

use think\Controller;
use think\Loader;
use think\Db;
use think\Request;
use app\index\model\User as UserModel;
use think\Session;

class User extends Controller
{

    /**
     * 用户修改密码
     * @author ZhangChao
     */
    public function repasswd()
    {
        if (empty(Session('?uid'))) {
            $this->redirect('index/index');
        } else {
            $pwd = $_POST['password'];
            $repwd1 = $_POST['repassword1'];
            $repwd2 = $_POST['repassword2'];
            if (md5($pwd) != Session('user')['password']) {
                $this->error('原密码输入错误！', $_SERVER['HTTP_REFERER']);
            }
            if ($repwd1 != $repwd2) {
                $this->error('两次密码不一致！', $_SERVER['HTTP_REFERER']);
            } else {
                Db::name('user')->where('uid', Session('uid'))->update(['password' => md5($repwd1)]);
                $this->success('密码修改成功，请重新登录！', 'user/logout');
            }
        }
    }

    /**
     * 通过ajax修改基本信息
     * @author ZhangChao
     */
    public function ajaxBase()
    {

        $data['sex'] = $_POST['sex'];
        $data['birthday'] = $_POST['year'] . '/' . $_POST['month'] . '/' . $_POST['day'];
        $data['city'] = $_POST['city'];
        $data['motto'] = strip_tags($_POST['motto']);
        $data['city_no'] = $_POST['city_no'];

        if (empty($data['birthday']) || empty($data['city']) || empty($data['motto'])) {
            $arr = array(
                "code" => 1,
                "msg" => '所有修改信息不能为空！'
            );
        } else if (!Db::name('user')->where(['uid' => Session('uid')])
            ->update(['motto' => $data['motto'],
                'sex' => $data['sex'],
                'birthday' => $data['birthday'],
                'city' => $data['city'],
                'city_no' => $data['city_no']])
        ) {
            $arr = array(
                "code" => 1,
                "msg" => '信息修改失败，请稍后重试！'
            );
        } else {
            $arr = array(
                'code' => 0,
                'msg' => '信息修改成功，下次登录时生效!'
            );
        }

        echo json_encode($arr);
    }

    /**
     * ajax验证注册
     * @author ZhangChao
     */
    public function ajax()
    {
        $captcha = new \think\captcha\Captcha();

        $data['username'] = $_POST['username'];
        $data['password'] = $_POST['password'];
        $data['repassword'] = $_POST['repassword'];
        $data['email'] = $_POST['email'];
        $data['code'] = trim($_POST['code']);

        $user = Db::name('user')->where('username', $data['username'])->field('uid')->select();
        $email = Db::name('user')->where('email', $data['email'])->field('uid')->select();

        $validate = Loader::validate('User');

        if (!$validate->check($data)) {
            $arr = array(
                "code" => 1,
                "msg" => $validate->getError()
            );
        } else if (!$captcha->check($_POST['code'], '', false)) {
            $arr = array(
                "code" => 1,
                "msg" => '验证码错误!'
            );
        } else if ($user) {
            $arr = array(
                "code" => 1,
                "msg" => '用户已存在!'
            );
        } else if ($email) {
            $arr = array(
                "code" => 1,
                "msg" => '邮箱已注册!'
            );
        } else {
            $arr = array(
                'code' => 0,
                'msg' => 'No error!'
            );
        }

        echo json_encode($arr);
    }

    /**
     * ajax删除好友、粉丝、取消关注
     * @author ZhangChao
     */
    public function ajaxData()
    {

        switch (input('get.type')) {
            case 'FRI':
                // 好友

                $delete = $_POST['friends'];

                $all = json_decode(input('post.oldFriends'), true);

                $friends = array_diff($all, $delete);

                $result = Db::name('user')->where('uid', (int)input('post.userId'))->update(['friends' => json_encode($friends)]);

                if ($result) {
                    Session::set('user.friends', json_encode($friends));
                    $arr = array(
                        'code' => 0,
                        'msg' => '删除好友成功，下次登录生效！'
                    );
                } else {
                    $arr = array(
                        'code' => 1,
                        'msg' => '删除失败，稍后重试！'
                    );
                }
                break;
            case 'FAN':
                // 好友

                $delete = $_POST['friends'];

                $all = json_decode(input('post.oldFriends'), true);

                $friends = array_diff($all, $delete);

                $result = Db::name('user')->where('uid', (int)input('post.userId'))->update(['fans' => json_encode($friends)]);

                if ($result) {
                    Session::set('user.fans', json_encode($friends));
                    $arr = array(
                        'code' => 0,
                        'msg' => '成功删除粉丝，请刷新页面！'
                    );
                } else {
                    $arr = array(
                        'code' => 1,
                        'msg' => '删除失败，稍后重试！'
                    );
                }
                break;
            case 'FOL':
                // 好友

                $delete = $_POST['friends'];

                $all = json_decode(input('post.oldFriends'), true);

                $friends = array_diff($all, $delete);

                $result = Db::name('user')->where('uid', (int)input('post.userId'))->update(['follow_uid' => json_encode($friends)]);
                $i = 0;
                foreach ($delete as $uid => $name) {
                    $fans = Db::name('user')->where('uid', $uid)->field('fans')->select();

                    $arr = json_decode($fans[0]['fans'], true);

                    unset($arr[input('post.userId')]);

                    if (empty($arr))
                        $arr[1] = '反馈中心';

                    if (Db::name('user')->where('uid', $uid)->update(['fans' => json_encode($arr)]))
                        $i++;
                }
                if ($result && ($i == count($delete))) {
                    Session::set('user.follow_uid', json_encode($friends));
                    $arr = array(
                        'code' => 0,
                        'msg' => '成功取消关注，请刷新页面！'
                    );
                } else {
                    $arr = array(
                        'code' => 1,
                        'msg' => '删除失败，稍后重试！'
                    );
                }
                break;
            case 'CITY':
                // 同城
                break;
            default:
                break;
        }

        echo json_encode($arr);
    }

    /**
     * ajax验证登录
     * @author ZhangChao
     */
    public function ajaxLogin()
    {
        $captcha = new \think\captcha\Captcha();

        $data['username'] = $_POST['username'];
        $data['password'] = md5($_POST['password']);
        $data['code'] = trim($_POST['code']);

        $user = Db::name('user')
            ->where(['username' => $data['username'], 'password' => $data['password']])
            ->field('uid')->select();

        $email = Db::name('user')
            ->where(['email' => $data['username'], 'password' => $data['password']])
            ->field('uid')->select();

        if (!$user && !$email) {
            $arr = array(
                "code" => 1,
                "msg" => '用户名或密码不正确！'
            );
        } else if (!$captcha->check($_POST['code'], '', false)) {
            $arr = array(
                "code" => 1,
                "msg" => '验证码错误!'
            );
        } else {
            $arr = array(
                'code' => 0,
                'msg' => 'No error!'
            );
        }

        echo json_encode($arr);
    }

    /**
     * 登录页
     * @author ZhangChao
     */
    public function signin()
    {
        if (empty(Session('?uid'))) {

            $this->assign('title', '登录到竹签录 | 让心灵去旅行');

            return $this->fetch('user/signin');

        } else {

            $this->redirect('index/index');
        }

    }

    /**
     * 注册页
     * @author ZhangChao
     */
    public function signup()
    {
        if (empty(Session('?uid'))) {

            $this->assign('title', '注册竹签录 | 让心灵去旅行');

            return $this->fetch('user/signup');

        } else {

            $this->redirect('index/index');

        }

    }

    /**
     * 注销登录
     * @author ZhangChao
     */
    public function logout()
    {

        if (!empty(Session('?uid'))) {

            Session(null);

        }

        $this->redirect('index/index');

    }

    /**
     * 登录验证
     * @author ZhangChao
     */
    public function login()
    {
        // 检测输入信息的正确性代码
        if (!empty(Session('?uid'))) {

            return $this->redirect('index/index');

        } else {

            $captcha = new \think\captcha\Captcha();

            $data['username'] = $_POST['username'];
            $data['password'] = md5($_POST['password']);
            $data['code'] = trim($_POST['code']);

            $user = Db::name('user')->where(['username' => $data['username'], 'password' => $data['password']])->select();

            $email = Db::name('user')->where(['email' => $data['username'], 'password' => $data['password']])->select();

            if (!$user && !$email) {
                $arr = array(
                    "code" => 1,
                    "msg" => '用户名或密码不正确！'
                );
            } else if (!$captcha->check($_POST['code'], '', false)) {
                $arr = array(
                    "code" => 1,
                    "msg" => '验证码错误!'
                );
            } else {
                $arr = array(
                    'code' => 0,
                    'msg' => 'No error!'
                );
            }

            if ($arr['code']) {

                return $this->error($arr['msg'], 'user/signin');

            } else {
                if (empty($user)) {
                    if ($email[0]['is_active']) {
                        Db::name('user')->where('uid', $email[0]['uid'])->update(['update_time' => date('Y-m-d H:i:s'),
                            'last_ip' => Request::instance()->ip()]);
                        Session('user', $email[0]);
                        Session('uid', $email[0]['uid']);
                        $tips = '登录成功！正在跳转……';
                        return $this->success($tips, 'index/index');
                    } else {
                        $tips = '请先激活账户再登录！';
                        return $this->error($tips, 'index/index');
                    }
                } else {
                    if ($user[0]['is_active']) {
                        Db::name('user')->where('uid', $user[0]['uid'])->update(['update_time' => date('Y-m-d H:i:s'),
                            'last_ip' => Request::instance()->ip()]);
                        Session('user', $user[0]);
                        Session('uid', $user[0]['uid']);
                        $tips = '登录成功！正在跳转……';
                        return $this->success($tips, 'index/index');
                    } else {
                        $tips = '请先激活账户再登录！';
                        return $this->error($tips, 'index/index');
                    }
                }


            }
        }


    }

    /**
     * 注册验证
     * @author ZhangChao
     */
    public function register()
    {

        // 检测注册信息正确性代码
        $captcha = new \think\captcha\Captcha();

        $data['username'] = $_POST['username'];
        $data['password'] = $_POST['password'];
        $data['repassword'] = $_POST['repassword'];
        $data['email'] = $_POST['email'];
        $data['code'] = trim($_POST['code']);

        $user = Db::name('user')->where('username', $data['username'])->field('uid')->select();
        $email = Db::name('user')->where('email', $data['email'])->field('uid')->select();

        $validate = Loader::validate('User');

        if (!$validate->check($data)) {
            $arr = array(
                "code" => 1,
                "msg" => $validate->getError()
            );
        } else if (!$captcha->check($_POST['code'], '', false)) {
            $arr = array(
                "code" => 1,
                "msg" => '验证码错误!'
            );
        } else if ($user) {
            $arr = array(
                "code" => 1,
                "msg" => '用户已存在!'
            );
        } else if ($email) {
            $arr = array(
                "code" => 1,
                "msg" => '邮箱已注册!'
            );
        } else {
            $arr = array(
                'code' => 0,
                'msg' => 'No error!'
            );
        }

        if ($arr['code']) {

            return $this->error($arr['msg'], 'user/signup');

        }


        $activeCode = md5($data['email'] . time());

        $user = new UserModel();

        $user->username = $data['username'];
        $user->password = $data['password'];
        $user->email = $data['email'];
        $user->create_ip = Request::instance()->ip();
        $user->last_ip = Request::instance()->ip();
        $user->active_code = $activeCode;

        $link = Request::instance()->domain() . '/index/user/active.html?code=' . $activeCode;

        $result = $this->sendMail($data['email'], '竹签录用户' . $data['username'] . '注册激活邮件',
            '点击链接<a href="' . $link . '">激活账户</a><br />或者复制该地址 ' . $link . ' 到浏览器打开！');

        if ($user->save() && !$result) {
            $this->success('激活邮件已发送，请激活后登录！', 'user/signin');
        } else {
            $this->error('激活邮件发送失败，请检查你的邮箱是否有效！', 'user/signup');
        }
        //return $this->redirect('user/signin');
    }

    /**
     * 发送邮件
     * @author ZhangChao
     * @param $to       string 发送给谁
     * @param $subject  string 邮件主题
     * @param $body     string 邮件主体内容
     * @param $name     string 显示在邮件上的发件人名称
     */
    protected function sendMail($to, $subject = 'No subject', $body, $name = '竹签录')
    {
        $loc_host = 'http://zql.zcstation.cn'; //发信计算机名，可随意
        $smtp_acc = 'no-reply@zcstation.win'; //Smtp认证的用户名，类似fish1240@fishcat.com.cn，或者fish1240
        $smtp_pass = 'ociIMHLyoT'; //Smtp认证的密码，一般等同pop3密码
        $smtp_host = 'smtp.ym.163.com'; //SMTP服务器地址，类似 smtp.tom.com
        $from = 'no-reply@zcstation.win'; //发信人Email地址，你的发信信箱地址
        $name = $name;

        $headers = "Content-Type: text/html; charset=\"utf-8\"\r\nContent-Transfer-Encoding: base64";
        $lb = "\r\n"; //linebreak

        $hdr = explode($lb, $headers); //解析后的hdr
        if ($body) {
            $bdy = preg_replace("/^\./", "..", explode($lb, $body));
        }//解析后的Body

        $smtp = array(
            //1、EHLO，期待返回220或者250
            array("EHLO " . $loc_host . $lb, "220,250", "HELO error: "),
            //2、发送Auth Login，期待返回334
            array("AUTH LOGIN" . $lb, "334", "AUTH error:"),
            //3、发送经过Base64编码的用户名，期待返回334
            array(base64_encode($smtp_acc) . $lb, "334", "AUTHENTIFICATION error : "),
            //4、发送经过Base64编码的密码，期待返回235
            array(base64_encode($smtp_pass) . $lb, "235", "AUTHENTIFICATION error : "));
        //5、发送Mail From，期待返回250
        $smtp[] = array("MAIL FROM: <" . $from . ">" . $lb, "250", "MAIL FROM error: ");
        //6、发送Rcpt To。期待返回250
        $smtp[] = array("RCPT TO: <" . $to . ">" . $lb, "250", "RCPT TO error: ");
        //7、发送DATA，期待返回354
        $smtp[] = array("DATA" . $lb, "354", "DATA error: ");
        //8.0、发送From
        $smtp[] = array("From: " . $name . "<" . $from . '>' . $lb, "", "");
        //8.2、发送To
        $smtp[] = array("To: " . $to . $lb, "", "");
        //8.1、发送标题
        $smtp[] = array("Subject: " . $subject . $lb, "", "");
        //8.3、发送其他Header内容
        foreach ($hdr as $h) {
            $smtp[] = array($h . $lb, "", "");
        }
        //8.4、发送一个空行，结束Header发送
        $smtp[] = array($lb, "", "");
        //8.5、发送信件主体
        if ($bdy) {
            foreach ($bdy as $b) {
                $smtp[] = array(base64_encode($b . $lb) . $lb, "", "");
            }
        }
        //9、发送“.”表示信件结束，期待返回250
        $smtp[] = array("." . $lb, "250", "DATA(end)error: ");
        //10、发送Quit，退出，期待返回221
        $smtp[] = array("QUIT" . $lb, "221", "QUIT error: ");

        //打开smtp服务器端口
        $fp = @fsockopen($smtp_host, 25);
        if (!$fp) echo "<b>Error:</b> Cannot conect to " . $smtp_host . "<br>";
        while ($result = @fgets($fp, 1024)) {
            if (substr($result, 3, 1) == " ") {
                break;
            }
        }

        $result_str = "";
        //发送smtp数组中的命令/数据
        foreach ($smtp as $req) {
            //发送信息
            @fputs($fp, $req[0]);
            //如果需要接收服务器返回信息，则
            if ($req[1]) {
                //接收信息
                while ($result = @fgets($fp, 1024)) {
                    if (substr($result, 3, 1) == " ") {
                        break;
                    }
                };
                if (!strstr($req[1], substr($result, 0, 3))) {
                    $result_str .= $req[2] . $result . "<br>";
                }
            }
        }
        //关闭连接
        @fclose($fp);

        return $result_str;
    }

    /**
     * 个人信息页
     * @author ZhangChao
     */
    public function profile()
    {
        if (!empty(Session('?uid'))) {
            $friend = json_decode(Session('user')['friends'], true);
            $follows = json_decode(Session('user.follow_uid'), true);
            $fan = json_decode(Session::get('user.fans'), true);
            $apply = json_decode(Session::get('user.f_apply'), true);
            $apply1 = json_decode(Session::get('user.applyed'), true);
            if (empty(Session('user.joined')))
                $peers1 = array();
            else
                $peers1 = explode(',',trim(Session('user.joined'), ','));

            if (empty(Session::get('user.keep_article')))
                $keep1 = array();
            else
                $keep1 = explode(',', rtrim(Session::get('user.keep_article'), ','));
            if (empty(Session::get('user.follow_ask')))
                $ask1 = array();
            else
                $ask1 = explode(',', trim(Session::get('user.follow_ask'), ','));

            $fanNum = count($fan);
            $followNum = count($follows);
            $num = count($friend);
            $applyNum = count($apply);
            $applyedNum = count($apply1);
            $keepNum = count($keep1);
            $askNum = count($ask1);
            $peersNum = count($peers1);

            $guide1 = Db::name('apply')->where('uid', Session::get('uid'))->select();

            $peers = '';
            if (!empty($peers1)) {
                foreach ($peers1 as $id) {
                    $a = Db::name('peers')->where('pid', $id)->select();
                    $peers .= '<div style="margin-bottom: 10px;padding-left: 20px;border-bottom: 1px dashed #3ef;">'
                        .'<p><a href="__STATIC__/index/community/detail/id/'.$a[0]['pid'].'.html">'.$a[0]['title'].'</a></p><p>发表时间：'
                        .$a[0]['create_time'].' 出发时间：'.$a[0]['start_time'].' 出发地：'.$a[0]['origin'].' 目的地：'.$a[0]['destination'].($a[0]['status'] == 1 ? ' 已过期' : ' <b style="color: #3f4;">进行中……</b>').'</p></div>';
                }
            }

            if (empty($guide1)) {
                $guideNum = 0;
                $guide = '<p class="text-info" style="padding-left: 20px;">你没有导游的申请记录，如果你有时间有热情，可以请申请导游赚点外快哦……</p>';
            } else if (0 == $guide1[0]['status']) {
                $guideNum = 1;
                $guide = '<p class="text-warning" style="padding-left: 20px;">你的导游申请正在处理中，请稍等！</p>';
            } else if (2 == $guide1[0]['status']) {
                $guideNum = 1;
                $guide = '<p class="text-error" style="padding-left: 20px;">你的导游申请未能通过审核，请手持身份证拍摄，并保证证件上的字迹清晰可见，感谢您的配合！</p>';
            } else {
                $guideNum = 0;
                $guide = '<p class="text-success" style="padding-left: 20px;">你的导游申请已经通过审核，请好好对待这份职业！</p>';
            }

            $ask = '';
            if (!empty($ask1)) {
                foreach ($ask1 as $id) {
                    $a = Db::name('ask')->where('askid', $id)->select();
                    $ask .= '<div style="margin-bottom: 10px;padding-left: 10px;border-bottom: 1px dashed #f3f;"><a style="font-size: 16px;" href="' . Request::instance()->domain() . '/index/index/detail/askid/' . $id . '.html">' . $a[0]['title']
                        . '</a><p>浏览量：'.$a[0]['scan'].'</a>&nbsp;发表时间：'.$a[0]['create_time'].' 目的地：'.$a[0]['keywords'].'</p></div>';
                }
            }
            $keep = '';
            if (!empty($keep1)) {
                foreach ($keep1 as $id) {
                    $a = Db::name('article')->where('id', $id)->select();
                    $keep .= '<div style="margin-bottom: 10px;padding-left: 10px;border-bottom: 1px dashed #f3f;"><a style="font-size: 16px;" href="' . Request::instance()->domain() . '/index/article/detail/id/' . $id . '.html">' . $a[0]['title']
                        . '</a>'.($a[0]['delete_time'] == '0000-00-00 00:00:00' ? '' : ' <b style="font-size: 12px; color: #f34;">文章已删除</b>') .'
                        <p>作者：<a href="/index/user/detail/u/'.$a[0]['uid'].'.html">'.$a[0]['author'].'</a>&nbsp;发表时间：'.$a[0]['create_time'].' 目的地：'.$a[0]['destination'].'</p></div>';
                }
            }
            $allApplyed = '';
            if (!empty($apply1)) {
                foreach ($apply1 as $uid => $name) {
                    $avatar = Db::name('user')->where('uid', $uid)->select();
                    $allApplyed .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                        . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                        . '</a></span></div>';
                }
            }
            $allApply = '';
            if (!empty($apply)) {
                foreach ($apply as $uid => $name) {
                    $avatar = Db::name('user')->where('uid', $uid)->select();
                    $allApply .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                        . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                        . '</a> <input type="checkbox" name="friend[]" value="' . $avatar[0]['uid'] . '" /><input type="hidden" name="names[]" value="' . $avatar[0]['username'] . '" /></span></div>';
                }
            }
            $fans = '';
            foreach ($fan as $uid => $name) {
                $avatar = Db::name('user')->field('avatar')->where('uid', $uid)->select();
                $fans .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                    . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                    . '</a> <input type="checkbox" value="' . $uid . '" /><input type="hidden" value="' . $name . '" /></span></div>';
            }
            $follow = '';
            foreach ($follows as $uid => $name) {
                $avatar = Db::name('user')->field('avatar')->where('uid', $uid)->select();
                $follow .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                    . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                    . '</a> <input type="checkbox" value="' . $uid . '" /><input type="hidden" value="' . $name . '" /></span></div>';

            }
            $friends = '';
            foreach ($friend as $uid => $name) {
                $avatar = Db::name('user')->field('avatar')->where('uid', $uid)->select();
                if (1 == $uid) {
                    $friends .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                        . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                        . '</a> <input type="checkbox" value="' . $uid . '" disabled /><input type="hidden" value="' . $name . '" /></span></div>';

                } else {

                    $friends .= '<div class="col-md-3" style="margin-bottom: 10px;"><img src="/' . $avatar[0]['avatar']
                        . '_32x32.jpg" /> <span><a href="' . Request::instance()->domain() . '/index/user/detail/u/' . $uid . '">' . $name
                        . '</a> <input type="checkbox" value="' . $uid . '" /><input type="hidden" value="' . $name . '" /></span></div>';
                }

            }
            $this->assign('title', '个人中心');
            $this->assign('username', '凭栏知潇雨');
            $this->assign('email', '1415855827@qq.com');
            $this->assign('username', Session('user')['username']);
            $this->assign('email', Session('user')['email']);
            $this->assign('year', explode('/', Session('user')['birthday'])[0]);
            $this->assign('month', explode('/', Session('user')['birthday'])[1]);
            $this->assign('day', explode('/', Session('user')['birthday'])[2]);
            $this->assign('motto', Session('user')['motto']);
            $this->assign('friends', $friends);
            $this->assign('follow', $follow);
            $this->assign('fans', $fans);
            $this->assign('keepNum', $keepNum);
            $this->assign('keep', $keep);
            $this->assign('askNum', $askNum);
            $this->assign('ask', $ask);
            $this->assign('fanNum', $fanNum);
            $this->assign('followNum', $followNum);
            $this->assign('num', $num);
            $this->assign('applyedNum', $applyedNum);
            $this->assign('applyed', $allApplyed);
            $this->assign('applyNum', $applyNum);
            $this->assign('apply', $allApply);
            $this->assign('guide', $guide);
            $this->assign('guideNum', $guideNum);
            $this->assign('peers', $peers);
            $this->assign('peersNum', $peersNum);
            $this->assign('allFriends', Session('user')['friends']);
            $this->assign('allFans', Session('user')['fans']);
            $this->assign('allFollows', Session('user')['follow_uid']);
            $this->assign('uid', Session('uid'));

            if (6 == strlen(Session('user')['city_no'])) {
                $this->assign('pro', (int)(explode('/', chunk_split(Session('user')['city_no'], 2, '/'))[0]));
                $this->assign('city', (int)(explode('/', chunk_split(Session('user')['city_no'], 4, '/'))[0]));
                $this->assign('area', (int)(explode('/', chunk_split(Session('user')['city_no'], 6, '/'))[0]));
            } else if (4 == strlen(Session('user')['city_no'])) {
                $this->assign('pro', (int)(explode('/', chunk_split(Session('user')['city_no'], 2, '/'))[0]));
                $this->assign('city', (int)(explode('/', chunk_split(Session('user')['city_no'], 4, '/'))[0]));
                $this->assign('area', 0);
            } else {
                $this->assign('pro', (int)(explode('/', chunk_split(Session('user')['city_no'], 2, '/'))[0]));
                $this->assign('city', 0);
                $this->assign('area', 0);
            }
            if (Session('user')['sex'])
                $sex = 'selected';
            else
                $sex = false;

            $this->assign('sex', $sex);

            return $this->fetch();
        }

        $this->redirect('index/index');
    }

    /**
     * 修改头像
     * @author ZhangChao
     */
    public function crop()
    {
        $this->assign('avatar', Session('user')['avatar']);
        return $this->fetch('user/avatar');
    }

    /**
     * 确认裁剪并生成新的头像
     * @author ZhangChao
     */
    public function avatar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $targ_w = $targ_h = 32;
            $targ_w1 = $targ_h1 = 128;

            $jpeg_quality = 90;

            // 用户上传后的文件路径
            $src = $_POST['filename'];

            switch (pathinfo($src)['extension']) {

                case 'png':

                    $img_r = imagecreatefrompng($src);

                    $img_r1 = imagecreatefrompng($src);

                    break;

                case 'jpg':

                    $img_r = imagecreatefromjpeg($src);

                    $img_r1 = imagecreatefromjpeg($src);

                    break;

                case 'gif':

                    $img_r = imagecreatefromgif($src);

                    $img_r1 = imagecreatefromgif($src);

                    break;

                case 'bmp':

                    $img_r = imagecreatefromwbmp($src);

                    $img_r1 = imagecreatefromwbmp($src);

                    break;

                case 'jpeg':

                    $img_r = imagecreatefromjpeg($src);

                    $img_r1 = imagecreatefromjpeg($src);

                    break;

                default:
                    exit('文件类型不允许！');
            }

            $dst_r = imageCreateTrueColor($targ_w, $targ_h);
            $dst_r1 = imageCreateTrueColor($targ_w1, $targ_h1);

            imagecopyresampled($dst_r, $img_r, 0, 0, $_POST['x'], $_POST['y'],
                $targ_w, $targ_h, $_POST['w'], $_POST['h']);

            imagecopyresampled($dst_r1, $img_r1, 0, 0, $_POST['x'], $_POST['y'],
                $targ_w1, $targ_h1, $_POST['w'], $_POST['h']);

            header('Content-type: image/jpeg');
            imagejpeg($dst_r, 'static/upload/avatar/' . Session('uid') . '_32x32.jpg', $jpeg_quality);
            imagejpeg($dst_r1, 'static/upload/avatar/' . Session('uid') . '_128x128.jpg', $jpeg_quality);

            if (Db::name('user')->where('uid', Session('uid'))->update(['avatar' => 'static/upload/avatar/' . Session('uid')])) {
                return '修改成功！';
            } else {
                return '修改失败！';
            }
        }
    }

    /**
     * 验证邮箱激活
     * @author ZhangChao
     */
    public function active()
    {
        $code = input('get.code');

        $result = Db::name('user')->where('active_code', $code)->field('uid,username')->select();

        if ($result) {
            $friend = Db::name('user')->where('uid', 1)->field('friends')->select();
            $friends = json_decode($friend[0]['friends'], true);
            $friends[$result[0]['uid']] = $result[0]['username'];
            Db::name('user')->where('uid', 1)->update(['friends' => json_encode($friends)]);
            Db::name('user')->where('active_code', $code)->update(['is_active' => 1, 'active_code' => '1']);
            copy('static/upload/avatar/1_32x32.jpg', 'static/upload/avatar/' . $result[0]['uid'] . '_32x32.jpg');
            copy('static/upload/avatar/1_128x128.jpg', 'static/upload/avatar/' . $result[0]['uid'] . '_128x128.jpg');
            return $this->success('账户激活成功，请登录！', 'user/signin');
        } else {
            return $this->error('不存在的账户或账户已激活！', 'index/index');
        }
    }

    /**
     * 用户个人主页
     * @author ZhangChao
     */
    public function detail()
    {
        $result = Db::name('user')->where('uid', input('param.u'))->field('uid,is_guide,f_apply,follow_uid,fans,motto,username,avatar,sex,level,city')->select();
        if (Session('uid') == input('param.u'))
            $article = Db::name('article')->where('uid', input('param.u'))->order('id desc')->select();
        else
            $article = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('uid', input('param.u'))->order('id desc')->field('id,title,create_time,great,uid,author,content')->select();

        $ask = Db::name('ask')->where('user_id', Session::get('uid'))->field('askid,title,create_time,content')->order('askid desc')->select();

        $this->assign('title', $result[0]['username'] . '的主页');
        $this->assign('username', $result[0]['username']);
        $this->assign('level', $result[0]['level']);
        $this->assign('avatar', $result[0]['avatar']);
        $this->assign('sex', $result[0]['sex']);
        $this->assign('motto', $result[0]['motto']);
        $this->assign('guide', $result[0]['is_guide']);
        $this->assign('follow', $result[0]['follow_uid']);
        $this->assign('fans', $result[0]['fans']);
        $this->assign('uid', $result[0]['uid']);
        $this->assign('apply', $result[0]['f_apply']);
        $this->assign('local', explode(' ', $result[0]['city'])[0]);
        $this->assign('articleData', $article);
        $this->assign('askData', $ask);
        return $this->fetch();
    }

    /**
     * 关注好友
     * @author ZhangChao
     */
    public function follow()
    {
        if ('c' == input('param.a')) {
            if (!empty(Session('uid'))) {
                $result = Db::name('user')->where('uid', input('get.u'))->field('fans')->select();
                if ($result) {
                    $arr = json_decode(Session('user.follow_uid'), true);
                    unset($arr[input('get.u')]);
                    if (empty($arr))
                        $arr[1] = '反馈中心';
                    $status = Db::name('user')->where('uid', Session('uid'))->update(['follow_uid' => json_encode($arr)]);
                    $fFans = json_decode($result[0]['fans'], true);
                    unset($fFans[Session('uid')]);
                    if (empty($fFans))
                        $fFans[1] = '反馈中心';
                    $status1 = Db::name('user')->where('uid', input('get.u'))->update(['fans' => json_encode($fFans)]);
                    if ($status || $status1) {
                        Session('user.follow_uid', json_encode($arr));
                        return $this->success('成功取消关注！');
                    } else {
                        return $this->error('你已经取消关注！');
                    }
                } else {
                    return $this->error('不存在的用户！');
                }
            } else {
                return $this->error('请登录！', 'user/signin');
            }
        } else {
            if (!empty(Session('uid'))) {
                $result = Db::name('user')->where('uid', input('get.u'))->field('fans')->select();
                if ($result) {
                    $arr = json_decode(Session('user.follow_uid'), true);
                    $arr[input('get.u')] = input('get.name');
                    $status = Db::name('user')->where('uid', Session('uid'))->update(['follow_uid' => json_encode($arr)]);
                    $fFans = json_decode($result[0]['fans'], true);
                    $fFans[Session('uid')] = Session('user')['username'];
                    $status1 = Db::name('user')->where('uid', input('get.u'))->update(['fans' => json_encode($fFans)]);
                    if ($status || $status1) {
                        Session('user.follow_uid', json_encode($arr));
                        return $this->success('关注好友成功！');
                    } else {
                        return $this->error('你已经关注过该好友！');
                    }
                } else {
                    return $this->error('不存在的用户！');
                }
            } else {
                return $this->error('请登录！', 'user/signin');
            }
        }
    }

    /**
     * 申请导游验证
     * @author ZhangChao
     */
    public function guide()
    {
        if (empty(Session('uid'))) {
            return $this->error('请先登录！', 'user/signin');
        } else {
            if (empty($_POST)) {
                return $this->error('请如实填写信息！');
            } else if (!is_numeric($_POST['id']) || 18 != strlen($_POST['id'])) {
                return $this->error('身份证号错误！');
            } else if (Db::name('apply')->where('uid', Session::get('uid'))->select()) {
                return $this->error('你已经申请过了，请勿重复申请！');
            } else {
// 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('front');
// 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->move(ROOT_PATH . 'public/static/upload/');
                $file1 = request()->file('back');
// 移动到框架应用根目录/public/uploads/ 目录下
                $info1 = $file1->move(ROOT_PATH . 'public/static/upload/');
                if (!empty($_FILES['guide']['name'])) {
                    $file2 = request()->file('guide');
// 移动到框架应用根目录/public/uploads/ 目录下
                    $info2 = $file2->move(ROOT_PATH . 'public/static/upload/');
                }
                if (($info && $info1) || $info2) {
// 成功上传后 获取上传信息
                    $path = $info->getSaveName();
                    $path1 = $info1->getSaveName();
                    if (!empty($info2))
                        $path2 = $info2->getSaveName();
                    else
                        $path2 = '无';
                } else {
// 上传失败获取错误信息
                    return $this->error('上传信息有误！');
                }

                $result = Db::name('apply')->insert([
                    'uid' => Session('uid'),
                    'name' => Session('user')['username'],
                    'realname' => $_POST['realname'],
                    'real_id' => $_POST['id'],
                    'front' => $path,
                    'back' => $path1,
                    'guide' => $path2
                ]);

                if ($result) {
                    return $this->success('申请已提交，请耐心等待审核结果！', 'index/index');
                } else {
                    return $this->error('申请提交失败，请检查信息是否有误！');
                }
            }
        }
    }

    public function apply()
    {
        if (empty(Session::get('uid'))) {
            return $this->error('请登录！', 'user/signin');
        } else if ('apply' == input('act')) {
            $fa = input('post.')['friend'];
            $fn = input('post.')['names'];

            foreach (array_combine($fa, $fn) as $k => $val) {
                $f = json_decode(Session::get('user.friends'), true);
                $f[$k] = $val;
                $fp = json_decode(Session::get('user.f_apply'), true);
                unset($fp[$k]);
                $r1 = Db::name('user')->where('uid', Session('uid'))->update(['friends'=>json_encode($f), 'f_apply'=>json_encode($fp)]);
                $ufp = Db::name('user')->where('uid', $k)->select();
                $ufp1 = json_decode($ufp[0]['applyed'], true);
                unset($ufp1[Session('uid')]);
                $ufp2 = json_decode($ufp[0]['friends'], true);
                $ufp2[Session('uid')] = Session('user.username');
                $r2 = Db::name('user')->where('uid', $k)->update(['applyed'=>json_encode($ufp1),'friends'=>json_encode($ufp2)]);

                if ($r1 && $r2) {
                    Session::set('user.friends', json_encode($f));
                    Session::set('user.f_apply', json_encode($fp));
                    return $this->success('已同意好友申请！');
                }
            }
        } else {
            $f = json_decode(Session('user.applyed'), true);
            $f[input('get.u')] = input('get.name');
            $fa = json_decode(input('get.a'));
            $fa[Session('uid')] = Session('user.username');
            $result = Db::name('user')->where('uid', Session('uid'))->update(['applyed'=>json_encode($f)]);
            $result1 = Db::name('user')->where('uid', input('get.u'))->update(['f_apply'=>json_encode($fa)]);

            if ($result && $result1) {
                Session::set('user.applyed', json_encode($f));
                return $this->success('好友申请成功，请等待对方同意！');
            } else {
                return $this->error('好友申请失败，有可能是你已经发送过申请！');
            }
        }
    }
}

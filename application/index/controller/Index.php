<?php
namespace app\index\controller;

use think\Controller;
use think\Validte;
use think\Loader;
use app\index\model\Ask;
use think\Db;
use think\Request;
use app\index\model\Answer;
use app\index\model\User;
use app\index\model\HComment;
use think\Session;

class Index extends Controller
{

    /**
     * 网站首页，展示给不同的用户（游客，注册会员）不同的内容
     * @author ZhangChao
     */
    public function index()
    {
        $this->assign('title', '竹签录 | 让心灵去旅行');
        $result = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->order('good desc')->paginate(5);
        $site = Db::name('config')->field('img,carousel')->select();
        $tui = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->field('id,title,content')->order('id desc')->limit('0,3')->select();
        if (!empty(Session('uid'))) {
            $arr = array_keys(json_decode(Session::get('user.friends'), true));
            $arr[] = Session('uid');
            $friends = Db::name('user')->field('uid,username,avatar,motto')->limit('0,5')->where('uid', 'notin', $arr)->where('city_no', 'like', '%' . mb_substr(Session('user')['city_no'], 0, 4) . '%')->select();

            $f = json_decode(Session::get('user')['friends'],true);
            if (count($f) > 1) {
                Session::set('user.level', 1);
                Db::name('user')->where('uid', Session('uid'))->update(['level'=>1]);
            }
            $this->assign('friends', $friends);
        }
        $this->assign('carousel', json_decode($site[0]['carousel'], true));
        $this->assign('datas', $result);
        $this->assign('tui', $tui);
        $this->assign('img', explode(',', rtrim($site[0]['img'], ',')));

        return $this->fetch();
    }
    /**
     * 问答社区-列表
     * @author zhaoyuzhi
     * @param  array $hotdata
     * @param array $waitdata
     * @param array $listdata
     * @param array $rankdata
     * @param string $title
     */
    public function request()
    {
        /*查找最热问题按照回复的数量查取*/
        $hotdata = Db::view('Ask', 'title,askid,content,first_img,create_time,keywords,answernum,scan,great')
            ->view('User', 'username,avatar,level', 'User.uid=Ask.user_id')
            ->order(['scan desc', 'create_time desc'])
            ->limit(40)
            ->select();
        /*查找没有回答的问题*/
        $waitdata = Db::view('Ask', 'title,askid,content,first_img,create_time,keywords,answernum,scan,great')
            ->view('User', 'username,avatar,level', 'User.uid=Ask.user_id')
            ->where('answernum', 0)
            ->order(['create_time desc'])
            ->limit(40)
            ->select();
        /*查找最近的问题*/
        $lastdata = Db::view('Ask', 'title,askid,content,first_img,create_time,keywords,answernum,scan,great')
            ->view('User', 'username,avatar,level', 'User.uid=Ask.user_id')
            ->where('answernum', '>', 0)
            ->order(['create_time desc'])
            ->limit(40)
            ->select();
        /*排行榜的数据*/
        $rankdata = Db::view('Ask', 'askid,scan')
            ->view('User', 'username,level,avatar,uid', 'User.uid = Ask.user_id')
            ->order('scan desc')->limit(5)->select();
        $title = '问答|竹签录';
        //dump($hotdata);

        return View('', compact('title', 'hotdata', 'waitdata', 'lastdata', 'rankdata'));
    }

    /**
     *查看点赞的个数
     */
    public function greatnum()
    {
        $askid = input('post.askid');
        $data = [];
        $greatnum = Db::name('answer')->where('ask_id', $askid)->count('greatnum');
        $data = [
            'greatnum' => $greatnum,
            'askid' => $askid,
        ];

        echo json_encode($data);
    }

    /**
     * 搜索问答的功能
     */
    public function search()
    {
        $keywords = input('post.keywords');
        $data = Db::name('ask')->where('title|keywords|content', 'like', '%' . $keywords . '%')->select();
        $newdata = [];
        //返回了一个数组
        foreach ($data as $key => $value) {
            $value['title'] = preg_replace("/$keywords/i", "<b style=\"color:red\">" . $keywords . "</b>", $value['title']);
            $value['keywords'] = preg_replace("/$keywords/i", "<b style=\"color:red\">" . $keywords . "</b>", $value['keywords']);
            $value['content'] = preg_replace("/$keywords/i", "<b style=\"color:red\">" . $keywords . "</b>", $value['content']);
            $greatnum = Db::name('answer')->where('ask_id', $value['askid'])->count('greatnum');
            $value['greatnum'] = $greatnum;
            $newdata[] = $value;
        }
        $title = '搜索';
        $count = count($newdata);
        return view('', compact('newdata', 'title', 'count', 'greatnum'));
    }

    /**
     *问答的详情
     * @author:zhaoyuzhi
     * @param int $askid
     * @param array $detail
     * @param string $title
     */
    public function detail(Ask $ask)
    {
        $askid = (input('param.askid'));
        //一打开此网页，浏览数就加1
        $ask = Ask::get($askid);
        $ask->scan = $ask->scan + 1;
        $result = $ask->save();

        /*查询该问题的详情*/
        $detail = Db::view('Ask', 'title,content,scan,great,create_time,keywords,attention,report_user,answernum')
            ->view('User', 'username,level,avatar,uid', 'Ask.user_id = User.uid')
            ->where('askid', $askid)
            ->select();

        //dump($detail);
        $uid = Session('uid');
        $title = $detail[0]['title'];
        //是否举报过该问题的用户
        $is_report = ($this->checkExist($detail[0]['report_user'], $uid))[1];
        //关注的个数
        $attention_num = ($this->checkExist($detail[0]['attention'], $uid))[0];
        //该用户是否关注过
        $is_attention = ($this->checkExist($detail[0]['attention'], $uid))[1];
        //所有的回答；
        $answer = Db::view('Answer', 'create_time,content,greatnum,hid')
            ->view('User', 'username,level,avatar,uid', 'user.uid=Answer.user_id')
            ->where('ask_id', $askid)
            ->select();
        return View('', compact('title', 'detail', 'attention_num', 'is_report', 'askid', 'is_attention', 'answer'));

    }

    /**
     *点赞功能的实现
     * @author zhaoyuzhi
     */
    public function addGreat()
    {
        $hid = input('post.answer_id');
        $data = [];
        $answer = Answer::get($hid);
        $answer->greatnum = $answer->greatnum + 1;
        $result = $answer->save();
        if ($result) {
            $data = ['status' => 1];
        } else {
            $data = ['status' => 0];
        }
        echo json_encode($data);
    }

    /**
     *所有的回答的回复列表
     * @author zhaoyuzhi
     */
    public function commentlist()
    {
        $answer_id = input('post.answer_id');

        $data = Db::view('HComment', 'h_cid,create_time,delete_time,content')
            ->view('User', 'username,avatar,level,uid', 'User.uid=HComment.user_id')
            ->where('answer_id', '=', $answer_id)
            ->select();
        echo json_encode($data);
    }

    /**
     *添加回答的回复
     * @author zhaoyuzhi
     */
    public function comment()
    {
        $uid = Session('uid');
        $user = Session('user');
        if (!$uid) {
            $this->error('登录后发表评论');
        }
        $content = input('post.content');
        $answer_id = input('post.hid');

        if ($content) {
            $comment = new HComment;
            $comment->user_id = $uid;
            $comment->content = $content;
            $comment->answer_id = $answer_id;
            $result = $comment->allowField(true)->save();
            if ($result) {
                $data = [
                    'status' => 1,
                    'avatar' => $user['avatar'],
                    'username' => $user['username'],
                    'content' => $content,
                    'time' => date('Y-m-d H:i:s', time()),
                ];
            } else {
                $data = ['status' => 0];
            }
        }
        echo json_encode($data);
    }

    /**
     *处理关注问题
     * @author zhaoyuzhi
     */
    public function attention()
    {
        $uid = Session('uid');
        if (!$uid) {
            $this->error('登录后才能关注问题');
        }
        //问题ID
        $askid = input('param.askid');
        //上级来源
        $prev_url = $_SERVER['HTTP_REFERER'];
        $ask = Ask::get($askid);
        $askstr = $this->parseStr($ask->attention, $uid);
        $ask->attention = $askstr;
        //将此用户存入ask表中
        //将此问题的ID添加到用户的关注问题的字段中
        $user = User::get($uid);
        $userstr = $this->parseStr($user->follow_ask, $askid);
        $user->follow_ask = $userstr;
        $resultAsk = $ask->save();
        $resultUser = $user->save();
        dump($userstr);
        if ($resultAsk && $resultUser) {
            Session::set('user.follow_ask', Session::get('user.follow_ask') . ',' . $askid);
            $this->redirect($prev_url);
        } else {
            $this->error('关注失败,请稍后再试');
        }
    }
    // 这是一个公共的方法，处理字符串
    protected function parseStr($str, $uid)
    {
        if ($str) {
            //如果非空的话
            $arr = explode(',', $str);
            $count = count($arr);
            if (empty($arr[$count - 1])) {
                unset($arr[$count - 1]);
            }
            if (!in_array($uid, $arr)) {
                //$uid 不在数组内
                array_push($arr, $uid);

            }
            $str = join(',', $arr);
        } else {
            $str = $uid;
        }
        return (string)$str;
    }

    /**
     *取消关注
     * @author zhaoyuzhi
     */
    public function cancelatte()
    {
        $askid = input('param.askid');
        $uid = Session('uid');
        $user = User::get($uid);
        $prev_url = $_SERVER['HTTP_REFERER'];
        //将该问题从用户关注表中删除
        //将该用户从关注表中删除
        $ask = Ask::get($askid);
        //该用户关注的所有问题
        $userarr = explode(',', $user->follow_ask);
        //关注该问题的所有用户
        $askarr = explode(',', $ask->attention);
        //新的用户关注问题
        $follow_ask = $this->checkarr($userarr, $askid);
        //新的问题关注者
        $attention = $this->checkarr($askarr, $uid);
        $ask->attention = $attention;
        $user->follow_ask = $follow_ask;
        $result1 = $ask->save();
        $result2 = $user->save();
        if ($result1 && $result2) {
            $arr = explode(',', trim(Session::get('user.follow_ask'), ','));
            foreach ($arr as $key => $val) {
                if ($askid == $val) {
                    unset($arr[$key]);
                }
            }
            Session::set('user.follow_ask', join(',', $arr));
            $this->redirect($prev_url);
        } else {
            $this->error('取消关注失败，请稍后再试');
        }

    }

    /**
     *判断某个值是否存在于数组，有则返回标志位1，没有则返回0
     * @author zhaoyuzhi
     */
    protected function checkExist($str, $uid)
    {
        if ($str) {
            $arr = explode(',', $str);
            $num = count($arr);
            if (!$arr[$num - 1]) {
                $num = $num - 1;
            }
            if (in_array($uid, $arr)) {
                $tip = 1;
            } else {
                $tip = 0;
            }

        } else {
            $num = 0;
            $tip = 0;
        }
        return [$num, $tip];
    }

    /**
     *判断在某个值的是否在一个数组中，如果有则销毁
     * @author zhaoyuzhi
     */
    protected function checkarr($arr, $id)
    {
        foreach ($arr as $key => $value) {
            if ($value == $id) {
                unset($arr[$key]);
            }
        }
        $str = join(',', $arr);
        return $str;
    }

    /**
     *处理举报的问题
     * @author zhaoyuzhi
     */
    public function report()
    {
        $askid = $_POST['askid'];
        $uid = Session('uid');
        $ask = Ask::get($askid);
        $user = $ask->report_user;
        if ($user) {
            //如果不为空将判断是否已经在里面里了，
            $arr = explode(',', $user);
            $count = count($arr);
            if (!$arr[$count - 1]) {
                //如果数组中的最后一个元素为空,销毁最后一个元素
                unset($arr[$count - 1]);
            }
            if (!in_array($uid, $arr)) {
                array_push($arr, $uid);
                $str = join(',', $arr);
                $ask->report_user = $str;
                $ask->reportnum = $ask->reportnum + 1;
            }
        } else {
            $ask->report_user = $uid;
            $ask->reportnum = $ask->reportnum + 1;
        }
        $result = $ask->save();
        if ($result) {
            $data = [
                'tips' => 1,
                'askid' => $askid,
                'uid' => $uid,
            ];
        }
        echo json_encode($data);
    }

    public function ask()
    {
        $title = '提问|竹签录';
        return View('', compact('title'));
    }

    /**
     *处理提问，存入数据库
     * @author zhaoyuzhi
     * @param $prev_url 上一级URL
     * @param  object $validate
     * @param  object $ask
     */
    public function settleask()
    {
        //if (Session(?uid))
        $prev_url = $_SERVER['HTTP_REFERER'];
        $validate = Loader::validate('Ask');
        if (!$validate->check($_POST)) {
            $this->error($validate->getError(), $prev_url);
        }
        $ask = new Ask;
        $prettern = '/<img(.*)?src="(.*)"/imUs';
        $content = $_POST['content'];
        preg_match($prettern, $content, $matches);
        $imgsrc = '';
        if ($matches) {
            $imgsrc = $matches[2];
        }
        //$create_ip = $_SERVER['REMOTE_ADDR'];
        $prev_url = $_SERVER['HTTP_REFERER'];
        $ask->title = $_POST['title'];
        $ask->content = $_POST['content'];
        $ask->keywords = $_POST['keywords'];
        $ask->user_id = Session('uid');
        $ask->first_img = $imgsrc;
        $result = $ask->allowField(true)->save();

        if ($result) {
            $this->success('提问成功', $prev_url);
        } else {
            $this->error('提交失败', $prev_url);
        }
    }

    /**
     *添加问题的回答并存入数据库
     * @author zhaoyuzhi
     */
    public function addanswer()
    {
        $data = [];
        //判断用户是已经登录
        $uid = Session('uid');
        $content = input('post.content');
        if ($uid) {
            //用户已经登录
            $data = ['status' => 1];
            //属于哪一个问题
            $ask_id = input('post.askid');
            $ask = Ask::get($ask_id);
            //将问题表中的回答个数加1；
            $ask->answernum = $ask->answernum + 1;
            $result2 = $ask->save();
            //将此回答添加到回答表中；
            $answer = new Answer;
            $answer->content = $content;
            $answer->ask_id = $ask_id;
            $answer->user_id = $uid;
            $result1 = $answer->allowField(true)->save();
            if ($result1 && $result2) {
                $data['tips'] = 1;
            } else {
                $data['tips'] = 0;
            }

        } else {
            /*用户没有登录*/
            $data = ['status' => 0];
        }


        echo json_encode($data);
    }
    /**
     * 搜索用户，文章，地点
     * @author ZhangChao
     */
    public function find()
    {
        if (empty($_GET['keyword'])) {
            $this->error('搜索内容为空！');
        } else {
            $user = Db::name('user')->where('username', 'like', '%' . $_GET['keyword'] . '%')->field('uid,username,avatar,motto')->select();
            $article = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('title', 'like', '%' . $_GET['keyword'] . '%')->field('id,title,destination,content,create_time,author,uid')->select();
            $area = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('destination', 'like', '%' . $_GET['keyword'] . '%')->field('id,title,destination,content,create_time,author,uid')->select();
            $this->assign('user', $user);
            $this->assign('article', $article);
            $this->assign('area', $area);
            $this->assign('title', '关于“' . $_GET['keyword'] . '”的搜索结果');
            $this->assign('keyword', $_GET['keyword']);

            return $this->fetch();
        }
    }
}

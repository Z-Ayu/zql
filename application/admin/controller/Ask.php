<?php
namespace app\admin\controller;

use think\Controller;
use app\index\model\Ask as AskModel;
use app\index\model\Answer;
use think\Db;

class Ask extends Controller
{
    public function index()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $title = '问题管理';
            $askdata = Db::view('Ask', 'askid,user_id,delete_time,title,keywords,scan,create_time,answernum,reportnum')
                ->view('User', 'username', 'User.uid=Ask.user_id')
                ->paginate(10);

            $page = $askdata->render();
            return view('', compact('title', 'askdata', 'page'));
        }
    }

    //删除某个问题
    public function delete($askid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $result = AskModel::destroy($askid);
            if ($result) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    //恢复被软删除的问题
    public function askreset($askid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $ask = AskModel::withTrashed()->find($askid);
            $ask->delete_time = '0000-00-00 00:00:00';
            $result = $ask->save();
            if ($result) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }

    public function detail($askid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $title = '问题详情';
            $askdata = Db::name('ask')->where('askid', $askid)->select();
            $askdata = $askdata[0];
            //查找某个用户下面的回答
            $answerdata = Db::view('Answer')->view('User', 'username', 'Answer.user_id=User.uid')->select();

            return view('', compact('title', 'askdata', 'answerdata'));
        }
    }

    //删除问题下面的评论
    public function answerdelete($hid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $result = Answer::destroy($hid);
            // dump($result);
            if ($result) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }

    }

    //恢复删除的评论
    public function answerreset($hid)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $answer = Answer::withTrashed()->find($hid);
            $answer->delete_time = '0000-00-00 00:00:00';
            $result = $answer->save();
            if ($result) {
                $this->success('成功');

            } else {
                $this->error('失败');
            }
        }
    }
}
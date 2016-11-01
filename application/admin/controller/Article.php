<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Session;
use app\index\model\AComment;
use think\Db;
use app\admin\model\Article as ArticleModel;

class Article extends Controller
{
    public function index()
    {
        if (!empty(Session::get('admin'))) {
            //统计总的游记数
            $count = Db::name('article')->count();
            $title = '后台中心';
            $articlegroup = Db::name('article')->field("count('destination') as value ,destination as name")
                ->group('destination')
                ->select();
            $data = $articlegroup;


            return View('', compact('title', 'count', 'data'));
        } else {
            return $this->error('还没登录哦，请登录！', 'user/login');
        }
    }

    public function detail($id)
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $title = '后台中心';
            //查找文章的详情
            $data = Db::name('article')->where('id', $id)->select();
            $data = $data[0];
            //查找作者的用户等级
            if ($data['uid']) {
                $userdata = Db::name('user')->field('level,avatar')->where('uid', $data['uid'])->select();
                $level = $userdata[0]['level'];
                $avatar = $userdata[0]['avatar'];
            } else {
                $level = 0;
                $avatar = '';
            }
            //查询某个文章下的所有的评论
            $commentdata = Db::name('a_comment')->where('article_id', $data['id'])->paginate(10);
            $page = $commentdata->render();
            return View('', compact('title', 'data', 'level', 'avatar', 'commentdata', 'page'));
        }
    }


    public function article()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            //所有的文章的列表
            $list = Db::name('article')->field('id,author,uid,create_time,destination,id,title')->paginate(5);
            $page = $list->render();
            return View('', compact('list', 'page'));
        }
    }

    //删除掉某个评论
    public function delete()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $cid = input('param.cid');
            $result = AComment::destroy($cid);
            if ($result) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }

    //恢复已经删除的评论
    public function commentreset()
    {
        if (empty(Session('admin'))) {
            return $this->error('还没登录哦，请登录！', 'user/login');
        } else {
            $cid = input('param.cid');
            $comment = AComment::withTrashed()->find($cid);
            $comment->delete_time = null;
            $result = $comment->save();
            if ($result) {
                $this->success('恢复成功');
            } else {
                $this->error('恢复失败');
            }

        }
    }
}
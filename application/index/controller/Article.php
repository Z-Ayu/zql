<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Validte;
use think\Loader;
use app\index\model\Article as ArticleModel;
use think\Db;
use app\index\model\AComment;

class Article extends Controller
{
    // 删除文章
    public function del()
    {
        if (Db::name('article')->where('id', input('param.id'))->update(['delete_time' => date('Y-m-d H:i:s')])){
            return $this->success('删除成功！');
        } else {
            return $this->success('删除失败！');
        }
    }

    // 恢复删除的文章
    public function back()
    {
        if (Db::name('article')->where('id', input('param.id'))->update(['delete_time' => '0000-00-00 00:00:00'])){
            return $this->success('恢复成功！');
        } else {
            return $this->success('恢复失败！');
        }
    }
    /**
     * 文章总览首页，同时实现文章收藏、赞的功能
     * @author ZhangChao
     */
    public function index()
    {
        switch (input('param.type')) {
            case 'keep':
                if (empty(Session('uid'))) {
                    return $this->error('请先登录！', 'user/signin');
                } else {
                    if (empty(input('param.id'))) {
                        return $this->error('无效的文章！', 'article/index');
                    } else {
                        if (!in_array(input('param.id'), explode(',', Session('user')['keep_article']))) {
                            Db::name('user')->where('uid', Session('uid'))->update(['keep_article' => (Session('user')['keep_article'] . input('param.id') . ',')]);
                            Session('user.keep_article', Session('user')['keep_article'] . input('param.id') . ',');
                            $this->success('收藏成功！');
                        } else {
                            $this->error('你已收藏过该文章，无需重复收藏！');
                        }
                    }
                }
                break;
            case 'good':
                Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('id', input('param.id'))->update(['good' => (input('param.g') + 1)]);
                $this->success('赞成功！');
                break;
            default:
                $this->assign('title', '竹签录 | 让心灵去旅行');
                $result = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->field('id,title,content,author,create_time,uid,great')->order('id desc')->paginate(10);
                if (!empty(Session('uid'))) {
                    $friends = Db::name('user')->field('uid,username,avatar,motto')->limit('0,5')
                        ->where('uid', '<>', Session('uid'))->where('city_no', 'like', '%' . mb_substr(Session('user')['city_no'], 0, 4) . '%')
                        ->select();
                    $this->assign('friends', $friends);
                }
                $this->assign('datas', $result);

                return $this->fetch();
                break;
        }
    }

    /**
     * 文章显示详情页
     * @author ZhangChao、zhaoyuzhi
     */
    public function detail(AComment $allcomment)
    {
        $result = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->field('id,go_off,destination,take,title,content,uid,author,create_time,level,update_time,create_time,good,read_num,comment_num')->where('id', input('param.id'))->select();
        //查找该游记下的所有的评论
        $article_id = input('param.id');
        $sql = "select u.avatar,c.user_id,c.a_cid,c.content,c.username,c.create_time from zql_a_comment c left join zql_user u on u.uid=c.user_id where c.article_id = " . $article_id . ' and is_delete = 0';
        $commentdata = $allcomment->query($sql);

        if (empty($result)) {
            return $this->redirect('article/index');
        } else {
            $rows = $result[0];
            if ($rows['good'] > 100)
                Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('id', $rows['id'])->update(['great' => 1]);
            Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('id', $rows['id'])->update(['read_num' => ($rows['read_num'] + 1)]);
            $this->assign('title', $rows['title'] . ' | 竹签录');
            $this->assign('articleTitle', $rows['title']);
            $this->assign('content', $rows['content']);
            $this->assign('level', $rows['level']);
            $this->assign('author', $rows['author']);
            $this->assign('readNum', $rows['read_num']);
            $this->assign('good', $rows['good']);
            $this->assign('id', $rows['id']);
            $this->assign('uid', $rows['uid']);
            $this->assign('goOff', $rows['go_off']);
            $this->assign('createTime', $rows['create_time']);
            $this->assign('destination', $rows['destination']);
            $this->assign('take', $rows['take']);
            $this->assign('comment_num', $rows['comment_num']);
            $this->assign('commentdata', $commentdata);
            return $this->fetch();
        }
    }

    /**
     * 删除问题下的评论
     * @author zhaoyuzhi
     */
    public function delete(AComment $comment)
    {
        $a_cid = input('post.a_cid');
        $article_id = input('post.article_id');
        $result = Db::name('a_comment')->where('a_cid', $a_cid)->update(['is_delete' => 1]);
        if ($result) {
            $data['status'] = 1;
            $articledata = ArticleModel::get($article_id);
            $articledata->comment_num = $articledata->comment_num - 1;
            $result2 = $articledata->save();
            if ($result2) {
                $data['tip'] = 1;
            } else {
                $data['tip'] = 0;
            }

        } else {
            $data['status'] = 0;
        }
        echo json_encode($data);
    }

    /**
     * 同城推荐，推荐一些同程旅游景点等
     * @author ZhangChao
     */
    public function commend()
    {
        $city = explode(' ', Session::get('user.city'));
        $cityNo = explode(' ', Session::get('user.city_no'));
        if (!empty($city[0])) {
            $posts = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('destination', 'like', '%' . rtrim($city[0], '区市') . '%')->field('id,destination,title,author,content,uid,create_time,good')->select();
            $guide = Db::name('user')->where('is_guide', 1)->order('rate desc')->where('city_no', 'like', '%'.$cityNo[0].'%')->field('city,uid,username,rate,avatar')->select();
            $friends = Db::name('user')->where('city_no', 'like', '%'.$cityNo[0].'%')->field('city,uid,username,avatar')->select();
        } else if (!empty($city[1])) {
            $posts = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->where('destination', 'like', '%' . rtrim($city[1], '区市') . '%')->field('id,title,destination,author,content,uid,create_time,good')->whereOr('destination', 'like', '%' . rtrim($city[0], '区市') . '%')->select();
            $guide = Db::name('user')->where('is_guide', 1)->order('rate desc')->where('city_no', 'like', '%'.$cityNo[1].'%')->whereOr('city_no', 'like', '%'.$cityNo[0].'%')->field('city,uid,username,rate,avatar')->select();
            $friends = Db::name('user')->where('city_no', 'like', '%'.$cityNo[1].'%')->whereOr('city_no', 'like', '%'.$cityNo[0].'%')->field('city,uid,username,avatar')->select();
        } else {
            $posts = Db::name('article')->where('delete_time','0000-00-00 00:00:00')->order('good desc')->field('id,title,author,uid,content,create_time,destination,good')->select();
            $guide = Db::name('user')->where('is_guide', 1)->order('rate desc')->field('city,uid,username,rate,avatar')->select();
            $friends = Db::name('user')->where('is_guide', '0')->field('city,uid,username,avatar')->select();
        }

        $this->assign('title', '同城推荐 | 竹签录');
        $this->assign('posts', $posts);
        $this->assign('guide', $guide);
        $this->assign('friends', $friends);
        return $this->fetch();
    }

    /**
     * 导游资格申请，可以点亮导游图标，为游客提供便利
     * @author ZhangChao
     */
    public function near()
    {
        $this->assign('title', '导游资格申请 | 竹签录');
        $this->assign('content', '导游资格申请');
        return $this->fetch();
    }

    /**
     * 同行旅游结伴
     * @author ZhangChao
     */
    public function together()
    {
        $this->assign('title', '同行 | 竹签录');
        $this->assign('content', '<h1>同行 | 竹签录</h1>');
        return $this->fetch();
    }

    /**
     * 发表游记、文章、攻略
     * @author zhaoyuzhi
     */
    public function post()
    {
        $title = '发表游记';
        return View('', compact('title'));
    }

    /**
     * 提交游记发表
     * @author zhaoyuzhi
     */
    public function postSubmit(ArticleModel $article)
    {
        $uid = session('uid');
        if (empty($uid)) {
            $this->error('发表游记前请登录');

        }
        $prev_url = $_SERVER['HTTP_REFERER'];
        $validate = Loader::validate('Article');
        if (!$validate->check($_POST)) {
            $this->error($validate->getError(), $prev_url);
        }
        $article->title = input('post.title');
        $article->destination = input('post.destination');
        $go_off = input('post.year') . '-' . input('post.month') . '-' . input('post.day');
        $article->go_off = $go_off;
        $article->uid = $uid;
        $article->author = session('user')['username'];
        $article->take = input('post.spendtime');
        $article->content = input('post.content');
        $result = $article->allowField(true)->save();
        if ($result) {
            $this->success('发表成功', 'index/article/index');
        } else {
            $this->error('发表失败', $prev_url);
        }

    }

    /**
     *显示部分评论开始
     * @author zhaoyuzhi
     */
    public function allcomment(AComment $allcomment)
    {
        $article_id = input('post.article_id');
        $sql = "select u.avatar,c.user_id,c.content,c.username,c.create_time from zql_a_comment c left join zql_user u on u.uid=c.user_id where c.article_id = " . $article_id;
        $data = $allcomment->query($sql);
        echo json_encode($data);
    }

    // 展示所有的评论
    public function showall(AComment $allcomment)
    {
        $article_id = input('post.article_id');
        $sql = "select u.avatar,c.user_id,c.content,c.username,c.create_time from zql_a_comment c left join zql_user u on u.uid=c.user_id where c.article_id =" . $article_id . " and c.a_cid>3";
        $data = $allcomment->query($sql);
        echo json_encode($data);
    }

    /**
     *发表评论开始
     * @author zhaoyuzhi
     */
    public function comment(AComment $acomment)
    {
        $uid = session('uid');
        $username = session('user')['username'];
        $article_id = input('post.article_id');
        $data = [];
        if($uid) {
           $acomment->data([
                'article_id' => $article_id,
                'content' => input('post.content'),
                'user_id' => $uid,
                'username' => $username,
            ]);
            $result1 =  $acomment->allowField(true)->save();
            $article = ArticleModel::get($article_id);
            $article->comment_num = $article->comment_num + 1;
            $result2 = $article->save();
           if($result1 && $result2) {
                $data = [
                    'status' => 1,
                    'tip' => 1,
                    'username' => $username,
                    'time' => date('Y-m-d H:i:s',time()),
                    'content' =>  input('post.content'),
                    'avatar' => session('user')['avatar'],
                    'uid' => $uid,

                ];
           } else {
                $data = ['tip' => 0 ];
           }
        } else {
            $data = ['status'=>0]; 
        }
        echo json_encode($data);
    }
}

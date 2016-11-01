<?php
/**
 * Created by PhpStorm.
 * User: Zhang Chao
 * Date: 2016/10/19
 * Time: 14:43
 */

$link = mysqli_connect('localhost', 'root', 'root', 'zql');

mysqli_set_charset($link, 'utf8');

if ('ajax' == $_GET['type']) {

    $sql = 'SELECT is_read,msg,send_time,id,from_uid FROM `zql_chat` WHERE to_uid = ' . $_POST['userFrom'] . ' AND is_read = 0 ORDER BY id ASC LIMIT 1';

    $results = mysqli_query($link, $sql);

    if ($results && mysqli_affected_rows($link)) {

        $rows = mysqli_fetch_assoc($results);


        mysqli_query($link, 'UPDATE `zql_chat` SET `is_read` = \'1\' WHERE `id` = ' . $rows['id']);

        $arr = array(
            'code' => $rows['is_read'],
            'msg' => $rows['msg'],
            'time' => $rows['send_time'],
            'uName' => mysqli_fetch_assoc(mysqli_query($link, 'SELECT username FROM zql_user WHERE uid = ' . $rows['from_uid']))['username'],
            'uId' => $rows['from_uid']
        );

    } else {
        $arr = array(
            'code' => 1,
            'msg' => '暂时没有新消息！',
            'time' => date('Y-m-d H:i:s')
        );
    }

} else if ('send' == $_GET['type']) {

    if ('' == trim($_POST['msg'])) {

        $arr = array(
            'code' => 1,
            'msg' => '消息发送失败！',
            'time' => date('Y-m-d H:i:s')
        );

    } else {

        $sql = 'INSERT INTO `zql_chat`(from_uid,to_uid,msg,send_time) VALUES(' . $_POST['userFrom'] . ',' . $_POST['userTo'] . ',"' . strip_tags($_POST['msg']) . '","' . $_POST['time'] . '")';

        $results = mysqli_query($link, $sql);

        if ($results && mysqli_affected_rows($link)) {

            $arr = array(
                'code' => 0,
                'msg' => '消息发送成功！',
                'time' => date('Y-m-d H:i:s'),
                'source' => strip_tags($_POST['msg'])
            );

        } else {
            $arr = array(
                'code' => 1,
                'msg' => '消息发送失败，内容不能为空！',
                'time' => date('Y-m-d H:i:s')
            );
        }
    }
} else if ('online' == $_GET['type']) {

    // 获取列出好友列表
    $all = explode(',', $_POST['all']);
    unset($all[count($all) - 1]);
    // 获取所有好友列表
    $friends = array_keys(json_decode($_POST['friends'], true));
    // 计算差集
    $lineFriends = array_values(array_diff($friends, $all));
    // 在线好友
    $line = array();

    foreach ($lineFriends as $uid) {

        $result = mysqli_query($link, 'SELECT update_time,username FROM zql_user WHERE uid = ' . $uid);

        $friend = mysqli_fetch_assoc($result);

        if ((strtotime($friend['update_time']) + 45) < time()) {

            $line['offline'][$uid] = $friend['username'];

        } else {

            $line['online'][$uid] = $friend['username'];
        }
    }
    if (empty($line['online']) && !empty($line['offline'])) {
        $arr = array(
            'code' => 2,
            'msg' => $line['offline']
        );
    } else if (!empty($line['online']) && empty($line['offline'])) {
        $arr = array(
            'code' => 3,
            'msg' => $line['online']
        );
    } else if (empty($line['online']) && empty($line['offline'])) {
        $arr = array(
            'code' => 5,
            'msg' => '暂时没有新上线好友！'
        );
    } else {
        $arr = array(
            'code' => 4,
            'online' => $line['online'],
            'offline' => $line['offline']
        );
    }

    $sql = 'UPDATE `zql_user` SET `update_time`="' . date('Y-m-d H:i:s') . '" WHERE uid=' . $_POST['userId'];
    mysqli_query($link, $sql);

} else {
    $arr = array(
        'code' => 4,
        'msg' => '出错了……'
    );
}

echo json_encode($arr);

mysqli_close($link);

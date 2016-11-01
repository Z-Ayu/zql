<?php
/**
 * Created by PhpStorm.
 * User: Zhang Chao
 * Date: 2016/10/19
 * Time: 14:43
 */
$link = mysqli_connect('localhost', 'root', 'root', 'zcstation');

mysqli_set_charset($link, 'utf8');

if ('ajax' == $_GET['type']) {

    $sql = 'SELECT is_read,msg,send_time,id FROM `chat` WHERE from_uid = ' . $_POST['userTo'] . ' AND to_uid = ' . $_POST['userFrom'] . ' AND is_read = 0 ORDER BY id ASC LIMIT 1';

    $results = mysqli_query($link, $sql);

    if ($results && mysqli_affected_rows($link)) {

        $rows = mysqli_fetch_assoc($results);



        mysqli_query($link, 'UPDATE `chat` SET `is_read` = \'1\' WHERE `id` = ' . $rows['id']);

        $arr = array(
            'code' => $rows['is_read'],
            'msg' => $rows['msg'],
            'time' => $rows['send_time']
        );

    } else {
        $arr = array(
            'code' => 1,
            'msg' => '暂时没有新消息！',
            'time' => date('Y-m-d H:i:s')
        );
    }

} else if ('send' == $_GET['type']) {

    $sql = 'INSERT INTO `chat`(from_uid,to_uid,msg,send_time) VALUES(' . $_POST['userFrom'] . ',' . $_POST['userTo'] . ',"' . $_POST['msg'] . '","' . $_POST['time'] . '")';

    $results = mysqli_query($link, $sql);

    if ($results && mysqli_affected_rows($link)) {

        $arr = array(
            'code' => 0,
            'msg' => '消息发送成功！',
            'time' => date('Y-m-d H:i:s')
        );

    } else {
        $arr = array(
            'code' => 1,
            'msg' => '消息发送失败！',
            'time' => date('Y-m-d H:i:s')
        );
    }
}

echo json_encode($arr);

mysqli_close($link);

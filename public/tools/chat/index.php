<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>在线聊天</title>
    <!--<link rel="stylesheet" href="css/bootstrap.css" media="screen" />-->
    <!--<link rel="stylesheet" href="css/bootstrap-responsive.css" />-->
    <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<?php
if (!empty($_POST['username']) || !empty($_COOKIE['ZQLuid'])) :
    ?>
    <?php

    $link = mysqli_connect('localhost', 'root', 'root', 'zql');

    mysqli_set_charset($link, 'utf8');

    if (empty($_COOKIE['ZQLuid'])) {

        $results = mysqli_query($link, 'SELECT `friends`,`uid` FROM `zql_user` WHERE `username`="'
            . $_POST['username'] . '" AND password="'
            . md5($_POST['password']) . '"');
        if (!($results && mysqli_affected_rows($link))) {
            exit('<h4 style="color: #fff;text-align: center;">账号密码错误！<a style="color: #ededed;" href="/tools/chat">重试</a>！</h4>');
        }

        $rows = mysqli_fetch_assoc($results);

        setcookie('ZQLuid', $rows['uid'], time() + 3600);

    } else {

        $results = mysqli_query($link, 'SELECT `friends`,`uid` FROM `zql_user` WHERE `uid`='
            . $_COOKIE['ZQLuid']);
        $rows = mysqli_fetch_assoc($results);

        $rows['uid'] = $_COOKIE['ZQLuid'];
        $rows['friends'] = $_COOKIE['ZQLfriends'];

    }

    setcookie('ZQLfriends', $rows['friends'], time() + 3600);

    $friends = json_decode($rows['friends'], true);

    $OfflineFriends = $friends;

    ?>
    <div class="container">
        <div class="left">
            <div class="chat-top">
                <div class="show-bar">
                    <span class="talkTo"><span class="icon-user"></span><b> 发送给：</b><span id="friend">我自己</span></span>
                    <input type="hidden" value="<?= $rows['uid']; ?>" name="userto"/>
                    <input type="hidden" value="<?= $rows['uid']; ?>" name="userid"/>
                    <input type="hidden" value='<?= $rows['friends']; ?>' name="friends"/>
                    <audio src="audio/message.mp3" id="message-alert"></audio>
                </div>
                <div id="detail" style="overflow-y: scroll;height: 250px;">
                    <div class="show-top"></div>
                </div>
                <div class="show-bar">
                    <!--<a href="javascript:;" id="display-faces"><span class="show-emotion" title="表情"></span></a>-->
                </div>
            </div>
            <div class="chat-bottom">
                <div class="chat-window">
                    <textarea class="input-window" placeholder="选择好友开始聊天吧……"></textarea>
                </div>
                <div class="submit-bar">
                    <a href="javascript:(void 0)" title="发送消息"><img src="img/emotion/send_btn.jpg"/></a>
                </div>
            </div>
        </div>
        <div class="right">
            <ul>
                <div class="list" style="font-weight: bold;text-indent: 1em;">好友列表</div>
                <div class="list" id="online"><span class="icon-chevron-down"></span>在线好友</div>
                <div class="list" id="offline"><span class="icon-chevron-down"></span>离线好友</div>
                <?php
                foreach ($OfflineFriends as $id => $name) :
                    $avatar = mysqli_fetch_assoc(mysqli_query($link, 'SELECT avatar FROM zql_user WHERE uid=' . $id));
                    ?>
                    <li name="offline">
                        <img src="/<?= $avatar['avatar']; ?>_32x32.jpg"/>
                        <div><?= $name; ?></div>
                        <span class="offline"></span>
                        <input type="hidden" value="<?= $id; ?>"/>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <script src="js/jquery-1.12.4.min.js"></script>
    <script src="js/chat.js"></script>
    <script>
        $(function () {
            setInterval(function () {
                $('.right ul').children('li').click(function () {
                    $('#friend').html($(this).children('div').html());
                    $('input[name="userto"]').val($(this).children('input').val());
                });
            }, 1000);
        });
    </script>
<?php else: ?>
    <div style="width: 200px;margin:100px auto;">
        <form action="index.php" method="post">
            <h4 style="color: #fff;">请输入用户名：</h4>
            <input type="text" name="username" placeholder="请输入用户名" required/>
            <h4 style="color: #fff;">请输入密码：</h4>
            <input type="password" name="password" placeholder="请输入密码" required/><br/>
            <input type="submit" value="确定"/>
        </form>
    </div>
<?php endif; ?>
</body>
</html>
/*Created by Zhang Chao on 2016/10/19.*/

$(function () {

    var onlineFlag = 0;
    var offlineFlag = 0;

    setInterval(function () {
        $('.right ul').children('li').remove();

        var li = $('.right ul').children('li');
        var num = li.length;
        var allUser = '';
        for (var i = 0; i < num; i++) {
            allUser += li.eq(i).children('input').val() + ',';
        }
        $.ajax({
            type: 'post',
            url: "receive.php?type=online",
            dataType: 'json',
            data: {
                all: allUser,
                friends: $('input[name="friends"]').val(),
                userId: $('input[name="userid"]').val()
            },
            success: function (msg) {
                var temp = '';
                var response = '';
                var online = '';
                var offline = '';

                switch (msg.code) {
                    case 2:
                        for (temp in msg.msg) {
                            response += '<li name="offline"><img src="/static/upload/avatar/' + temp + '_32x32.jpg" />'
                                + '<div>' + msg.msg[temp] + '</div><span class="offline"></span>'
                                + '<input type="hidden" value="' + temp + '" /></li>';
                        }
                        $("#offline").after(response);
                        break;
                    case 3:
                        for (temp in msg.msg) {
                            response += '<li name="online"><img src="/static/upload/avatar/' + temp + '_32x32.jpg" />'
                                + '<div>' + msg.msg[temp] + '</div><span class="online"></span>'
                                + '<input type="hidden" value="' + temp + '" /></li>';
                        }
                        $("#online").after(response);
                        break;
                    case 4:
                        for (temp in msg.offline) {
                            offline += '<li name="offline"><img src="/static/upload/avatar/' + temp + '_32x32.jpg" />'
                                + '<div>' + msg.offline[temp] + '</div><span class="offline"></span>'
                                + '<input type="hidden" value="' + temp + '" /></li>';
                        }
                        for (temp in msg.online) {
                            online += '<li name="online"><img src="/static/upload/avatar/' + temp + '_32x32.jpg" />'
                                + '<div>' + msg.online[temp] + '</div><span class="online"></span>'
                                + '<input type="hidden" value="' + temp + '" /></li>';
                        }
                        $("#online").after(online);
                        $("#offline").after(offline);
                        break;
                    default:
                       break;
                }

            },
            error: function (textStatus) {
                //alert('服务器错误！' + textStatus);
            }
        });
    }, 30000);

    $('.submit-bar a').click(function () {
        sendMsg();
    });

    $(window).keydown(function (event) {
        if (13 == event.keyCode) {
            sendMsg();
        }
    });

    $('#online').click(function () {
        if (0 == onlineFlag) {
            $(this).siblings('li[name="online"]').hide();
            $(this).children('span').addClass('icon-chevron-right').removeClass('icon-chevron-down');
            onlineFlag = 1;
        } else {
            $(this).siblings('li[name="online"]').show();
            $(this).children('span').addClass('icon-chevron-down').removeClass('icon-chevron-right');
            onlineFlag = 0;
        }
    });

    $('#offline').click(function () {
        if (0 == offlineFlag) {
            $(this).siblings('li[name="offline"]').hide();
            $(this).children('span').addClass('icon-chevron-right').removeClass('icon-chevron-down');
            offlineFlag = 1;
        } else {
            $(this).siblings('li[name="offline"]').show();
            $(this).children('span').addClass('icon-chevron-down').removeClass('icon-chevron-right');
            offlineFlag = 0;
        }
    });

    function sendMsg() {
        var userId = $('input[name="userid"]').val();
        var userTo = $('input[name="userto"]').val();
        var now = new Date;
        var time = "";
        time += now.getFullYear() + "-";
        time += now.getMonth() + 1 + "-";
        time += now.getDate() + " ";
        time += now.getHours() + ":";
        time += now.getMinutes() + ":";
        time += now.getSeconds();
        // 获取用户发送的消息
        var messages = $('.input-window').val();

        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                userTo: userTo,
                userFrom: userId,
                msg: messages,
                time: time
            },
            url: 'receive.php?type=send',
            success: function (msg) {

                if (0 == msg.code) {
                    var info = '<div class="myself"><div style="float: left;"><div class="content">'
                        + msg.source + '</div><div class="head"><img src="/static/upload/avatar/' + userId + '_32x32.jpg"/></div></div>'
                        + '<p class="chat-time">' + time + '</p></div>';
                    (null != messages) && ("" != messages) && ("\t" != messages) && ("\r" != messages) && ("\n" != messages) ? ($(".show-top").append(info), $("#detail").scrollTop($(".show-top").height()), $(".input-window").val("")) : ($(".input-window").val(""), alert("\u8bf7\u8f93\u5165\u804a\u5929\u5185\u5bb9!"));

                } else {

                    $(".input-window").val(""), alert(msg.msg);
                }

            },
            error: function () {
                alert('发送失败！');
            }
        });
    }

    // 接收好友消息
    setInterval(function () {
        var userId = $('input[name="userid"]').val();
        var userTo = $('input[name="userto"]').val();
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                userTo: userTo,
                userFrom: userId,
            },
            url: 'receive.php?type=ajax',
            success: function (msg) {

                var response = '<div class="friend"><div style="float: left;"><div class="head"><img src="/static/upload/avatar/'
                    + msg.uId + '_32x32.jpg" title="' + msg.uName + '"/></div><div class="content">'
                    + msg.msg + '</div></div>'
                    + '<p class="chat-time"><b style="color: #f30;">' + msg.uName + '</b> 于 ' + msg.time + '</p></div>';

                if (0 == msg.code) {
                    $(".show-top").append(response), $("#detail").scrollTop($(".show-top").height()), $(".input-window").val("");
                    message();
                    $('#friend').html(msg.uName);
                    $('input[name="userto"]').val(msg.uId);
                    //alert('你有一条来自“' + msg.uName + '”的新消息');
                    document.getElementById('message-alert').play();
                }
            },
            error: function () {
                //alert('服务器错误！');
            }
        });
    }, 1500);

    function message() {

        var a = show();
        setTimeout(function () {
                clear(a)
            }
            , 8e3);
        function show() {
            var a = 0, b = document.title;
            if (-1 == document.title.indexOf("\u3010"))var c = setInterval(function () {
                    a++, 3 == a && (a = 1), 1 == a && (document.title = "\u3010\u3000\u3000\u3000\u3011" + b), 2 == a && (document.title = "\u3010\u65b0\u6d88\u606f\u3011" + b)
                }
                , 500);
            return [c, b]
        }

        function clear(a) {
            a && (clearInterval(a[0]), document.title = a[1])
        }
    }
});
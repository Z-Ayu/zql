{__NOLAYOUT__}<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>跳转提示</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
    <style type="text/css">
        *{ padding: 0; margin: 0; }
        body{ background: #fff; font-family: "Microsoft Yahei","Helvetica Neue",Helvetica,Arial,sans-serif; color: #333; font-size: 16px; }
        .system-message{ padding: 24px 48px; }
        .system-message h1{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
        .system-message .jump{ padding-top: 10px; }
        .system-message .jump a{ color: #333; }
        .system-message .success,.system-message .error{ line-height: 1.8em; font-size: 36px; }
        .system-message .detail{ font-size: 12px; line-height: 20px; margin-top: 12px; display: none; }
    </style>
  <link rel="stylesheet" href="/static/css/custom.css"/>
  <link rel="stylesheet" href="/static/css/application.min.css"/>
  <link rel="stylesheet" href="/static/css/bootstrap.min.css"/>
  <link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
    <div class="system-message" style="max-width:600px;margin:auto;padding:20px;">
        <?php switch ($code) {?>
            <?php case 1:?>
            <div class="alert alert-success" role="alert"><h3><?php echo(strip_tags($msg));?></h3></div>
            <?php break;?>
            <?php case 0:?>
            <div class="alert alert-danger" role="alert"><h3><?php echo(strip_tags($msg));?></h3></div>
            <?php break;?>
        <?php } ?>
        <p class="detail"></p>
        <p class="jump">
            页面<b id="wait"><?php echo($wait);?></b>秒后自动<a id="href" href="<?php echo($url);?>">跳转</a>！
        </p>
    </div>
    <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>
<script src="/static/js/jquery-1.11.1.min.js"></script> 
<script src="/static/js/bootstrap.min.js"></script>
</body>
</html>

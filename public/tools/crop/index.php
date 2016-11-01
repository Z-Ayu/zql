<?php
  if (empty($_FILES)) {
    echo '<img src="static/upload/avatar/' .Session('uid'). '_128x128.jpg" />';
    echo '<form action="/tools/crop/index.php" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="avatar" />';
    echo '<input type="submit" value="上传" style="padding:6px 10px;background-color:#343434;color:#fff;border:none;" />';
    echo '</form>';
    exit;
  }
  //var_dump($_FILES);
  $imageFix = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp');

  if (!in_array($_FILES['avatar']['type'], $imageFix)) {
    exit('文件类型不允许，请重新选择图片！');
  }
  
  $filename = uniqid() .  substr($_FILES['avatar']['name'], strrpos($_FILES['avatar']['name'], '.'));
  
  if (!move_uploaded_file($_FILES['avatar']['tmp_name'], '../../static/upload/temp/' . $filename)) {
    exit('文件上传失败，请稍后重试！');
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Aspect Ratio with Preview Pane | Jcrop Demo</title>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  <script src="/static/js/jquery-1.12.4.min.js"></script>
  <script src="/tools/crop/js/jquery.Jcrop.js"></script>
  <script type="text/javascript">

    function updateCoords(c)
    {
      $('#x').val(c.x);
      $('#y').val(c.y);
      $('#w').val(c.w);
      $('#h').val(c.h);
    };

    function checkCoords()
    {
      if (parseInt($('#w').val())) return true;
      alert('Please select a crop region then press submit.');
      return false;
    };
    jQuery(function($){

      // Create variables (in this scope) to hold the API and image size
      var jcrop_api,
          boundx,
          boundy,

          // Grab some information about the preview pane
          $preview = $('#preview-pane'),
          $pcnt = $('#preview-pane .preview-container'),
          $pimg = $('#preview-pane .preview-container img'),

          xsize = $pcnt.width(),
          ysize = $pcnt.height();
      
      $('#target').Jcrop({
        aspectRatio: 1,
        onChange: updatePreview,
        onSelect: updatePreview,
        aspectRatio: xsize / ysize
      },function(){
        // Use the API to get the real image size
        var bounds = this.getBounds();
        boundx = bounds[0];
        boundy = bounds[1];
        // Store the API in the jcrop_api variable
        jcrop_api = this;

        // Move the preview into the jcrop container for css positioning
        $preview.appendTo(jcrop_api.ui.holder);
      });

      function updatePreview(c)
      {
        if (parseInt(c.w) > 0)
        {
          var rx = xsize / c.w;
          var ry = ysize / c.h;

          $pimg.css({
            width: Math.round(rx * boundx) + 'px',
            height: Math.round(ry * boundy) + 'px',
            marginLeft: '-' + Math.round(rx * c.x) + 'px',
            marginTop: '-' + Math.round(ry * c.y) + 'px'
          });
          $('#x').val(c.x);
          $('#y').val(c.y);
          $('#w').val(c.w);
          $('#h').val(c.h);
        }
      };

    });
  </script>
  <link rel="stylesheet" href="/tools/crop/demo/main.css" type="text/css" />
  <link rel="stylesheet" href="/tools/crop/demo/demos.css" type="text/css" />
  <link rel="stylesheet" href="/tools/crop/css/jquery.Jcrop.css" type="text/css" />
  <style type="text/css">

  /* Apply these styles only when #preview-pane has
     been placed within the Jcrop widget */
  .jcrop-holder #preview-pane {
    display: block;
    position: absolute;
    z-index: 2000;
    top: 10px;
    right: -280px;
    padding: 6px;
    border: 1px rgba(0,0,0,.4) solid;
    background-color: white;

    -webkit-border-radius: 6px;
    -moz-border-radius: 6px;
    border-radius: 6px;

    -webkit-box-shadow: 1px 1px 5px 2px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: 1px 1px 5px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 1px 1px 5px 2px rgba(0, 0, 0, 0.2);
  }

  /* The Javascript code will set the aspect ratio of the crop
     area based on the size of the thumbnail preview,
     specified here */
  #preview-pane .preview-container {
    width: 128px;
    height: 128px;
    overflow: hidden;
  }

  </style>

</head>
<body>

  <img src="/static/upload/temp/<?=$filename;?>" id="target" alt="[Jcrop Example]" style="max-width:600px" />

  <div id="preview-pane">
    <div class="preview-container">
      <img src="/static/upload/temp/<?=$filename;?>" class="jcrop-preview" alt="Preview" />
    </div>
  </div>

		<!-- This is the form that our event handler fills -->
		<form action="/index/user/avatar.html" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
      <input type="hidden" name="filename" value="static/upload/temp/<?=$filename;?>" />
			<input type="submit" value="确定裁剪" class="btn btn-large btn-inverse" />
		</form>


</body>
</html>
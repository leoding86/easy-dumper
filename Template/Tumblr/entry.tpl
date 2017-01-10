<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF8">
    <meta name="description" content="The HTML5 Herald">
    <meta name="author" content="SitePoint">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $blog ?></title>

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
    <![endif]-->
    <style>
    * {margin:0; padding:0;}
    body {background: #eee;}
    a {text-decoration: none;}
    .fl {float:left;}
    .fr {float:right;}
    #container {width:480px; margin:10px auto;}
    .title {margin:20px 0;}
    .title h1 {font-size: 18px; text-shadow: 0 2px 0 #ccc;}
    .box {width:100%; border-radius: 5px; box-shadow: 0px 2px 0px #ccc; overflow: hidden;}
    .posts .item {margin:10px 0;}
    .posts .content {padding:8px;background:#fff;}
    .posts .content a {color: #555;}
    .posts .content a:hover {color: #333;}
    .posts .content a:visited {color: #999;}

    .popup {position:fixed; width: 100%; height: 100%; top: 0; left: 0;  background: rgba(0,0,0,0.5); z-index: 99; display: none;}
    .popup .close {position: absolute; width: 50px; left: -55px; top: 10px; line-height:30px; text-align:center; font-size:12px; border-radius:3px; background: #fff; cursor: pointer}
    .popup .container {width: 50%; height: 100%; margin: 0 auto; position: relative;}
    </style>
</head>
<body>
<div id="container">
    <div class="title">
        <h1><a href="http://<?php echo $blog; ?>"><?php echo $blog; ?></a></h1>
    </div>
    <div class="posts">
        <?php foreach ($posts as $post) : ?>

        <div class="item box">
            <div class="content">
                <a href="<?php echo $post['url']; ?>" data-loaded="false"><span class="fr"><?php echo $post['count']; ?> Pcs.</span><span><?php echo $post['type']; ?></span></a>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>
<div class="popup">
    <div class="container">
        <div class="close">Close</div>
        <!-- <iframe src="" frameborder="0" border="0"></iframe> -->
    </div>
</div>
</body>
<script>
    var $popup = document.querySelector('.popup');

    function closePopup() {
        $popup.style.display = 'none';
        $popup.querySelector('iframe').remove();
        document.body.style.overflow = 'auto';
    }
    document.querySelector('.popup .close').addEventListener('click', closePopup);
    document.querySelector('.popup').addEventListener('click', closePopup);

    var $posts = document.querySelectorAll('.posts .item a');
    for (var i = 0, l = $posts.length; i < l; i++) {
        (function($post) {
            if ($post.getAttribute('data-loaded') != 'true') {
                $post.addEventListener('click', function(e) {
                    var iframe = document.createElement('iframe');
                    iframe.frameborder = 0;
                    iframe.width = '100%';
                    iframe.height = '100%';
                    iframe.src = $post.href;
                    iframe.style.border = '0';
                    $popup.querySelector('.container').appendChild(iframe);
                    $popup.style.display = "block";
                    $post.setAttribute('data-loaded', 'true');
                    document.body.style.overflow = 'hidden';
                    e.preventDefault();
                    return false;
                });
            }
        })($posts[i]);
    }
</script>
</html>
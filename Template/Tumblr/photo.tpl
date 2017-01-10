<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF8">
    <meta name="description" content="The HTML5 Herald">
    <meta name="author" content="SitePoint">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title></title>

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
    <![endif]-->

    <style>
    * {margin:0; padding:0;}
    #container {width:100%; margin:10px auto;}
    .box {width:100%; border-radius: 5px; overflow: hidden;}
    .photos .item {margin-bottom:10px;}
    .photos .photo {background:#eee;}
    .photos .photo img {display:block; width:100%;}
    .caption {background: #eee;}
    .caption .content {padding:10px;}
    </style>
</head>
<body>
    <div id="container">
        <div class="photos">
            <?php foreach ($photos as $photo): ?>

            <div class="item box">
                <div class="photo">
                    <img src="<?php echo $photo; ?>" alt="<?php echo $photo; ?>">
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        <?php if (!empty($caption)) : ?>

        <div class="caption box">
            <div class="content">
                <?php echo $caption ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>

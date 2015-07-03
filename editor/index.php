<?php

$pass=['Gammanet2015','jemyrazem2015'];

if ($pass) {	
    if (isset($_POST['_pass'])) {
            $_COOKIE['PASS']=$_POST['_pass'];
            SetCookie('PASS',$_POST['_pass']);
    }
    if (!isset($_COOKIE['PASS']) || !in_array($_COOKIE['PASS'],$pass)) {
        
        die('<html><head><title>Please login</title></head><body><form method="post" style="text-align:center; margin-top:200px;"><input type="password" name="_pass"><input type="submit" value="Password"></form></body></html>');
    }
}

$dir=__DIR__.'/../server/rest/observer';
$labels=[];

foreach (scandir($dir) AS $lang){
    if (!is_dir("$dir/$lang")) continue;
    if ($lang[0]=='.') continue;
    foreach(scandir("$dir/$lang") AS $f)
    {
        if ($f[0]=='.') continue;
        $labels[$lang][]=$f;
    }
}

if (isset($_POST['edit']) && isset($_POST['data']) ) {
    $data=$_POST['data'];
    $data=str_replace('<pre>','',$data);
    $data=str_replace('</pre>',"\n\n{include:header.html}",$data);
    
    $data.="{include:footer.html}";
    
    $data=str_replace("<p>&nbsp;</p>",'',$data);
    $data=str_replace("\r",'',$data);
    $data=str_replace('&oacute;','ó',$data);
    $data=str_replace('http://beta.jemyrazem.pl/','{app_root}',$data);
    $data=str_replace('https://www.jemyrazem.pl/','{app_root}',$data);
    
    $data=trim($data);
    
    if (strlen($data)) file_put_contents("$dir/".$_POST['edit'],$data);
    
}


if (isset($_GET['edit'])) {
    $data=file_get_contents("$dir/".$_GET['edit']);
    $pos=strpos($data,"\n\n");
    $data='<pre>'.substr($data,0,$pos).'</pre>'.substr($data,$pos);
    $data=str_replace('{include:header.html}','',$data);
    $data=str_replace('{include:footer.html}','',$data);
    
    //die(htmlspecialchars($data));
}



?>
<html>
<head>
    <meta charset="utf-8">
    <title>Mail editor</title>
  
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">

    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>

    <!-- fonts -->
    <link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <!-- /fonts -->
    
    <script src="ckeditor.js"></script>
    
    <!-- style -->
    <style>
     
    </style>
  
  
</head>
<body>
    <ul class="menu nav nav-tabs">
    <?php foreach($labels AS $lang=>$files): ?>
        <li role="presentation" class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false"><?php echo $lang;?></a>
            <ul class="dropdown-menu" role="menu">
                <?php foreach($files AS $file): ?>
                    <li>
                        <a href="index.php?edit=<?php echo $lang;?>/<?php echo $file;?>"><?php echo $file;?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
    
        <li>
            <a href="index.php?deploy=1" onclick="return confirm('Jesteś pewny')">deploy &raquo; beta</a>
        </li>
    </ul>
    
    
    <?php if (isset($_GET['edit'])): ?>
        <b>&nbsp; &nbsp; <?php echo $_GET['edit'];?></b>
        <form action="index.php" method="post">
            <input type="hidden" name="edit" value="<?php echo $_GET['edit'];?>"/>
            
            <div style="text-align:right;padding: 7px"><input style="position: relative; top:38px;" type="submit" value="Save"></div>
            <textarea id="mailmsg" name="data" style=""><?php echo $data; ?></textarea>

        </form>
        
        <script>
            
            CKEDITOR.replace( 'mailmsg', {
                extraAllowedContent: '*{*}',
                height: '70%'
            });
            
        </script>
    <?php endif; ?>
    
    
    <?php
    if (isset($_GET['deploy'])) {
        echo '<pre>';
        
        //system("cd ../tools; php langs.php; ");
        system("cd ../appengine; php deploy.php; echo OK");
        system("svn ci -m maile ../server/rest/observer");
        echo '</pre>';
    }
    ?>
</body>
</html>
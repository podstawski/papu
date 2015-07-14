<?php
	ini_set('display_errors',0);
	$names=array();

	
	foreach (scandir(__DIR__.'/rest/controllers') AS $c)
	{
		if (is_dir(__DIR__.'/rest/controllers/'.$c)) continue;
		$controller=str_replace('Controller.php','',$c);
		if (!$controller) continue;
		require_once __DIR__.'/rest/controllers/'.$c;
		$controller_name=$controller.'Controller';
		$names[]=$controller;
		$ctrl=new $controller_name('a','b');
		$methods=get_class_methods($ctrl);
		foreach($methods AS $m)
		{
			if ($m[0]=='_') continue;
			$pos=strpos($m,'_');
			if (!$pos) continue;
			$names[$controller.'/'.substr($m,$pos+1)]=$controller.'/'.substr($m,$pos+1);
		}
	}
	
	if (isset($_SERVER['REQUEST_URI']))
	{
	    $uri = $_SERVER['REQUEST_URI'];
	    $root = dirname($_SERVER['SCRIPT_NAME']);
    
	    $uri = str_replace($root, '', $uri);
	    if ($root != '/') $root .= '/';
	}
	else $root='/';	
	
	

?><html>
	<head>
		<meta charset="utf-8">
		<title>REST tester</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	</head>
	<body>
		<p>
			<a href="<?php echo $root;?>admin/">Admin pane</a>
			<a href="<?php echo $root;?>rest/user/google?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'])?>">Login Google</a>
			<a href="<?php echo $root;?>rest/user/facebook?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'])?>">Login FB</a>
		</p>
		
		
		<select id="url" style="width:346px" value="user">
			<?php foreach ($names AS $n)
			{
				echo '<option>'.$n.'</option>';	
			}?>
		</select>
		<input style="width:50px" id="id" placeholder="id"/><br>
		<textarea id="data" style="width:400px; height:100px">{}</textarea>
		<form id="captcha">
			<script type="text/javascript" src="//www.google.com/recaptcha/api/challenge?k=6Le55fwSAAAAAJWv435Os_Jrw_x7vibjKpXBUG9H"></script>
		</form>
		<div>
		<input type="button" id="b_get" value="GET"/>
		<input type="button" id="b_put" value="PUT"/>
		<input type="button" id="b_post" value="POST"/>
		<input type="button" id="b_delete" value="DELETE"/>
		<input type="button" id="b_options" value="OPTIONS"/>
		</div>
		
		<pre id="result">
			
		</pre>
		
		<form id="upload" method="POST" enctype="multipart/form-data" style="display: none" target="_blank">
			<input type="file" name="files" size="40" accept="image/*" capture="camera">
			<input type="submit" value="Send">
		</form>
		
	</body>
	
	
	<script>	

		$.fn.serializeObject = function()
		{
		    var o = {};
		    var a = this.serializeArray();
		    $.each(a, function() {
			if (o[this.name] !== undefined) {
			    if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			    }
			    o[this.name].push(this.value || '');
			} else {
			    o[this.name] = this.value || '';
			}
		    });
		    return o;
		};

		$('#url').change(function () {
			if ($(this).val()=='user' && false) {
				$('#data').val('{ "email":"piotr@gammanet.pl","firstname": "piotr","lastname": "podstawski","password": "alamakota"}');
			} else $('#data').val('{}');
		});

		$.get('<?php echo $root;?>rest/user/time?d='+encodeURIComponent(new Date()));
		
		$('input[type="button"]').click(function() {
			$('#result').html('');
			
			var data=JSON.parse($('#data').val());
			if ($(this).val()=='POST' && $('#url').val()=='user') {
				data.captcha=$('#captcha').serializeObject();
			}
			
			var url='<?php echo $root;?>rest/'+$('#url').val();
			var id=$('#id').val().trim();
			if (id.length) url+='/'+id;
			//url='http://robson.webkameleon.com/eat/server/rest/'+$('#url').val();
			$.ajax({
				url: url,
				type: $(this).val(),
				dataType: 'json',
				data: data,
				xhrFields: {
					withCredentials: true
				},
				success: function (data, textStatus, request) {
					$('#result').html(JSON.stringify(data));
					//console.log(request.getAllResponseHeaders());
					if (!data.status) Recaptcha.reload();
					
					if ($('#url').val()=='image')
					{
						if (typeof(data.url)!='undefined') $('#upload').attr('action',data.url).fadeIn();
						console.log(data);
					}
				}
			});
		});
		
		var d=new Date();
		console.log(d);
	</script>
</html>

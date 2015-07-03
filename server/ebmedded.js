

function jemyrazem_height_check__RANDOM_() {
    
    $.ajax({
        url: jemyrazem_height__RANDOM_,
        type: "GET",
        xhrFields: {
            withCredentials: true
        },
        success: function (height) {
            if (parseInt(height)>0) {
                $('#'+jemyrazem_random__RANDOM_).height(parseInt(height)+50).fadeIn(500);
            } else {
                setTimeout(jemyrazem_height_check__RANDOM_,200);
            }  
        }
    });
    
}

function jemyrazem_jquery_loaded__RANDOM_() {
    
    var iframe=$('<iframe src="'+jemyrazem_url__RANDOM_+'" id="'+jemyrazem_random__RANDOM_+'" scrolling="no" seamless="seamless" style="border:0; overflow: hidden; width:100%; height:100px;"/>');
    var url=jemyrazem_url__RANDOM_.replace('https://','').replace('http://','');
    $('script[src*="'+url+'"]').each(function() {        
        if ($(this).attr('src').substr(0,jemyrazem_url__RANDOM_.length)) {
            $(this).after(iframe);
        }

    });
    setTimeout(jemyrazem_height_check__RANDOM_,100);
}


if (typeof $ == "undefined") {
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = "//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js";
    script.onload = jemyrazem_jquery_loaded__RANDOM_;
    document.getElementsByTagName("head")[0].appendChild(script);
} else {
    jemyrazem_jquery_loaded__RANDOM_();
}
var t = 0;

var i = setInterval(function(){
	var e = document.querySelectorAll('.ccm_col_content_cookieitem-radiowrap > label:first-child .ccm_col_content_cookieitem-radiocheck');
	t++;
	
	if (e.length > 0)
	{
		e.forEach(function(element) {
			element.click();
		});
		
		document.querySelector('button.ccm_btn').click();
	}
	
	if (e.length > 0 || t == 200)
		clearInterval(i);
}, 500);
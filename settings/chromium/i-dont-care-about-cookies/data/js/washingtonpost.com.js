if (/\/gdpr-consent\//.test(document.location.href))
{
	var i = setInterval(function() {
		var checkbox = document.querySelector('.gdpr-consent-container .consent-page:not(.hide) #agree');
		
		if (checkbox)
		{
			checkbox.checked = true;
			checkbox.dispatchEvent(new Event('change'));
			
			document.querySelector('.gdpr-consent-container .consent-page:not(.hide) .continue-btn.button.accept-consent').click();
			clearInterval(i);
		}
	}, 500);
}
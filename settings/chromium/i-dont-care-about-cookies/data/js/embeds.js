(function() {
	const classname = Math.random().toString(36).replace(/[^a-z]+/g, '');
	
	var url = document.location.href,
		is_audioboom = false,
		is_dailymotion = false,
		is_dailybuzz = false;
	
	if (url.indexOf('embeds.audioboom.com') > -1)
		is_audioboom = true;
	else if (url.indexOf('dailymotion.com/embed') > -1)
		is_dailymotion = true;
	else if (url.indexOf('dailybuzz.nl/buzz/embed') > -1)
		is_dailybuzz = true;
	
	
	function searchEmbeds() {
		setTimeout(function() {
			
			// audioboom.com iframe embeds
			if (is_audioboom) {
				document.querySelectorAll('div[id^="cookie-modal"] .modal[style*="block"] .btn.mrs:not(.' + classname + ')').forEach(function(button) {
					button.className += ' ' + classname;
					button.click();
				});
			}
			
			// dailymotion.com iframe embeds
			else if (is_dailymotion) {
				document.querySelectorAll('.np_DialogConsent-accept:not(.' + classname + ')').forEach(function(button) {
					button.className += ' ' + classname;
					button.click();
				});
			}
			
			// dailybuzz.nl iframe embeds
			else if (is_dailybuzz) {
				document.querySelectorAll('#ask-consent #accept:not(.' + classname + ')').forEach(function(button) {
					button.className += ' ' + classname;
					button.click();
				});
			}
			
			else {
				// Twitter
				document.querySelectorAll('.twitter-tweet-rendered:not(.' + classname + ')').forEach(function(e) {
					if (!e.shadowRoot) {
						e.className += ' ' + classname;
						return;
					}
					
					var button = e.shadowRoot.querySelector('.js-interstitial:not(.u-hidden) .js-cookieConsentButton');
					
					if (button) {
						e.className += ' ' + classname;
						button.click();
					}
				});
			}
			
			searchEmbeds();
		}, 1000);
	}

	var start = setInterval(function() {
		var html = document.querySelector('html');
		
		if (!html || (new RegExp(classname)).test(html.className))
			return;
		
		html.className += ' ' + classname;
		searchEmbeds();
		clearInterval(start);
	}, 500);
})();
function isValidUrl(toURL) {
	return (toURL || '').match(/^(?:https?:?\/\/)?(?:[^.(){}\\\/]*)?\.?forbes\.com(?:\/|\?|$)/i);
}

function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

if (/\/consent\/\?toURL\=/.test(document.location.href))
{
	document.cookie = "notice_preferences=2:";
	
	setTimeout(function() {
		var toURL = getUrlParameter("toURL");
		document.location.href = isValidUrl(toURL) ? toURL : "https://www.forbes.com/";
	}, 1000);
}
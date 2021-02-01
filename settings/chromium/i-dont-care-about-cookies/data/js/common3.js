function getItem(h)
{
	switch (h)
	{
		case 'phoenix.de': return {strict: false, key: 'user_anonymous_profile', value: '{"config":{"tracking":false,"userprofile":false,"youtube":false,"twitter":false,"facebook":false,"iframe":false,"video":{"useSubtitles":false,"useAudioDescription":false}},"votings":[],"msgflash":[],"history":[]}'};
		
		case 'm.aliexpress.com': return {strict: false, key: 'MSITE_GDPR', value: 'true'};
		
		case 'krant.volkskrant.nl':
		case 'krant.dg.nl':
		case 'krant.demorgen.be':
		case 'krant.trouw.nl':
		case 'krant.ad.nl':
		case 'krant.parool.nl':
		case 'krant.ed.nl':
			return [
				{strict: false, key: 'vl_disable_tracking', value: 'true'},
				{strict: false, key: 'vl_disable_usecookie', value: 'selected'}
			];
	}
	
	
	const parts = h.split('.');
	
	if (parts.length > 2)
	{
		parts.shift();
		return getItem(parts.join('.'));
	}
	
	return false;
}


let	h = document.location.hostname.replace(/^w{2,3}\d*\./i, ''),
	counter = 0,
	items = getItem(h);

if (items) {
	(items instanceof Array ? items : [items]).forEach(function(item) {
		let value = localStorage.getItem(item.key);
		
		if (value == null || (item.strict && value != item.value)) {
			localStorage.setItem(item.key, item.value);
			counter++;
		}
	});
	
	if (counter > 0)
		document.location.reload();
}
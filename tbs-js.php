	function trackclick(url, aid, atxt, jump_url) {
    if(document.images){
        (new Image()).src=jump_url+url+
        "&aid="+aid+"&sponsor="+atxt+"&loc="+document.location;
    }
    return true;
	}
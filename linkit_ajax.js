var api_address = "www.123linkit.com";

function AjaxManager(pubkey, privkey){
	this._pubkey = pubkey;
	this._privkey = privkey;
	this._url = getPluginDir()+"/123linkit-affiliate-marketing-tool/simpleproxy.php";
}

AjaxManager.prototype = {
	setKeys: function(pubkey, privkey){
		this._pubkey  = pubkey;
		this._privkey = privkey;
	},
	ajaxCall: function(data, type){
		if(type == '_pubkey'){
			data._pubkey = this._pubkey;
		}else{
			data._privkey = this._privkey;
		}
		return jQuery.post(this._url, data);
	}
};

function LinkitMaster(tinyMCE, pubkey, privkey){
	this._ajaxManager = new AjaxManager(pubkey, privkey);
	this._tinyMCE = tinyMCE;
}

LinkitMaster.prototype = {
	writeHTML: function() {
		jQuery('.linkit_content').html("<div class='linkit_header'><img src='http://" + api_address + "/images/plugin_header.jpg' /></div><a href='#' class='update_post'>Add Affiliate Links</a>");
	},
	getContent: function(){
        var content = "";
        if (this._tinyMCE.activeEditor === null) { content = jQuery("#content").val(); }
        else { content = this._tinyMCE.activeEditor.getContent(); }
		return content;
	},
	advertise: function(){
		content = this.getContent();		
		links = linkit._ajaxManager.ajaxCall({'url': 'blogs.json', 'content': linkit.getContent()}, '_pubkey');
		return links;
	}
};

jQuery(document).ready(function(){
	key = getKeys()._pubkey;
	
	if(window.tinyMCE === undefined) {return ;}
	var linkit = new LinkitMaster(tinyMCE, key._pubkey, key._privkey);
	linkit.writeHTML();
	
	jQuery('.update_post').click(function(){
		links = linkit.advertise();
	});
});

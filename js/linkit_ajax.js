function AjaxManager(pubkey, privkey){
	this._pubkey = pubkey;
	this._privkey = privkey;
    this._url = getPluginDir() + "/123linkit-affiliate-marketing-tool/simpleproxy.php";
}

AjaxManager.prototype = {
	setKeys: function(pubkey, privkey){
		this._pubkey  = pubkey;
		this._privkey = privkey;
	},
    writeTable: function(data){
        jQuery('div.ajax_working').fadeOut('slow');
        var blog_id = getBlogId();
		var contents = 0;
		html = "Thanks for using 123LinkIt! Our goal is to automate as much as this process as possible which is why we're working on automating the embedding of the links. For now, please add them manually. This is done by right-clicking on 'Copy Link' and embedding the link into the keyword we recommend.";
        html += "<table  class='tablesorter'><thead><tr><th>Keyword</th><th>Advertiser</th><th>Advertising Url</th><th>7 Day EPC</th><th>3 Month EPC</th><th>Copy Link</th></tr></thead><tbody>";
        for(keyword in data.advertised){
            for(link in data.advertised[keyword]){
				contents = contents + 1;
                html += "<tr><td>"+keyword+"</td><td>" + data.advertised[keyword][link].link.advertiser_name + "</td><td>"+data.advertised[keyword][link].link.link_url+"</td><td>"+data.advertised[keyword][link].link['7dayepc']+"</td><td>"+data.advertised[keyword][link].link["3monepc"]+"</td><td>";
                html +="<a href="+getPluginDir()+"/123linkit-affiliate-marketing-tool/links.php?bid="+blog_id+"&pid=1&aid="+data.advertised[keyword][link].link.advertiser_id+"&lid="+data.advertised[keyword][link].link.id+"&key="+getKeys()['_pubkey']+" rel=nofollow>"+keyword+"</a></td></tr>";
            }
        }
		if(contents == 0)
			html += "<tr><td colspan = '6'>We couldn't find relevant affiliate link recommendations for this post</td></tr>";
        html +="</tbody></table>"
        jQuery("div.result").html(html).fadeIn("slow");
        jQuery("table").tablesorter();
    },
    advertise: function(data){
        this.ajaxCall(data, '_privkey', this.writeTable);
    },
    showWork: function(data){
        jQuery("div.result").fadeOut("slow");
        jQuery("div.ajax_working").fadeIn("slow");
    },
	ajaxCall: function(data, type, func){
		if(type == '_pubkey'){
			data['_pubkey'] = this._pubkey;
		}else{
			data['_privkey'] = this._privkey;
		}
		jQuery.ajax({
                   type: "POST",
                   url: this._url,
                   beforeSend: this.showWork,
                   data: data,
                   dataType: "json",
                   async: false,
                   success: func
        });
	},
}

function LinkitMaster(tinyMCE, pubkey, privkey){
	this._ajaxManager = new AjaxManager(pubkey, privkey);
	this._tinyMCE = tinyMCE;
}

LinkitMaster.prototype = {
	getContent: function(){
		return this._tinyMCE.activeEditor.getContent();
	},
	advertise: function(){
		content = this.getContent();
		id = getBlogId();
        if(id){
            links = this._ajaxManager.advertise({'url': 'getAdvertisers/'+id+'/posts/advertise.json', 'content': this.getContent()});
        }
        //TODO Error out and show it for me :)
    },
}

jQuery(document).ready(function(){
    
    key = getKeys();
	var linkit = new LinkitMaster(tinyMCE, key['_pubkey'], key['_privkey']);
	
	jQuery('.update_post').click(function(){
		links = linkit.advertise();
        return false;
	});
});

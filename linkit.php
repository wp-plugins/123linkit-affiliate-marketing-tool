<?php
/*
Plugin Name: 123Linkit Affiliate Marketing Tool
Plugin URI:  http://www.123linkit.com/general/download
Description: 123LinkIt Affiliate Plugin - Generate money easily from your blog by transforming keywords into affiliate links. No need to apply to affiliate networks or advertisers - we do it all for you. Just pick from our list of recommendations and you're good to go! Navigate to Settings -> 123LinkIt configuration to get started.
Version: 0.1.1
Author: 123Linkit, LLC.
Author URI: http://www.123linkit.com/
*/

$api_address = "174.143.204.12";

//Action adds all the admin menus that I want
add_action('admin_menu', 'linkit_custom_advertise_box');
add_action("admin_init", "register_linkitsettings");
add_action("admin_print_scripts", "linkit_admin_head");
add_action("admin_print_styles", "linkit_admin_styles");
if(get_option('linkit_allow_auto') == 1){
    add_filter('the_content', 'change_content');
}

function register_linkitsettings(){
	register_setting("linkit-options", "linkit_keys");
	register_setting("linkit-options", "linkit_allow_auto");
	wp_register_style('tblcss', WP_PLUGIN_URL . '/123Linkit/css/jquery.tablesorter.css');
	wp_register_style('linkitcss', WP_PLUGIN_URL . '/123Linkit/css/linkit.css');
}

function linkit_custom_advertise_box(){
	if( function_exists('add_meta_box')){
		$box = add_meta_box('linkit_advertiseid', __('Advertise Post', 'linkit_textdomain'),
					'linkit_inner_custom_box', 'post', 'side', 'high');
	}
	add_options_page('123Linkit Configuration', "123Linkit Configuration", 8, "123linkit_menu", "linkit_options");

}

function linkit_proxy_url(){
	return get_bloginfo('wpurl').'/wp-content/plugins/123Linkit/simpleproxy.php';
}

function linkit_admin_head(){
//We use need some simple convenience functions here
$keys = get_option('linkit_keys');
?>
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>js/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
    <script type="text/javascript">
        function getPluginDir(){
            return"<?php echo WP_PLUGIN_URL; ?>";
        }
	    function getKeys(){ 
            return {'_pubkey': '<?php echo $keys['_pubkey'];?>', '_privkey':'<?php echo $keys['_privkey'];?>'};
        }
        function getBaseUrl(){
            return "<?php echo get_bloginfo('url'); ?>";
        }
        function getBlogId(){
            var id = false;
            jQuery.ajax({
                type: "POST",
                url: getPluginDir()+"/123Linkit/simpleproxy.php",
                data: { 
                       url: "getBlogId/view.json",
                        baseurl: getBaseUrl(),
                        _pubkey: getKeys()['_pubkey']
                    },
                dataType: "json",
                async: false,
                success: function(data){
                        if(data.blogs != false){
                            id = data.blogs.blog.id;
                        }
                    }
            
                });
             return id;
        }
    </script>
<?php
	wp_enqueue_script('linkitscripts', WP_PLUGIN_URL .'/123Linkit/js/linkit_ajax.js', array('jquery'), '0.1');
	wp_enqueue_script('tblsorter', WP_PLUGIN_URL .'/123Linkit/js/jquery.tablesorter.min.js', array('jquery'), '0.1');
}
function linkit_admin_styles(){
	wp_enqueue_style('tblcss');
	wp_enqueue_style('linkitcss');
}
function change_content($content){
    //TODO automatically replace links dynamically based on blog type   
    $keys = get_option('linkit_keys');
   
    global $api_address;

    $url = "http://$api_address/api/";
    $blg = "getBlogId/view.json";
    try{
    
        $data = "baseurl=".get_bloginfo('url')."&_pubkey=".$keys['_pubkey']; 

        $response = posturl($url.$blg, $data);

		$response = json_decode($response);
		$id = $response->{'blogs'}->{'blog'}->{'id'};
    
        $adv = "getAdvertisers/$id/posts/advertise.json";
        $data = "content=$content&_privkey=".$keys['_privkey'];
        $response = posturl($url.$adv,$data);
        if($response['advertised']){
       /*
            $key_to_pos = array();
            foreach($keywords as $keyword => $phrase){
            //More positions == More relevant (Not completely but simple enough for now)
                   //$positions = $this->Common->strallpos($content, $phrase['word']);
                   if(sizeof($positions) >= 1 && $positions != false){
                      //Get all the links that go with this advertiser
                      echo "woah";
                      //$links = $this->link->findAllByAdvertiserId($advertiser['advertisercategory']['advertiser_id']);
                      //$return_array[$phrase['word']] = $positions;
                      //$links_array[$phrase['word']] = $links;
                   }
            }*/
            echo "yes!";
        }
        return $content;

    }catch(Exception $e){
        
        return $content;
    
    }
}
function posturl($url, $data){
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url); //This really isn't the way it's supposed to be done but, can't figure out the problem
	curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$results = curl_exec($ch);
	$info = curl_getinfo( $ch );
    if ($info['http_code'] != 200) {
	     return array(false, "Problem reading data from $url : " . curl_error( $ch ) . "\n");
	}
	curl_close($ch);
	if(!$results){
		$results = false;
	}
	return $results;

}
function linkit_inner_custom_box(){
	?>
     <div class='linkit_main'>
        <div class='linkit_content'>
	        <div class='linkit_header'>
                <img src='http://<?php global $api_address; echo $api_address; ?>/images/plugin_header.jpg' />
                <a href='#' class='update_post'>Add Affiliate Links</a>
                <div class="notify_div">
                    <div class="ajax_working" style='display: none;'>
                        <img src="<?php echo WP_PLUGIN_URL;?>/123Linkit/css/ajax-loader.gif"/>
                        Working...
                    </div>
                    <div class="error">
                    </div>
                </div>
            </div>
            <div class='result'>
            </div>
        </div>
    </div> 
<?php
}
function linkit_options(){
	if(get_option("linkit_keys") != ""){
		$keys = get_option("linkit_keys"); 
		$auto = get_option('linkit_allow_auto');
		
		//Do they have value on? If so echo checked else do nothing
		//Sorry about the ternary but really? who wants to write all those braces!
		$checked = $auto == 1 ? "checked": "";
	}
	?>
		
<div class="wrap" action="options.php">
	<h2>123Linkit Advertising Plugin Settings</h2>
	<p>The form below allows you to append your public and private key that was given to you by 123Linkit. <p>
	<p>If you haven't signed up click <a href="http://www.123linkit.com/users/new">here</a>.</p>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
		<table class="form-table">
			<tr valign="top">
			<th scope="row">Public API Key:</th>
			<td><input type="text" size="32" name='linkit_keys[_pubkey]' value="<?php echo $keys['_pubkey']; ?>" /><span class="description"></span></td>
			<tr valign="top">
			<th scope="row">Private API Key:</th>
			<td><input type="text" size="32" name='linkit_keys[_privkey]' value="<?php echo $keys['_privkey']; ?>" /><span class="description"></span> </td>
		</table>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="linkit_keys,linkit_allow_auto" />
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
	<?php
}

?>

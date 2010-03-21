<?php
/*
Plugin Name: 123Linkit Affiliate Marketing Tool
Plugin URI:  http://www.123linkit.com/general/download
Description: 123LinkIt Affiliate Plugin - Generate money easily from your blog by transforming keywords into affiliate links. No need to apply to affiliate networks or advertisers - we do it all for you. Just pick from our list of recommendations and you're good to go! To get started, sign up at 123LinkIt.com for an account and Navigate to Settings -> 123LinkIt configuration to input your API keys.
Version: 0.1.12
Author: 123Linkit, LLC.
Author URI: http://www.123linkit.com/
*/

$api_address = "www.123linkit.com";

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
	wp_register_style('tblcss', WP_PLUGIN_URL . '/123linkit-affiliate-marketing-tool/css/jquery.tablesorter.css');
	wp_register_style('linkitcss', WP_PLUGIN_URL . '/123linkit-affiliate-marketing-tool/css/linkit.css');
}

function linkit_custom_advertise_box(){
	if( function_exists('add_meta_box')){
		$box = add_meta_box('linkit_advertiseid', __('Advertise Post', 'linkit_textdomain'),
					'linkit_inner_custom_box', 'post', 'side', 'high');
	}
	add_options_page('123Linkit Configuration', "123Linkit Configuration", 8, "123linkit_menu", "linkit_options");

}

function linkit_proxy_url(){
	return get_bloginfo('wpurl').'/wp-content/plugins/123linkit-affiliate-marketing-tool/simpleproxy.php';
}

function linkit_admin_head(){
//We use need some simple convenience functions here
$keys = get_option('linkit_keys');
?>
	<script type="text/javascript" src="http://www.123linkit.com/javascripts/tiny_mce.js"></script>
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
    </script>
<?php
	wp_enqueue_script('linkitscripts', "http://www.123linkit.com/javascripts/client.js", array('jquery'), false);
	wp_enqueue_script('tblsorter', "http://www.123linkit.com/javascripts/jquery.tablesorter.min.js", array('jquery'), false);
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
    
    $data = "baseurl=".get_bloginfo('url')."&_pubkey=".$keys['_pubkey']; 

    $response = posturl($url.$blg, $data);

		$response = json_decode($response);
		$id = $response->{'blogs'}->{'blog'}->{'id'};
    
    $adv = "getAdvertisers/$id/posts/advertise.json";
    $data = "content=$content&_privkey=".$keys['_privkey'];
    $response = posturl($url.$adv,$data);
    if($response['advertised']) echo "yes!";
    return $content;
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
                        <img src="<?php echo WP_PLUGIN_URL;?>/123linkit-affiliate-marketing-tool/css/ajax-loader.gif"/>
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

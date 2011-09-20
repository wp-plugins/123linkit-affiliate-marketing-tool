<?php
/*
Plugin Name: 123Linkit Affiliate Marketing Tool
Plugin URI:  http://www.123linkit.com/general/download
Description: Generate money easily from your blog by transforming brand names and product keywords into affiliate links. There’s no need to apply to affiliate networks or programs - we do it all for you. Just install the plugin, sync your posts and we’ll automatically add relevant, money-making affiliate links to your blog.
Version: 1.3.1
Author: 123Linkit, LLC.
Author URI: http://www.123linkit.com/
*/

class LinkIt {

	// Every cached post is stored for only 5 hours before it is refreshed from the 123LinkIt server
	const LINKIT_CACHE_EXPIRY_MINUTES = 300;

	/**
	 * TODO Perform full sync using cron
	 */


	/**
	 * Constructor for the class.
	 * Adds all of the actions to plugin hooks that are required by the plugin
	 */
	public function __construct() {
		//This plugin requires sessions for message retention
		session_start();
		
		// Add WP action hooks
		add_action( 'admin_notices', array($this, 'admin_notices') );  // Display a notice to register at the top of the page
		add_action( 'init', array($this, 'init') );  // Initialize various values
		add_action( 'admin_init', array($this, 'admin_init') );  // Output the admin CSS
		add_action( 'admin_menu', array($this, 'admin_menu') );  // Add some stuff to the admin menu
		add_action( 'linkit_cron', array($this, 'linkit_cron') );  // Periodically check for un-sync'ed posts
		add_action( 'publish_post', array($this, 'publish') );  // When a post is published, sync it!

		// Add WP filter hooks
		add_filter( 'posts_results', array($this, 'post_results'), 2);  // Make sure we display a post with its affiliate links
		add_filter( 'wp_trim_excerpt', array($this, 'wp_trim_excerpt'), 2 );  // Don't try to display affiliate links in excerpts
		add_filter( 'the_content', array($this, 'the_content'), 11 );  // Replaces some ampersands in the output

		// Register the hook for activation of this plugin
		register_activation_hook(__FILE__, array($this, 'activation'));

		// Make the API object
		$this->api = new LinkItAPI();

		// If you don't have a real email address, unset all of the LinkIt options
		$email = get_option( "LinkITEmail" );
		if ( strlen( $email ) <= 4 && strlen( $email ) > 0 ) {
			foreach($this->options() as $key => $default) {
				delete_option('LinkIt' . $key);
			}
		}
	}

	/**
	 * Lists the options used by this plugin and their default values
	 * @return array Key/Value array of named options and their defaults.
	 */
	function options() {
		$options = array(
			'Email' => '0',
			'PrivateKey' => '0',
			'PublicKey' => '0',
			'NPosts' => '0',
			'NLinks' => '0',
			'AvgLinks' => 'N/A',
			'NCommissions' => '0',
			'TotalSelf' => '0 USD',
			'NReferrals' => '0',
			'TotalReferrals' => '0 USD',
			'ReceivedMoney' => '0 USD',
			'RemainingBalance' => '0 USD',
			'Config' => '',
			'BlogCategory' => '',
			'LastCron' => 0,
			'LastSync' => 0,
			'BlogCategory' => 0,
		);
		return $options;
	}
	
	/**
	 * Return a list of blog categories
	 * This function should ultimately periodically query the API for the list of 
	 * category ids, and cache it locally instead of having a hard-coded list
	 */
	function get_categories() {
		return array(
			1 => 'Accessories',
			2 => 'Art/Photo/Music',
			3 => 'Automotive',
			4 => 'Beauty',
			5 => 'Books/Media',
			6 => 'Business',
			7 => 'Buying and Selling',
			8 => 'Careers',
			9 => 'Clothing/Apparel',
			10 => 'Computer &amp; Electronics',
			11 => 'Department Stores/Malls',
			12 => 'Education',
			13 => 'Entertainment',
			14 => 'Family',
			15 => 'Financial Services',
			16 => 'Food &amp; Drinks',
			17 => 'Games &amp; Toys',
			18 => 'Gifts &amp; Flowers',
			19 => 'Health and Wellness',
			20 => 'Home &amp; Garden',
			21 => 'Legal',
			22 => 'Marketing',
			23 => 'Non-Profit',
			24 => 'Online Services',
			25 => 'Recreation &amp; Leisure',
			26 => 'Seasonal',
			27 => 'Sports &amp; Fitness',
			28 => 'Telecommunications',
			29 => 'Travel',
		);
	}

	/**
	 * Get the location of the plugin as a URL, for use with included CSS and images
	 * @return string The base URL of the plugin
	 */
	function baseurl() {
		static $url = null;
		if(!$url) {
			$url = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__));
			// This detects Owen's server, which uses a symlink to point to the git repo:
			if(strpos($url, 'linkit.lise')) {
				$url = WP_PLUGIN_URL . '/' . basename(dirname(plugin_basename(__FILE__)));
			}
		}
		return $url;
	}

	/**
	 * Returns 'on' if a value is set, or 0 if it's not.
	 * @param mixed $value Any value
	 * @return int|string Either 'on' or 0.
	 */
	function one_if_set($value) {
		if(isset($value)) {
			return 'on';
		}
		return 0;
	}

	/**
	 * Given an index of the $_SESSION array and a CSS class, produces a UL of messages
	 */
	function collect_messages($index, $class) {
		$msgout = '';
		if(isset($_SESSION[$index])) {
			$messages = $_SESSION[$index];
			if(count($messages) > 0) {
				foreach($messages as $message) {
					$msgout .= "<li>{$message}</li>\n";
				}
				$msgout = "<ul class=\"{$class}\">{$msgout}</ul><div id=\"clear\"></div>";
				unset($_SESSION[$index]);
			}
		}
		return $msgout;
	}

	/**
	 * Determines if the 123LintIt account has been connected to the sever
	 * @return bool True if the account is connected
	 */
	function is_authenticated() {
		return get_option( 'LinkITPublicKey' ) != '';
	}

	/**
	 * Create or refresh the cron for syncing posts.
	 * If there are posts that need synced, do it right away.
	 * If not, check again in an hour.
	 */
	function queue_cron() {
		global $wpdb;
		
		$last_sync = get_option( 'LinkItLastSync', 0 );
		$datetime = date( 'Y-m-d H:i:s', $last_sync );
		$posts_to_go = $wpdb->get_var("select count(id) from {$wpdb->prefix}posts p left join {$wpdb->prefix}linkit_cached_posts c on p.guid = c.guid where post_status = 'publish' and post_type = 'post' and (post_modified > '{$datetime}' or isnull(c.guid));");

		// Make sure our hourly event is still properly scheduled
		$timestamp = wp_next_scheduled( 'linkit_cron' );
		if($timestamp) {
			wp_unschedule_event($timestamp, 'linkit_cron');
		}
		// If there are more posts to sync than we just completed...
		if($posts_to_go > 10) {
			// ...then run another cron batch right away
			wp_schedule_event(time(), 'hourly', 'linkit_cron');
		}
		else {
			// ... otherwise, queue the batch for an hour from now
			wp_schedule_event(time() + 3600, 'hourly', 'linkit_cron');
		}
	}

	/**
	 * Obtain the blog category stored on the server and store it locally
	 */
	function refresh_blog_category() {
		$result = $this->api->get_category();
		if ( $result->error == '0' ) {
			$this->blogcategory = $result->blogcategory;
			update_option( "LinkITBlogCategory", $this->blogcategory );
		}
	}

	/**
	 * Get the profile stats from the server and store them in options
	 */
	function sync_profile() {
		$result = $this->api->get_stats();

		foreach($this->options() as $option => $default) {
			$obj_option = strtolower($option);
			if(isset($result->$obj_option)) {
				update_option( "LinkIT" . $option, $result->$obj_option );
			}
		}
	}

	/**
	 * Get the post content that contains affiliate links.
	 * @param stdClass $guid The post object
	 * @return string The cached or fresh post content with affiliate links in it
	 */
	function cache_post($post) {
		$content = $this->db_get_cached_post($post->guid);

		if(!$content) {
			$result = $this->api->download($post->guid);

			if($result->_status == 200) {
				$content = $result->content;
				// Sometimes the API returns nothing.  Not sure why, but don't display nothing.
				// Todo: Fix the API to return better info about why this happens
				if(trim($content) == '' && trim($post->post_content) != '') {
					$content = $post->post_content;
				}
				$hash = md5($content);
				$this->db_add_cached_post($post->guid, $content, $hash);
			}
			else {
				$content = $post->post_content;
			}
		}
		return $content;
	}

	/**
	 * Add a non-error notice to the output
	 * @param string $notice The notice to display
	 */
	function notice($notice) {
		$_SESSION['linkit_messages'][] = $notice;
	}

	/**
	 * Add an error to display on the next plugin page that appears
	 * @param string $error The error to display
	 */
	function error($error) {
		$_SESSION['linkit_errors'][] = $error;
	}
	/**
	 * Display the main plugin admin page and subordinate pages.
	 */
	function admin() {
		$this->dispatch_admin_step(array('dashboard', 'force_sync', 'reset_all', 'sync_all', 'sync_profile', 'restore_defaults'), 'dashboard');
	}

	/**
	 * Display the login, signup, and create user pages
	 * (Pages that can appear before logging in.)
	 */
	function login() {
		$this->dispatch_admin_step(array('login', 'signup', 'createuser'), 'login', true);
	}

	/**
	 * Display the 123LinkIt Challenge page
	 */
	function admin_challenge() {
		$this->admin_header();

		$challenge = new LinkitView('challenge.php');
		$challenge->keywords = $this->api->get_random_keywords();
		$challenge->render();
	}

	/**
	 * Display the bug reporting page
	 */
	function admin_bug() {
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$msg = $_POST['LinkITMsg'];
			global $wpdb;

			$table = $wpdb->prefix . "linkit_requests";
			$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY time DESC");
			$data = "request | sent | recived | time <br/>";

			foreach ( $rows as $row ) {
				$request = $row->{'request'};
				$sent = $row->{'data_sent'};
				$recived = $row->{'data_recived'};
				$time = $row->{'time'};
				$data = $data . "$request | $sent | $recived | $time <br/>";
			}
			$this->api->bug_report($msg, $data);

			$this->notice('Bug report sent.  Thanks!');
			wp_redirect('admin.php?page=linkit_reportbug');
			exit;
		}

		$this->admin_header();

		$challenge = new LinkitView('bug_report.php');
		$challenge->render();
	}

	/**
	 * Logs the user out of their 123LinkIt account locally and redirects to the login page.
	 * This is the dispatch for the logout menu option.
	 */
	function logout() {
		delete_option( 'LinkITEmail' );
		delete_option( 'LinkITPrivateKey' );
		delete_option( 'LinkITPublicKey' );
		wp_redirect('?page=linkit_plugin');
	}


	/**
	 * Displays the logo and error/notice information
	 * @param bool $suppress_header If false, do not diplay errors
	 */
	function admin_header($suppress_header = false){

		echo <<< LINKIT_HEADER
<div id="header">
	<img src="http://www.123linkit.com/images/plugin/logo.png" />
</div>
LINKIT_HEADER;

		if($suppress_header) {
			return;
		}

		$msgout = $this->collect_messages('linkit_errors', 'errors');
		$msgout .= $this->collect_messages('linkit_messages', 'notices');
		if($msgout != '') {
			$msgout = "<div class=\"inner\">{$msgout}</div>";
		}

		if($this->is_authenticated()) {
			$email = get_option( "LinkITEmail" );
			$greeting = "Welcome, {$email}";
		}
		else {
			$greeting = "Welcome to 123LinkIt";
		}

			echo <<< LINKIT_HEADER2
<div id="menu" class="box">
	<span class="caption">{$greeting}</span>
	{$msgout}
</div>
LINKIT_HEADER2;

	}

	/**
	 * An easier way to dispatch requests for specific steps inside the 123LinkIt admin to the correct function for display.
	 * @param array $valid_steps An array of valid steps that are allowed to be displayed
	 * @param string $default The default page to display if none is specified
	 * @param bool $suppress_header If true, don't display errors/notices
	 */
	function dispatch_admin_step($valid_steps = array(), $default = 'login', $suppress_header = false) {
		$this->admin_header($suppress_header);

		if(isset($_GET['step'])) {
			$step = $_GET['step'];
		}
		else {
			$step = $default;
		}
		if(count($valid_steps) > 0 && !in_array($step, $valid_steps)) {
			echo 'Sorry, the page you requested does not exist.  Please return to the <a href="?page=linkit_plugin">main configuration page</a>.';
			return;
		}
		$method = 'do_admin_' . $step;
		if(method_exists($this, $method)) {
			$this->$method();
			return;
		}
		echo 'Sorry, the page you requested does not exist.  Please return to the <a href="?page=linkit_plugin">main configuration page</a>.';
	}

	/**
	 * do_admin_* functions are all dispatched from the dispatch_admin_step() method to implement different plugin admin pages
	 */

	function do_admin_dashboard() {
		$dashboard = new LinkitView('dashboard.php');
		$dashboard->add_object($this);
		$dashboard->render();
	}

	function do_admin_login() {
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$result = $this->api->login($_POST['email'], $_POST['pass']);
			if ( $result->error != "0" ) {
				$this->error($result->error);
			}
			else {
				update_option( "LinkITEmail", $_POST['email'] );
				update_option( "LinkITPrivateKey", $result->private_key );
				update_option( "LinkITPublicKey", $result->public_key );
				$this->refresh_blog_category();

			}
			wp_redirect('admin.php?page=linkit_plugin');
			exit;
		}

		$login = new LinkItView('login.php');

		$msgout = $this->collect_messages('linkit_errors', 'errors');
		$msgout .= $this->collect_messages('linkit_messages', 'notices');
		$login->error_message = '';
		if($msgout != '') {
			$login->error_message = "<tr><td colspan=\"2\"><div class=\"error\">{$msgout}</div></td></tr>";
		}
		$login->render();
	}

	/**
	 * Bring the profile data down from the server, and redirect to the settings page
	 */
	function do_admin_sync_profile( ) {
		$this->sync_profile();
		$this->notice("Sync'ed profile data.");
		wp_redirect('?page=linkit_plugin');
	}

	/**
	 * Display the signup page
	 */
	function do_admin_signup() {
		$signup = new LinkItView('signup.php');

		$msgout = $this->collect_messages('linkit_errors', 'errors');
		$msgout .= $this->collect_messages('linkit_messages', 'notices');
		$signup->error_message = '';
		if($msgout != '') {
			$signup->error_message = "<tr><td colspan=\"2\"><div class=\"error\">{$msgout}</div></td></tr>";
		}
		$signup->categories = $this->get_categories();
		$signup->render();
	}

	/**
	 * Create a new user from a submitted signup page after validation
	 */
	function do_admin_createuser() {
		$email = $_POST['LinkITEmail'];
		$password = $_POST['LinkITPassword'];
		$passwordc = $_POST['LinkITPasswordc'];
		$category = $_POST['blogcategory'];
		$agree = $_POST['agree'];

		if($password != $passwordc) {
			$this->error('The passwords you entered must match.');
			wp_redirect('admin.php?page=linkit_plugin&step=signup');
		}
		elseif($agree != 'agreed') {
			$this->error('You must agree to the terms of service and privacy policy to continue.');
			wp_redirect('admin.php?page=linkit_plugin&step=signup');
		}
		else {
			$result = $this->api->create_user( $email, $password, $passwordc, $category );

			if ( $result->error != '0') {
				$this->error($result->error);
				wp_redirect('admin.php?page=linkit_plugin&step=signup');
			} else {
				$result = $this->api->login($email, $password);
				if ( $result->error != "0" ) {
					$this->error($result->error);
					wp_redirect('admin.php?page=linkit_plugin');
				}
				else {
					update_option( "LinkITEmail", $email );
					update_option( "LinkITPrivateKey", $result->private_key );
					update_option( "LinkITPublicKey", $result->public_key );
					$this->refresh_blog_category();
					$this->do_admin_sync_profile();

					wp_redirect('admin.php?page=linkit_plugin');
				}
			}
		}
	}

	/**
	 * Pretend the last sync time was never, then kick off the sync again
	 */
	function do_admin_force_sync() {
		update_option( "LinkItLastSync", 0 );
		$this->do_admin_sync_all();
	}

	/**
	 * Process all of the posts for sync that were created since the last sync completion
	 */
	function do_admin_sync_all() {
		global $wpdb;
		$poststable = $wpdb->prefix . "posts";
		$last_sync = get_option( 'LinkItLastSync', 0 );
		$datetime = date( 'Y-m-d H:i:s', $last_sync );

		$posts_to_start = $wpdb->get_var("select count(id) from {$wpdb->prefix}posts p left join {$wpdb->prefix}linkit_cached_posts c on p.guid = c.guid where post_status = 'publish' and post_type = 'post' and (post_modified > '{$datetime}' or isnull(c.guid));");
		$this->linkit_cron();
		$posts_to_go = $wpdb->get_var("select count(id) from {$wpdb->prefix}posts p left join {$wpdb->prefix}linkit_cached_posts c on p.guid = c.guid where post_status = 'publish' and post_type = 'post' and (post_modified > '{$datetime}' or isnull(c.guid));");
		$posts_in_cache = $wpdb->get_var("select count(id) from {$wpdb->prefix}posts p inner join {$wpdb->prefix}linkit_cached_posts c on p.guid = c.guid where post_status = 'publish' and post_type = 'post';");

		$posts_done = $posts_to_start - $posts_to_go;

		$this->notice("{$posts_done} posts have been processed, {$posts_to_go} are queued for synchronization, {$posts_in_cache} have been synced in total.");
		$this->notice("<a href=\"?page=linkit_plugin&step=force_sync\">Clear cache and force re-synchronization of all posts?</a>");

		$this->do_admin_sync_profile( );

		/*
		// make sure the option LinkItLastSync exists
		$last_sync = get_option( 'LinkItLastSync', 0 );
		$datetime = date( 'Y-m-d H:i:s', $last_sync );
		$myrows = $wpdb->get_results("SELECT post_modified, id, post_title FROM {$poststable} WHERE post_status = 'publish' AND post_type = 'post' AND post_modified > '{$datetime}'");

		if ( !$myrows ) {
			$this->notice("All posts are synchronized.  <a href=\"?page=linkit_plugin&step=force_sync\">Force re-synchronization?</a>");
		} 
		else {
			foreach ( $myrows as $row ) {
				$title = $row->post_title;
				$modified = strtotime( $row->post_modified );
				if ( $modified >= $last_sync ) {
					$this->publish($row->id);
					$this->notice("<b>" . $title . "</b> - synchronizing");
				} else {
					$this->notice($title . " - already synchronized");
				}
			}
		}
		update_option( "LinkItLastSync", time( ) );
		$this->do_admin_sync_profile( );
		*/
	}

	/**
	 * Reset all of the LinkIt options within this database
	 * Does not reset server data
	 */
	function do_admin_reset_all() {
		// Delete all LinkIt options
		
		foreach($this->options() as $key => $default) {
			$key = preg_replace('#^LinkIt#i', '', $key);
			delete_option($key);
		}

		global $wpdb;
		$table_name = $wpdb->prefix . "linkit_cached_posts";
		$wpdb->query("delete all from $table_name");
	}

	/**
	 * Restore settings data from the settins stored on the server
	 */
	function do_admin_restore_defaults() {
		$this->api->restore_default_settings();
		$this->notice('Restored default settings on the 123LinkIt server.');
		wp_redirect('admin.php?page=linkit_options');
	}

	/**
	 * Display the options page
	 */
	function admin_options() {
		$this->admin_header();
		$options = new LinkitView('options.php');

		// This updates the options if they were submitted
		if ( !empty($_POST) ) {
			update_option( "LinkITConfig", "done" );
			update_option( "LinkITBlogCategory", $_POST['blogcategory'] );

			$this->api->update_options(
				$this->one_if_set($_POST['options_cloaked']),
				$this->one_if_set($_POST['options_nofollow']),
				$this->one_if_set($_POST['options_new_window']),
				$this->one_if_set($_POST['options_num_links'])
			);
			$this->api->set_category($_POST['blogcategory']);
			$this->notice('Updated blog options.');
			$this->do_admin_force_sync();
			wp_redirect('?page=linkit_options');
			return;
		}

		$results = $this->api->get_options();

		$options->cloaked = $results->cloak_links == '1' ? 'checked' : '';
		$options->nofollow = $results->nofollow_tags == '1' ? 'checked' : '';
		$options->newwindow = $results->links_new_window == '1' ? 'checked' : '';

		$options->maximumlinks = $results->maximum_links == '1' ? 'checked' : '';
		$options->onelink = $results->maximum_links != '1' ? 'checked' : '';

		$options->categories = $this->get_categories();
		$options->category = $this->blogcategory;

		$options->render();
	}

	/**
	 * WordPress allows for many hooks.  Here are some implementations for a handful of them.
	 */

	/**
	 * Implementation of the action init hook
	 */
	function init() {
		// Make sure we're loading jQuery (For ajax on status page)
		wp_enqueue_script( "jquery" );
		// Put options into object scope as properties
		foreach($this->options() as $option => $default) {
			$obj_option = strtolower($option);
			$this->$obj_option = get_option('LinkIt' . $option);
		}
	}

	/**
	 * Implementation of the action publish_post hook
	 * Sends the post content to the server, and removes any locally cached data.
	 */
	function publish($id) {
		// If you can't access the API, don't try to upload anything
		if ( $this->privatekey == '' ) {
			return;
		}

		global $wpdb;
		$poststable = $wpdb->prefix . "posts";

		$post = $wpdb->get_row("SELECT guid, post_title, post_content, post_type FROM {$poststable} WHERE id = {$id}");
		if ( $post->post_type == 'post' ) {
			$result = $this->api->upload($post->guid, $post->post_title, $post->post_content);
			$this->db_delete_cached_post($post->guid);
			$this->cache_post($post);
		}
	}

	/**
	 * Implementation of wp_trim_excerpt hook
	 * Alters the post content on feed output to use the raw data, not the cached data
	 * @param string $text The trimmed content
	 * @return string Updated content that does not include affiliate links
	 */

	function wp_trim_excerpt( $text ) {
		global $post;

		$text = $post->linkit_raw_content;
		$text = strip_shortcodes( $text );


		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = preg_replace('#<script.*?</script>#i', '', $text);
		$text = wp_strip_all_tags($text);

		$excerpt_length = apply_filters('excerpt_length', 55);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
		$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
		if ( count($words) > $excerpt_length ) {
			array_pop($words);
			$text = implode(' ', $words);
			$text = $text . $excerpt_more;
		} else {
			$text = implode(' ', $words);
		}

		return $text;
	}

	/**
	 * Implementation of found_posts hook/filter
	 * This is setup within the post_results function below to preserve the $wp_query->found_posts value while accessing caches
	*/
	function found_posts_fix($n)
	{
		return $this->found_posts;
	}
	
	/**
	 * Implementation of post_results hook
	 * Alters the post content right after the post is fetched from the database to use the cached data
	 * Makes use of the custom cache tables
	 * @param array $posts The array of original post content results in the query
	 * @return array Updated posts that includes affiliate links
	 */
	function post_results( $posts ) {
		// If we have no private key, we shouldn't do this anyway
		if ( $this->privatekey == '' ) {
			return $posts;
		}

		global $wpdb;
		$poststable = $wpdb->prefix . "posts";

		//make sure to correctly set found_posts later
		$this->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
		add_filter("found_posts", array($this, "found_posts_fix"));
		
		foreach($posts as $index => $post) {
			$guid = $post->guid;

			$content = $this->cache_post($post);

			$posts[$index]->linkit_raw_content = $posts[$index]->post_content;
			$posts[$index]->post_content = $content;
		}

		return $posts;
	}

	/**
	 * Used in the_content to replace entity ampersands with actual ampersands.
	 */
	function de_entify_links($matches) {
		$result = $matches[0];
		$result = str_replace('&#038;', '&', $result);
		return $result;
	}

	/**
	 * Replaces the ampersand HTML entity within any LinkIt comment tags with a regular ampersand.
	 * This is required because, when injecting the linked-up content prior to output, WordPress has the opportunity to
	 * convert symbols to HTML entities, which it does, and we don't want these converted.
	 * The priority this is registered at is set high sot hat it happens after WordPress' internal code mangles ampersands.
	 * @param string $content The content of the post that should be displayed
	 * @return string The content with the LinkIt ampersands restored.
	 */
	function the_content($content) {
		$content = preg_replace_callback('#<!--B:123LinkIt-->.+?<!--E:123LinkIt-->#i', array($this, 'de_entify_links'), $content);
		return $content;
	}

	/**
	 * Implementation of the register_activation_hook function
	 */
	function activation() {
		// Store all of the default options into the options table
		foreach($this->options() as $option => $default) {
			add_option('LinkIt' . $option, $default);
		}
		// Schedule a cron event that periodically syncs new posts
		$this->queue_cron();

		// Make sure the needed tables are created
		$this->db_create_tables();
	}

	/**
	 * Implementation of the linkit_cron hook that executes periodically
	 */
	function linkit_cron() {
		global $wpdb;
		
		$last_sync = get_option( 'LinkItLastSync', 0 );
		$datetime = date( 'Y-m-d H:i:s', $last_sync );
		$posts = $wpdb->get_results("select post_modified, post_title, id, post_type, p.guid, c.guid from {$wpdb->prefix}posts p left join {$wpdb->prefix}linkit_cached_posts c on p.guid = c.guid where post_status = 'publish' and post_type = 'post' and (post_modified > '{$datetime}' or isnull(c.guid)) order by isnull(c.guid) desc, post_modified asc limit 10;");

		foreach ( $posts as $post ) {
			$this->publish($post->id);
		}

		update_option( "LinkItLastSync", time( ) );
		$this->sync_profile( );

		// Make sure the cron fires again, as needed
		$this->queue_cron();
	}

	/**
	 * Implementation of admin_notices hook
	 * Displays the signup alert if the plugin hasn't been configured yet.
	 */
	function admin_notices( ) {
		if ( get_option( 'LinkITConfig' ) != 'done' && $_REQUEST['step'] != "signup") {
			$alert = new LinkitView('signup_alert.php');
			$alert->render();
		}
	}

	/**
	 * Implementation of the admin_init hook
	 * Registers the plugin stylesheet and starts an output buffer
	 */
	function admin_init() {
		wp_register_style('123linkit', $this->baseurl() . '/123linkit.css');
		wp_admin_css('123linkit');
		ob_start();
	}

	/**
	 * Implmentation of the admin_menu hook
	 * Adds various menu options to the main WP admin menu
	 */
	function admin_menu() {	
		if($this->publickey == '') {
			add_menu_page( '123LinkIt', '123LinkIt', 'manage_options', 'linkit_plugin', array($this, 'login'), 'http://www.123linkit.com/images/123linkit.favicon.gif', 3 );						
			
			//catch users trying to get to the other pages			
			if(in_array($_REQUEST['page'], array("linkit_options", "linkit_challenge", "linkit_logout", "linkit_reportbug")))
			{
				wp_redirect("?page=linkit_plugin");
				exit;
			}
		}
		else {
			add_menu_page( '123LinkIt', '123LinkIt', 'manage_options', 'linkit_plugin', array($this, 'admin'), 'http://www.123linkit.com/images/123linkit.favicon.gif', 3 );
			add_submenu_page('linkit_plugin', 'Settings', 'Settings', 'manage_options', 'linkit_options', array($this, 'admin_options'));
			add_submenu_page('linkit_plugin', '123LinkIt Challenge', '123LinkIt Challenge', 'manage_options', 'linkit_challenge', array($this, 'admin_challenge'));
			add_submenu_page('linkit_plugin', 'Logout', 'Logout', 'manage_options', 'linkit_logout', array($this, 'logout'));
			add_submenu_page('linkit_plugin', 'Report Bug', 'Report Bug', 'manage_options', 'linkit_reportbug', array($this, 'admin_bug'));
		}
	}

	/**
	 * Return the name of the cache table
	 * @return string The name of the cache table
	 */
	function db_get_cache_table() {
		global $wpdb;
		return $wpdb->prefix . "linkit_cached_posts";
	}

	/**
	 * Delete the specified post from the cache
	 * @param string $guid The guid of a post
	 */
	function db_delete_cached_post($guid) {
		global $wpdb;
		$table_name = $this->db_get_cache_table();
		$wpdb->query("DELETE FROM {$table_name} WHERE guid = '$guid'");
	}

	/**
	 * Add a post content to the cache
	 * @param string $guid The guid of the post
	 * @param string $contents The post contents
	 * @param string $hash An MD5 hash of the post content
	 */
	function db_add_cached_post($guid, $contents, $hash) {
		global $wpdb;
		$this->db_delete_cached_post($guid);
		$table_name = $this->db_get_cache_table();
		$wpdb->insert(
			$table_name,
			array(
				'updated' => current_time('mysql'),
				'guid' => $guid,
				'contents' => $contents,
				'hash' => $hash
			)
		);
	}

	/**
	 * Return a post from cache
	 * @param string $guid The guid of a post
	 * @return mixed The string content of the post or false if the post content is missing or stale
	 */
	function db_get_cached_post($guid) {
		global $wpdb;
		$table_name = $this->db_get_cache_table();
		$cached_post = $wpdb->get_row("SELECT * FROM {$table_name} WHERE guid = '{$guid}'");

		if ( time() - strtotime($cached_post->updated) > self::LINKIT_CACHE_EXPIRY_MINUTES * 60 ) {
			// Our time has expired, check for a new version
			$server_result = $this->api->get_post_hash($guid);

			// If the post hasn't changed...
			if ($server_result->hash == $cached_post->hash) {
				// Update the time in the cache and return the cached value
				$this->db_add_cached_post($guid, $cached_post->contents, $cached_post->hash);
				return $cached_post->contents;
			}
		}
		else {
			// The cache value is valid, return it
			return $cached_post->contents;
		}
		// The cache is out of date and the post has been updated
		return false;
	}

	/**
	 * Create the cache table
	 */
	function db_create_tables() {
		global $wpdb;

		$sql = "
			CREATE TABLE {$wpdb->prefix}linkit_cached_posts (
				guid varchar(255) NOT NULL,
				contents text NOT NULL,
				hash varchar(255) NOT NULL,
				updated datetime NOT NULL,
				PRIMARY KEY  (guid)
			);
			CREATE TABLE {$wpdb->prefix}linkit_requests (
				request varchar(255) NOT NULL,
				data_sent text NOT NULL,
				data_recived text NOT NULL,
				time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				id int NOT NULL auto_increment,
				PRIMARY KEY  (id)
			);
		";

//		$wbdb->query("DROP TABLE {$wpdb->prefix}linkit_cached_posts;");
//		$wbdb->query("DROP TABLE {$wpdb->prefix}linkit_requests;");

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

/**
 * Basic utility templating class
 */
class LinkitView
{
	protected $template = '';
	protected $values = array();

	/**
	 * @param string $template The name of the template file to render
	 */
	function __construct($template)
	{
		$this->template = $template;
	}

	/**
	 * Magic __set method is used to set properties into the template
	 * @param string $key a variable name
	 * @param string $value a variable value
	 */
	function __set($key, $value)
	{
		$this->values[$key] = $value;
	}

	/**
	 * Produce the template output with included variables
	 */
	function render()
	{
		$template = $this->template;
		if(strpos($template, '/') === false) {
			$template = dirname(__FILE__) . '/templates/' . $template;
		}
		if(file_exists($template)) {
			foreach($this->values as $key => $value) {
				$$key = $value;
			}
			include($template);
		}
		else {
			echo "<h3>" . htmlspecialchars($template) . " <small>doesn't exist</small></h3>";
			echo "<table>";
			foreach($this->values as $key => $value) {
				echo '<tr><th valign="top">' . htmlspecialchars($key) . '</th><td><pre>' . htmlspecialshars(print_r($value,1)) . '</pre></td></tr>';
			}
			echo "</table>";
		}
	}

	/**
	 * Place all of the properties of the object into the template as variables
	 * @param StdClass $obj The object to add to the template
	 */
	function add_object($obj)
	{
		$vars = get_object_vars($obj);
		foreach($vars as $key => $value) {
			$this->values[$key] = $value;
		}
	}

	/**
	 * Output a select input with specified criteria
	 * @param string $name The name/id of the control
	 * @param array $options A key/value array of options
	 * @param mixed $default An optional key for the default selection in the select
	 * @return string The HTML output of the select box
	 */
	function select($name, $options, $default = null)
	{
		$select = '<select name="' . $name . '" id="' . $id . '">';
		foreach($options as $value => $display) {
			$select .= '<option value="' . $value . '"';
			if($value == $default) {
				$select .= ' selected="selected"';
			}
			$select .= '>' . $display . '</option>';
		}
		$select .= '</select>';
		return $select;
	}
}

/**
 * A class for connecting to the 123LinkIt API
 */
class LinkItAPI
{
	const BASE_URL = 'http://www.123linkit.com/';

	// A list of the calls, endpoints, and parameters
	private $api_calls = array(
		'login' => array('api/login', array('email', 'password')),
		'get_stats' => array('api/getStats', array('#private_key')),
		'download' => array('api/downloadPost', array('guid', '#private_key', '#blog_url')),
		'upload' => array('api/createPost', array('guid', 'title', 'content', '#private_key', '#blog_url')),
		'get_random_keywords' => array('api/getRandomKeywords', array('nothing')),
		'get_options' => array('api/getOptions', array('#private_key')),
		'get_category' => array('api/getBlogCategory', array('#blog_url', '#private_key')),
		'set_category' => array('api/setBlogCategory', array('blog_category', '#blog_url', '#private_key')),
		'update_options' => array('api/updateOptions', array('cloak_links', 'nofollow_tags', 'links_new_window', 'maximum_links', '#private_key')),
		'create_user' => array('api/createuser', array('email', 'password', 'passwordc', 'blogcategory', '#blogurl')),
		'upload' => array('api/createPost', array('guid', 'title', 'content', '#private_key', '#blog_url')),
		'get_post_hash' => array('api/getHashedPost', array('guid', '#private_key', '#blog_url')),
		'restore_default_settings' => array('api/restoreDefaultSettings', array('#private_key')),
		'bug_report' => array('api/reportBug', array('msg', 'data')),
	);

	/**
	 * The PHP magic __call method is used to dispatch calls to the methods listed in the $api_calls array
	 * @param string $name Method to dispatch
	 * @param array $inputs An array of inputs
	 * @return array The response from the API, decoded from JSON, with the HTTP response code added as the _status element
	 */
	function __call($name, $inputs)
	{
		global $wpdb;

		if(isset($this->api_calls[$name])) {
			list($fn, $params) = $this->api_calls[$name];
			foreach($params as $param) {
				switch($param) {
					case '#private_key':
						$outputs['private_key'] = get_option('LinkItPrivateKey');
						break;
					case '#blog_url':
						$outputs['blog_url'] = site_url();
						break;
					case '#blogurl':
						$outputs['blogurl'] = site_url();
						break;
					default:
						$outputs[$param] = array_shift($inputs);
						break;
				}
			}
			$httpresponse = wp_remote_post(self::BASE_URL . $fn, array('body' => $outputs));

			$variables_url = "";
			foreach($outputs as $key => $value) {
				$variables_url .= urlencode($key) . '=' . urlencode($value) . '&';
			}
			if(is_wp_error($httpresponse)) {
				$wpdb->insert("{$wpdb->prefix}linkit_requests", array('request' => self::BASE_URL . $fn, 'data_sent' => $variables_url, 'data_recived' => print_r($httpresponse,1)));
			}
			else {
				$wpdb->insert("{$wpdb->prefix}linkit_requests", array('request' => self::BASE_URL . $fn, 'data_sent' => $variables_url, 'data_recived' => $httpresponse['body']));
			}

			///* FOR DEBUGGING API: */echo '<pre>';var_dump(self::BASE_URL . $fn, array('body' => $outputs));echo htmlspecialchars(print_r($httpresponse,1));echo '</pre>';
			if(is_wp_error($httpresponse)) {
				$response = new StdClass();
				$response->_status = '500';
			}
			else {
				$response = json_decode($httpresponse['body']);
				if(is_object($response)) {
					$response->_status = $httpresponse['response']['code'];
				}
			}
			return $response;			
		}
	}
}

new LinkIt();
ob_start();

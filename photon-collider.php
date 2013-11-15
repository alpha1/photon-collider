<?php
/*
Plugin Name: Photon Collider 
Plugin URI: 
Description:
Version:0.2.0
Author: Michael Fitzpatrick-Ruth	
Author URI: http://alpha1beta.org
License: MIT License


TODO:
Minimize variables


DONE:
*/
include('photon-collider-base.php');
include_once('updater.php');

$folder_name = "photon-collider";
$github_base = "alpha1/alpha1/photon-collider";
$changelog = "changelog.txt"; //should be located in the master branch of the repo


if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
$config = array(
'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
'proper_folder_name' => 'enewsletter-generator', // this is the name of the folder your plugin lives in
'api_url' => 'https://api.github.com/repos/'. $github_base, // the github API url of your github repo
'raw_url' => 'https://raw.github.com/'. $github_base .'/master', // the github raw url of your github repo
'github_url' => 'https://github.com/'. $github_base, // the github url of your github repo
'zip_url' => 'https://github.com/'. $github_base .'/zipball/master', // the zip url of the github repo
'sslverify' => true, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
'requires' => '3.0', // which version of WordPress does your plugin require?
'tested' => '3.7.1', // which version of WordPress is your plugin tested up to?
'readme' => $changelog // which file to use as the readme for the version number
);
new WP_GitHub_Updater($config);
}

if(!function_exists('plugin_page_links')){
	function plugin_page_links($links, $file) {
		//might need to be global URL
		$path_name = pathinfo(__FILE__);
		if ($file == plugin_basename(dirname(__FILE__).'/'. $path_name['basename'])){
			$links[] = '<a href="http://alpha1beta.org/" target="_blank">Get Support</a>';
		}
		return $links;
	}
}
add_filter('plugin_action_links', 'plugin_page_links', -10, 2);
//************************************************************************************************************************************
//Pulse beacon: This lets the author know what versions of wordpress the plugin is being used on. This helps me be prepared for new versions, and keeping plugins working with old versions of wordpress.
register_activation_hook(__FILE__, 'pulse_beacon_activate');
register_deactivation_hook(__FILE__, 'pulse_beacon_deactivate');
if(!function_exists('pulse_beacon_deactivate')){
	function pulse_beacon_deactivate(){
		$plugin = get_plugin_data(__FILE__,false,false);
		pulse_beacon('deactivate',$plugin['Name'], $plugin['Version']);
	}
}
if(!function_exists('pulse_beacon_activate')){
	function pulse_beacon_activate(){
		$plugin = get_plugin_data(__FILE__,false,false);
		pulse_beacon('activate',$plugin['Name'], $plugin['Version']);
	}
}
if(!function_exists('pulse_beacon')){
	function pulse_beacon($action, $plugin, $plugin_version){
		global $wp_version;
		$pulse_domain = "pulse.alpha1beta.org";
		$pulse_url = 'http://'. $pulse_domain . str_replace(" ", "_",'/pulse-beacon/?action='. $action .'&plugin='. strtolower($plugin) .'&url='. site_url() .'&wp_version='. $wp_version .'&plugin_version='. $plugin_version);
		$response = wp_remote_get($pulse_url);
	}
}
//************************************************************************************************************************************
//Custom updating and info getting, I host my plugins on github so this allows me to update from there and get the latest changelogs from github.
add_filter( 'plugins_api', 'get_plugin_info_and_changelog_from_github', 20, 3 );

if(function_exists('get_plugin_info_and_changelog_from_github')){
	function get_plugin_info_and_changelog_from_github($res, $action, $args) {
		global $github_base;
		global $folder_name;
		//$plugin = get_plugin_data(__FILE__,false,false);

		if($args->slug == $folder_name){
			if($action == 'plugin_information' ){
				//if in details iframe on update core page short-curcuit it
				if(did_action( 'install_plugins_pre_plugin-information' )){
					$changelog = wp_remote_get('https://raw.github.com/'.$github_base.'/master/'. $changelog);
					if($changelog['response']['code'] == "200"){
						echo nl2br($changelog['body']);
					} else {
						echo $changelog['response']['code'];
					} 
					exit;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}



//add_action('in_plugin_update_message-PLUGINFOLDER/PLUGINBASE.php','plugin_update_message');

if(!function_exists('plugin_update_message')){
	function plugin_update_message() {
		global $github_base;
		$response = wp_remote_get('https://raw.github.com/'.$github_base.'/master/'. $changelog);
		if ( ! is_wp_error( $response ) || is_array( $response ) ) {
			//TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			$data = $response['body'];
			$bits=explode('== Upgrade Notice ==',$data);
			echo '<div id="mc-upgrade"><p><strong style="color:#c22;">Upgrade Notes:</strong> '.nl2br(trim($bits[1])).'</p></div>';
			//TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		} else {
			//TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			printf(__('<br /><strong>Note:</strong> Please review the <a class="thickbox" href="%1$s">changelog</a> before upgrading.','content-progress'),'plugin-install.php?tab=plugin-information&amp;plugin=content-progress&amp;TB_iframe=true&amp;width=640&amp;height=594');
			//TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		}
	}
}
//************************************************************************************************************************************
?>

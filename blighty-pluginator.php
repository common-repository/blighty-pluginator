<?php
/**
 * Plugin Name: Blighty Pluginator
 * Plugin URI: http://blighty.net/wordpress-blighty-pluginator-plugin/
 * Description: Checks a WordPress installation for out of date plugins and sends an email to the admin.
 * (C) 2016-2024 Chris Murfin (Blighty)
 * Version: 1.1.4
 * Author: Blighty
 * Author URI: http://blighty.net
 * License: GPLv3 or later
 **/

/**

Copyright (C) 2016-2017 Chris Murfin

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**/

defined('ABSPATH') or die('Plugin file cannot be accessed directly.');

define('BPL_PLUGIN_NAME', 'Blighty Pluginator');
define('BPL_PLUGIN_VERSION', '1.1.4');
define('BPL_PLUGIN_URL', 'http://blighty.net');

define('BPL_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'wp_mail' ) ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
}

if ( is_admin() ){ // admin actions
	require_once(BPL_PLUGIN_DIR .'/admin-settings.php');
	add_action( 'admin_menu', 'bpl_setup_menu' );
	add_action( 'admin_init', 'bpl_init' );
} 

register_deactivation_hook( __FILE__, 'bpl_deactivate' );

add_action( 'init', 'bpl_pluginator');


function bpl_deactivate() {

	if (get_option('bpl_snapshot', '0') == '1') {
        $emailAddress = get_option('bpl_email');

        if (empty($emailAddress)) {
            $emailAddress = get_bloginfo('admin_email');
        }

        $headers = 'From: ' .get_bloginfo('name') .' <' .get_bloginfo('admin_email') .'>' . "\r\n";
        $subj = '[' .get_bloginfo('name') .'] Pluginator Alert';
        $body = "WARNING! " .BPL_PLUGIN_NAME ." plugin was deactivated!\r\n\r\n";
        $body .= "Sent with " .BPL_PLUGIN_NAME ." v" .BPL_PLUGIN_VERSION ." by " .BPL_PLUGIN_URL ."\r\n";

        wp_mail( $emailAddress, $subj, $body, $headers );
    }

}

function bpl_pluginator() {

    $lastRun = get_option('bpl_last_run');
    if (!empty($lastRun)) {
        $diff = time() - $lastRun;
        // check every hour (3600 seconds)...
        if ( $diff < 3600 ) {
            return false;
        }
    }
    
    update_option('bpl_last_run', time());
    
    $allPlugins = get_plugins();
	$emailAddress = get_option('bpl_email');

    if (empty($emailAddress)) {
	    $emailAddress = get_bloginfo('admin_email');
	}
	
	$headers = 'From: ' .get_bloginfo('name') .' <' .get_bloginfo('admin_email') .'>' . "\r\n";
	$subj = '[' .get_bloginfo('name') .'] Pluginator Alert';
	
	//-- Activations/Deactivations Check (Snapshot)...

    $found = false;
    $sendEmail = false;
    $body = "";
    		
	if (get_option('bpl_snapshot', '0') == '1') {
	
		$activePluginsNow = get_option('active_plugins');
        $activePluginsLast = get_transient('bpl_active_plugins');
        $allPluginsLast = get_transient('bpl_all_plugins');
        if (empty($allPluginsLast)) {
            $allPluginsLast = $allPlugins;
        }

        if (!empty($activePluginsLast)) {

            // check for plugins that went inactive...
            $diff = array_diff($activePluginsLast, $activePluginsNow);
            if (count($diff) > 0) {
                $body .= "Deactivated Plugins:\r\n";
                foreach ($diff as $key => $value) {
                    $body .= "- " .$allPluginsLast[$value]["Name"] ." (Version: " .$allPlugins[$value]["Version"] .")\r\n";
                }
                $found = true;
            }

            // check for plugins that went active...
            $diff = array_diff($activePluginsNow, $activePluginsLast);
            if (count($diff) > 0) {
                $body .= "Activated Plugins:\r\n";
                foreach ($diff as $key => $value) {
                    $body .= "- " .$allPlugins[$value]["Name"] ." (Version: " .$allPlugins[$value]["Version"] .")\r\n";
                }
                $found = true;
            }

            if ($found) {
                $body = "The following WordPress plugins have changed status since the last snapshot:\r\n\r\n" .$body ."\r\n\r\n";
                $sendEmail = true;
            }
          
        }	

        set_transient('bpl_all_plugins', $allPlugins, 86400);
        set_transient('bpl_active_plugins', $activePluginsNow, 86400);
        
    }
    
	//-- Check for plugin updates...
		    
	$found = false;    
    $ignorePlugins = get_option('bpl_ignore');
    $notified = get_option("bpl_notified", array());
	
	$updatePlugins = get_site_transient('update_plugins');

	$body2 = "The following WordPress hosted plugins are reporting there are updates available:\r\n\r\n";

	if (!empty($updatePlugins)) {

    	$response = $updatePlugins->response;
    
        foreach ($response as $key => $value) {
            if (!empty($allPlugins[$key]["Name"])) {
                if ($ignorePlugins[$key] != 1) {
                    $keyVersion = $key .$response[$key]->new_version;
                    if (isset($notified[$keyVersion])) {                
                        $notifiedThis = true;
                    } else {
                        $notifiedThis = false;
                    }
                    if (!$notifiedThis) {
                        $notified[$keyVersion] = 1;
                        $found = true;
                        $out = "- " .$allPlugins[$key]["Name"] .' (Version: ';
                        $out .= $allPlugins[$key]["Version"] .') - Update available... Version: ';
                        $out .= $response[$key]->new_version ."\r\n";
                        $body2 .= $out;
                    }
                }
            }
        }
        
        if ($found) {	
            $sendEmail = true;
            $body .= $body2 ."\r\nVisit your admin plugins page to make the updates.\r\n\r\n";
        }

    }
    
    $body .= "Sent with " .BPL_PLUGIN_NAME ." v" .BPL_PLUGIN_VERSION ." by " .BPL_PLUGIN_URL ."\r\n";
    
    if ($sendEmail) {
    	wp_mail( $emailAddress, $subj, $body, $headers );
	}

    update_option('bpl_notified', $notified);
	
	return true;
}

?>

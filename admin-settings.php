<?php
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

v1.1.4

**/


function bpl_init() {
	register_setting( 'bpl_option-settings', 'bpl_email', 'bpl_email_validate' );
	register_setting( 'bpl_option-settings', 'bpl_ignore', 'bpl_ignore_validate' );
	register_setting( 'bpl_option-settings', 'bpl_last_run' );
	register_setting( 'bpl_option-settings', 'bpl_notified' );
	register_setting( 'bpl_option-settings', 'bpl_snapshot' );
}

function bpl_setup_menu(){
	add_options_page( 'Blighty Pluginator', 'Blighty Pluginator', 'manage_options', 'blighty-pluginator-plugin', 'bpl_admin_settings' );
}

add_filter( 'plugin_action_links_blighty-pluginator/blighty-pluginator.php', 'bpl_add_action_links' );

function bpl_add_action_links ( $links ) {
	$url = '<a href="' . admin_url( 'options-general.php?page=blighty-pluginator-plugin' ) . '">Settings</a>';
	$mylinks = array( $url );
	return array_merge( $mylinks, $links );
}

function bpl_admin_settings(){
?>
	<div class="wrap">
		<h2><?php echo BPL_PLUGIN_NAME; ?> version <?php echo BPL_PLUGIN_VERSION; ?></h2>
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div class="inner-sidebar">
					<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
						<div class="postbox">
							<h3>Help me help you!</h3>
							<div class="inside">
                                Hi, I'm Chris - the developer of Blighty Pluginator. Did this plugin help you fill a need? Did it save you some development time? Please consider making a donation today. Thank you.<br /><br />
								<div align="center">
									<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
									<input type="hidden" name="cmd" value="_donations">
									<input type="hidden" name="business" value="2D9PDAS9FDDCA">
									<input type="hidden" name="lc" value="US">
									<input type="hidden" name="item_name" value="Blighty Explorer Plugin">
									<input type="hidden" name="item_number" value="BPL001A">
									<input type="hidden" name="button_subtype" value="services">
									<input type="hidden" name="no_note" value="1">
									<input type="hidden" name="no_shipping" value="1">
									<input type="hidden" name="currency_code" value="USD">
									<input type="hidden" name="bn" value="PP-BuyNowBF:btn_donateCC_LG.gif:NonHosted">
									<input type="hidden" name="on0" value="website">
									<input type="hidden" name="os0" value="<?php echo $_SERVER['SERVER_NAME']; ?>">
									<input type="radio" name="amount" value="4">$4&nbsp;
									<input type="radio" name="amount" value="7">$7&nbsp;
									<input type="radio" name="amount" value="10">$10&nbsp;
									<input type="radio" name="amount" value="20">$20&nbsp;
									<input type="radio" name="amount" value="">Other<br /><br />
									<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
									<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
									</form>
								</div>
							</div>
						</div>
						<div class="postbox">
							<h3>Technical Support</h3>
							<div class="inside">
								If you need technical support or would like to see a new featured implemented, please provide your feedback via the <a href="https://wordpress.org/support/plugin/blighty-pluginator">WordPress Plugin Forums</a>.
							</div>
						</div>
					</div>
				</div>

				<div id="post-body-content" class="has-sidebar-content">
					<div class="meta-box-sortabless">
                        <div class="postbox">
                            <h3>Options</h3>
                            <div class="inside">
                                <form method="post" action="options.php">
                                <?php

                                settings_fields('bpl_option-settings');
                            
                                $ignore = get_option('bpl_ignore');							
                                $lastRun = get_option('bpl_last_run');
                                if (empty($lastRun)) {
                                    $runDate = 'Never';
                                } else {
                                    $runDate = date(get_option('date_format') .' '. get_option('time_format'), $lastRun );
                                }
                                
                                if (get_option('bpl_snapshot') == '1') {
                                    $snapshotChecked = ' checked';
                                } else {
                                    $snapshotChecked = '';
                                };
                        
                                echo 'By default, emails are sent to this blog\'s admin email address (' .get_bloginfo('admin_email') .'). You can use an alternate address here for these notifications if you wish. <br /><br />';
                                echo '<b>Alternate email address:</b>&nbsp;<input type="text" name="bpl_email" value="' .esc_attr( get_option('bpl_email') ) .'" /><br /><br />';
                                echo '<b>Plugins last checked:</b> ' .$runDate .'<br /><br />';
                                echo 'All plugins will be monitored <i>unless</i> you check them below. i.e. check the ones you\'d like to ignore.<br /><br />';

                                $allPlugins = get_plugins();
                                $i = 0;
                                foreach ($allPlugins as $key => $value) {
                                    if (isset($ignore[$key]) && $ignore[$key] == '1') {
                                        $checked = ' checked';
                                    } else {
                                        $checked = '';
                                    }
                                    echo '<input type="checkbox" name="bpl_ignore_' .$i .'" value="' .$key .'"' .$checked .' />&nbsp;';
                                    echo $allPlugins[$key]["Name"];
                                    echo "&nbsp;(v" .$allPlugins[$key]["Version"] .")<br />";
                                    $i++;
                                }
                                 
                                echo '<br />';
                                echo '<b>Send notification when plugin activations/deactivations are detected:</b>&nbsp;';
                                echo '<input type="checkbox" name="bpl_snapshot" value="1"' .$snapshotChecked .' />';

                                submit_button();

                                ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

				<?php echo BPL_PLUGIN_NAME; ?> version <?php echo BPL_PLUGIN_VERSION; ?> by <a href="http://blighty.net" target="_blank">Blighty</a>
			</div>

	</div>
<?php
}

function bpl_email_validate($email) {

	if ($email != '') {
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			add_settings_error( 'bpl_option-settings', 'invalid-email', 'You have entered an invalid email address.', "error" );
			return "";
		} else {
			return $email;
		}
	} else {
		return "";
	}
}

function bpl_ignore_validate($input) {
	$ignore = array();
	foreach ($_POST as $field => $value) {
		if (substr($field,0,11) == 'bpl_ignore_') {
				$ignore[$value] = '1';
		}
	}
	return $ignore;
}

?>

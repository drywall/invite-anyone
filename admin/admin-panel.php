<?php

function invite_anyone_admin_add() {

	$plugin_page = add_submenu_page( 'bp-general-settings', __( 'Invite Anyone', 'bp-invite-anyone' ), __( 'Invite Anyone', 'bp-invite-anyone' ), 'manage_options', __FILE__, 'invite_anyone_admin_panel' );
	
	add_action( "admin_print_scripts-$plugin_page", 'invite_anyone_admin_scripts' );
	add_action( "admin_print_styles-$plugin_page", 'invite_anyone_admin_styles' );
}
add_action( is_multisite() && function_exists( 'is_network_admin' ) ? 'network_admin_menu' : 'admin_menu', 'invite_anyone_admin_add', 80 );

/* Stolen from Welcome Pack - thanks, Paul! */
function invite_anyone_admin_add_action_link( $links, $file ) {
	if ( 'invite-anyone/invite-anyone.php' != $file )
		return $links;

	$settings_link = '<a href="' . admin_url( 'admin.php?page=invite-anyone/admin/admin-panel.php' ) . '">' . __( 'Settings', 'bp-invite-anyone' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links', 'invite_anyone_admin_add_action_link', 10, 2 );


function invite_anyone_admin_scripts() {
	wp_enqueue_script( 'invite-anyone-admin-js', WP_PLUGIN_URL . '/invite-anyone/admin/admin-js.js' );
}
 
function invite_anyone_admin_styles() {
	wp_enqueue_style( 'invite-anyone-admin-css', WP_PLUGIN_URL . '/invite-anyone/admin/admin-css.css' );
}

function invite_anyone_admin_panel() {
	
	if ( !empty( $_GET['migrate'] ) && $_GET['migrate'] == '1' ) {
		$iaoptions 	= get_option( 'invite_anyone' );
		$maybe_version	= !empty( $iaoptions['db_version'] ) ? $iaoptions['db_version'] : '0.7';
 
		// Don't run this migrator if coming from IA 0.8 or greater
		if ( !version_compare( $maybe_version, '0.8', '>=' ) ) {
			invite_anyone_migration_step();
			return;
		}
	}


	// Get the proper URL for submitting the settings form. (Settings API workaround)
	$url_base = function_exists( 'is_network_admin' ) && is_network_admin() ? network_admin_url( 'admin.php?page=invite-anyone/admin/admin-panel.php' ) : admin_url( 'admin.php?page=invite-anyone/admin/admin-panel.php' );
	
	// Catch and save settings being saved (Settings API workaround)
	if ( !empty( $_POST['invite-anyone-settings-submit'] ) ) {
		update_option( 'invite_anyone', $_POST['invite_anyone'] );
	}

?>
	<div class="wrap">
    	<h2><?php _e( 'Invite Anyone Settings', 'bp-invite-anyone' ) ?></h2>
    
    	<form action="<?php echo $url_base ?>" method="post">
	
	<?php /* The Settings API does not work with WP 3.1 Network Admin, but these functions still work to create the markup */ ?>
	<?php settings_fields( 'invite_anyone' ); ?>
	<?php do_settings_sections( 'invite_anyone' ); ?>
	
	<input id="invite-anyone-settings-submit" name="invite-anyone-settings-submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />           
	</form>

    
    </div>
<?php 
}



function invite_anyone_settings_setup() {
	register_setting( 'invite_anyone', 'invite_anyone', 'invite_anyone_settings_check' );
	
	/* General Settings */
	add_settings_section('invite_anyone_general_settings', __('General Settings', 'bp-invite-anyone'), 'invite_anyone_settings_main_content', 'invite_anyone');

	add_settings_field('invite_anyone_settings_replacement_patterns', __('Replacement patterns for email text fields', 'bp-invite-anyone'), 'invite_anyone_settings_replacement_patterns', 'invite_anyone', 'invite_anyone_general_settings');
		
	add_settings_field('invite_anyone_settings_default_invitation_subject', __('Text of email invitation subject line', 'bp-invite-anyone'), 'invite_anyone_settings_default_invitation_subject', 'invite_anyone', 'invite_anyone_general_settings');
	
	add_settings_field('invite_anyone_settings_default_invitation_message', __('Main text of email invitation message', 'bp-invite-anyone'), 'invite_anyone_settings_default_invitation_message', 'invite_anyone', 'invite_anyone_general_settings');
	
	add_settings_field('invite_anyone_settings_addl_invitation_message', __('Footer text of email invitation message (not editable by users)', 'bp-invite-anyone'), 'invite_anyone_settings_addl_invitation_message', 'invite_anyone', 'invite_anyone_general_settings');
		
	
	add_settings_field('invite_anyone_settings_is_customizable', __('Allow users to customize invitation', 'bp-invite-anyone'), 'invite_anyone_settings_is_customizable', 'invite_anyone', 'invite_anyone_general_settings');	

	
	add_settings_field('invite_anyone_settings_number_of_invitations', __('Number of email invitations users are permitted to send at a time', 'bp-invite-anyone'), 'invite_anyone_settings_number_of_invitations', 'invite_anyone', 'invite_anyone_general_settings');
	
	add_settings_field('invite_anyone_settings_can_send_group_invites_email', __('Allow users to send group invitations along with email invitations', 'bp-invite-anyone'), 'invite_anyone_settings_can_send_group_invites_email', 'invite_anyone', 'invite_anyone_general_settings');
	
	add_settings_field('invite_anyone_settings_bypass_registration_lock', __('Allow email invitations to be accepted even when site registration is disabled', 'bp-invite-anyone'), 'invite_anyone_settings_bypass_registration_lock', 'invite_anyone', 'invite_anyone_general_settings');	

	/* Cloudsponge Settings */
	add_settings_section( 'invite_anyone_cs', __( 'CloudSponge', 'bp-invite-anyone' ), 'invite_anyone_settings_cs_content', 'invite_anyone' );
	
	/* Access Settings */
	add_settings_section('invite_anyone_access_settings', __('Access Settings', 'bp-invite-anyone'), 'invite_anyone_settings_access_content', 'invite_anyone');
	
	add_settings_field('invite_anyone_settings_email_visibility', __('Allow email invitations to be sent by', 'bp-invite-anyone'), 'invite_anyone_settings_email_visibility', 'invite_anyone', 'invite_anyone_access_settings');
	
	add_settings_field( 'invite_anyone_settings_group_invite_visibility', __( 'Limit group invitations', 'bp-invite-anyone' ), 'invite_anyone_settings_group_invite_visibility', 'invite_anyone', 'invite_anyone_access_settings' );
}
add_action( 'admin_init', 'invite_anyone_settings_setup' );



function invite_anyone_settings_main_content() {

?>
	<p><?php _e( 'Control the default behavior of Invite Anyone.', 'bp-invite-anyone' ) ?></p>

<?php
}

function invite_anyone_settings_replacement_patterns() {
?>
	<ul>
		<li><strong>%%SITENAME%%</strong> - <?php _e( 'name of your website', 'bp-invite-anyone' ) ?></li>
		<li><strong>%%INVITERNAME%%</strong> - <?php _e( 'display name of the inviter', 'bp-invite-anyone' ) ?></li>
		<li><strong>%%INVITERURL%%</strong> - <?php _e( 'URL to the profile of the inviter', 'bp-invite-anyone' ) ?></li>
		<li><strong>%%ACCEPTURL%%</strong> - <?php _e( 'Link that invited users can click to accept the invitation', 'bp-invite-anyone' ) ?></li>
		<li><strong>%%OPTOUTURL%%</strong> - <?php _e( 'Link that invited users can click to opt out of future invitations', 'bp-invite-anyone' ) ?></li>
	</ul>
<?php
}


/* Max number of email invitations at a time */

function invite_anyone_settings_number_of_invitations() {
	$options = get_option( 'invite_anyone' );
	echo "<input id='invite_anyone_settings_number_of_invitations' name='invite_anyone[max_invites]' size='10' type='text' value='{$options['max_invites']}' />";
}

function invite_anyone_settings_can_send_group_invites_email() {
	$options = get_option( 'invite_anyone' );
?>
	<input type="checkbox" name="invite_anyone[can_send_group_invites_email]" value="yes" <?php if ( $options['can_send_group_invites_email'] == 'yes' ) : ?>checked="checked"<?php endif; ?> />
<?php
}

function invite_anyone_settings_bypass_registration_lock() {
	$options = get_option( 'invite_anyone' );
?>
	<input type="checkbox" name="invite_anyone[bypass_registration_lock]" value="yes" <?php if ( $options['bypass_registration_lock'] == 'yes' ) : ?>checked="checked"<?php endif; ?> />
<?php
}

function invite_anyone_settings_default_invitation_subject() {
	echo "<textarea name='invite_anyone[default_invitation_subject]' cols=60 rows=2 >" . esc_html( invite_anyone_invitation_subject() ) . "</textarea>";
}

function invite_anyone_settings_default_invitation_message() {
	echo "<textarea name='invite_anyone[default_invitation_message]' cols=60 rows=5 >" . esc_html( invite_anyone_invitation_message() ) . "</textarea>";
}

function invite_anyone_settings_addl_invitation_message() {
?>
	<textarea name='invite_anyone[addl_invitation_message]' cols=60 rows=5 ><?php echo esc_html( invite_anyone_process_footer( '[email]' ) ) ?></textarea>
<?php
}

function invite_anyone_settings_is_customizable() {
	$options = get_option( 'invite_anyone' );
?>
	<ul>
		<li>
			<input type="checkbox" name="invite_anyone[subject_is_customizable]" value="yes" <?php if( $options['subject_is_customizable'] == 'yes' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'Subject line', 'bp-invite-anyone' ) ?>
		</li>
		
		<li>
			<input type="checkbox" name="invite_anyone[message_is_customizable]" value="yes" <?php if( $options['message_is_customizable'] == 'yes' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'Message body', 'bp-invite-anyone' ) ?>
		</li>
	</ul>
<?php
}

function invite_anyone_settings_access_content() {
?>
	<p><?php _e( 'Control which members are able to send various kinds of invitations.', 'bp-invite-anyone' ) ?></p>
<?php
}

function invite_anyone_settings_email_visibility() {
	$options = get_option( 'invite_anyone' );
?>

	<ul>
		<li><input type='radio' name='invite_anyone[email_visibility_toggle]' id='invite_anyone_toggle_email_no_limit' value='no_limit' <?php if( $options['email_visibility_toggle'] != 'limit' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'All users', 'bp-invite-anyone' ) ?></li>
		
		<li><input type='radio' name='invite_anyone[email_visibility_toggle]' id='invite_anyone_toggle_email_limit' value='limit' <?php if( $options['email_visibility_toggle'] == 'limit' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'A limited set of users', 'bp-invite-anyone' ) ?>
			<div class="invite-anyone-admin-limited">
			<ul>
				<li>
					<input type="checkbox" name="invite_anyone[email_since_toggle]" value="yes" <?php if( $options['email_since_toggle'] == 'yes' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'Only users who have been members of the site for a minimum number of days:', 'bp-invite-anyone' ) ?> 
					<input name='invite_anyone[days_since]' size='10' type='text' value='<?php echo $options['days_since'] ?>' />			
				</li>
				
				<li>
					<input type="checkbox" name="invite_anyone[email_role_toggle]" value="yes"  <?php if( $options['email_role_toggle'] == 'yes' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'Only users who have at least the following role on this blog:', 'bp-invite-anyone' ) ?> 
					<select name="invite_anyone[minimum_role]">
						<option value="Subscriber" <?php if( $options['minimum_role'] == 'Subscriber' ) : ?>selected="selected"<?php endif; ?> ><?php _e( 'Subscriber' ) ?></option>
						<option value="Contributor" <?php if( $options['minimum_role'] == 'Contributor' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Contributor' ) ?></option>
						<option value="Author" <?php if( $options['minimum_role'] == 'Author' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Author' ) ?></option>
						<option value="Editor" <?php if( $options['minimum_role'] == 'Editor' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Editor' ) ?></option>
						<option value="Administrator" <?php if( $options['minimum_role'] == 'Administrator' ) : ?>selected="selected"<?php endif; ?>><?php _e( 'Administrator' ) ?></option>
					</select>					
				</li>		
				
				<li>
					<input type="checkbox" name="invite_anyone[email_blacklist_toggle]" value="yes"  <?php if( $options['email_blacklist_toggle'] == 'yes' ) : ?>checked="checked"<?php endif; ?> /> <?php _e( 'Provide a comma-separated list of users (identified by their numerical user ids) who <strong>cannot</strong> send invitations by email:', 'bp-invite-anyone' ) ?>
					<input name='invite_anyone[email_blacklist]' size='40' type='text' value='<?php echo $options['email_blacklist'] ?>' />
				</li>		
			</ul>
			</div>
		</li>
	</ul>

<?php
}

function invite_anyone_settings_group_invite_visibility() {
	$options = get_option( 'invite_anyone' );
?>
	<ul>	
	<?php _e( 'Invite Anyone extends BuddyPress\'s default group invitation settings. Instead of allowing you to invite only friends to a group, this plugin allows you to invite any member of the site. Use these settings to limit possible invitees for different group roles.', 'bp-invite-anyone' ) ?>
	<br />
		<li><?php _e( "<strong>Site admins</strong> can send group invitations to: ", 'bp-invite-anyone' ) ?> 
			<select name="invite_anyone[group_invites_can_admin]">
				<option value="anyone" <?php selected( $options['group_invites_can_admin'], 'anyone' ) ?>><?php _e( 'Anyone', 'bp-invite-anyone' ) ?></option>
				<option value="friends" <?php selected( $options['group_invites_can_admin'], 'friends' ) ?>><?php _e( 'Friends', 'bp-invite-anyone' ) ?></option>
				<option value="noone" <?php selected( $options['group_invites_can_admin'], 'noone' ) ?>><?php _e( 'No one', 'bp-invite-anyone' ) ?></option>
			</select>		
		</li>

		<li><?php _e( "<strong>Group admins</strong> can send group invitations to: ", 'bp-invite-anyone' ) ?> 
			<select name="invite_anyone[group_invites_can_group_admin]">
				<option value="anyone" <?php selected( $options['group_invites_can_group_admin'], 'anyone' ) ?>><?php _e( 'Anyone', 'bp-invite-anyone' ) ?></option>
				<option value="friends" <?php selected( $options['group_invites_can_group_admin'], 'friends' ) ?>><?php _e( 'Friends', 'bp-invite-anyone' ) ?></option>
				<option value="noone" <?php selected( $options['group_invites_can_group_admin'], 'noone' ) ?>><?php _e( 'No one', 'bp-invite-anyone' ) ?></option>
			</select>		
		</li>

		<li><?php _e( "<strong>Group mods</strong> can send group invitations to: ", 'bp-invite-anyone' ) ?> 
			<select name="invite_anyone[group_invites_can_group_mod]">
				<option value="anyone" <?php selected( $options['group_invites_can_group_mod'], 'anyone' ) ?>><?php _e( 'Anyone', 'bp-invite-anyone' ) ?></option>
				<option value="friends" <?php selected( $options['group_invites_can_group_mod'], 'friends' ) ?>><?php _e( 'Friends', 'bp-invite-anyone' ) ?></option>
				<option value="noone" <?php selected( $options['group_invites_can_group_mod'], 'noone' ) ?>><?php _e( 'No one', 'bp-invite-anyone' ) ?></option>
			</select>		
		</li>

		<li><?php _e( "<strong>Group members</strong> can send group invitations to: ", 'bp-invite-anyone' ) ?> 
			<select name="invite_anyone[group_invites_can_group_member]">
				<option value="anyone" <?php selected( $options['group_invites_can_group_member'], 'anyone' ) ?>><?php _e( 'Anyone', 'bp-invite-anyone' ) ?></option>
				<option value="friends" <?php selected( $options['group_invites_can_group_member'], 'friends' ) ?>><?php _e( 'Friends', 'bp-invite-anyone' ) ?></option>
				<option value="noone" <?php selected( $options['group_invites_can_group_member'], 'noone' ) ?>><?php _e( 'No one', 'bp-invite-anyone' ) ?></option>
			</select>		
		</li>	
	</ul>
<?php
}

function invite_anyone_settings_cs_content() {
	
	$options 	= get_option( 'invite_anyone' );
	$domain_key 	= !empty( $options['cloudsponge_key'] ) ? $options['cloudsponge_key'] : '';

?>
	<div class="cs">
		<a href="http://www.cloudsponge.com/?utm_source=invite-anyone&utm_medium=partner&utm_campaign=integrator"><img class="cs-logo" src="<?php echo plugins_url( 'invite-anyone/images/cloudsponge_logo.png' ) ?>" /></a>
		
		<div class="cs-explain">
			<p><?php _e( '<a href="http://www.cloudsponge.com/?utm_source=invite-anyone&utm_medium=partner&utm_campaign=integrator">CloudSponge</a> is a cool service that gives your users easy and secure access to their address books (Gmail, Yahoo, and a number of other online and desktop email clients), so that they can more easily invite friends to your site. In order to enable CloudSponge support in Invite Anyone and BuddyPress, you\'ll need to <a href="http://www.cloudsponge.com/signup?utm_source=invite-anyone&utm_medium=partner&utm_campaign=integrator">register for a CloudSponge account</a>.', 'bp-invite-anyone' ) ?></p>
			
			<label for="invite_anyone[cloudsponge_enabled]"><input type="checkbox" name="invite_anyone[cloudsponge_enabled]" id="cloudsponge-enabled" <?php checked( $options['cloudsponge_enabled'], 'on' ) ?>/> <strong><?php _e( 'Enable CloudSponge?', 'bp-invite-anyone' ) ?></strong></label>
		
			
		</div>
		
		<div class="cs-settings">
			<label for="invite_anyone[cloudsponge_key]"><?php _e( 'CloudSponge Domain Key', 'bp-invite-anyone' ) ?></label> <input type="text" id="cloudsponge-key" name="invite_anyone[cloudsponge_key]" value="<?php echo esc_html( $domain_key ) ?>" /> <span class="description"><?php _e( 'CloudSponge integration will not work without a valid domain key.', 'bp-invite-anyone' ) ?></span>
		
			<p class="description"><?php _e( 'When you use CloudSponge with Invite Anyone, part of your CloudSponge monthly payment goes to the author of Invite Anyone. This is a great way to support future development of the plugin. Thanks for your support!', 'bp-invite-anyone' ) ?></p>
		</div>
	</div>
	
	
<?php
}

function invite_anyone_settings_check($input) {
	return $input;
}

?>

<?php

class Cloudsponge_Integration {
	/**
	 * PHP 4 Constructor
	 *
	 * @package Invite Anyone
	 * @since 0.8
	 */
	function cloudsponge_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 Constructor
	 *
	 * @package Invite Anyone
	 * @since 0.8
	 */
	function __construct() {
		add_action( 'invite_anyone_after_addresses', array( $this, 'import_markup' ) );
	}

	function import_markup() {
		?>		
		
		
		<!-- Include these scripts to import address books with CloudSponge -->

<script type="text/javascript" src="<?php echo plugins_url( 'invite-anyone/by-email/address_books.js' ) ?>"></script>
<script type="text/javascript" charset="utf-8">csInit({domain_key:"D8UDAPCEKMULKJE83EPN", textarea_id:'invite-anyone-email-addresses'});</script>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#contact_list').change(function(){
		alert('htntnh');
	});
},(jQuery));
</script>

<!-- Any link with a class="cs_import" will start the import process -->
<?php _e( 'You can also add email addresses <a class="cs_import">from your Address Book</a>.', 'bp-invite-anyone' ) ?>
		
		
		<?php
	}


}
$cloudsponge_integration = new Cloudsponge_Integration;

?>
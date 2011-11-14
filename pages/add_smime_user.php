<?php
/**
 * This adds a public key to the specified user
 *
 *
 */
$t_plugin_path = config_get( 'plugin_path' );
require_once( $t_plugin_path . 'Smime' . DIRECTORY_SEPARATOR . 'Smime.php' );

form_security_validate( 'plugin_smime_action' );
auth_reauthenticate( );

$f_file = gpc_get_file( 'file', -1 );
$f_user = gpc_get_int_array('user_id');
$contents = file_get_contents($f_file['tmp_name']);

file_ensure_uploaded( $f_file );

form_security_purge( 'plugin_smime_action' );

html_page_top(lang_get( 'plugin_smime_title'  ) );

print_manage_menu( 'manage_smime_page.php' );

/*
 * Is the certificate valid?
 */
if(openssl_pkey_get_public($contents)==false){
	echo "<font color=\"#ff0000\">";
	echo plugin_lang_get("cert_import_error");
	echo "</font>";
	html_page_bottom();
	return;
}


echo "<pre>\n";
echo "Result: \n";
foreach($f_user as $user)
{

	$query = "SELECT * FROM " . plugin_table("certificates") . " WHERE user_id=" . $user . ";";
	$result = db_query_bound( $query);

	if($result->RecordCount() != 0)
	{
		/*
		 * If there already is a certificate for the user, update it.
		 */
		db_query_bound("UPDATE " . plugin_table("certificates") . " SET cert = " .
		db_param() . " WHERE user_id=".db_param().";",array($contents,$user));
		echo "Zertifikat fuer " . user_get_name($user) . " aktualisiert.\n";
	}
	else
	{
		/*
		 * Insert the new certificate
		 */
		db_query_bound("INSERT INTO " . plugin_table("certificates") . "(user_id,cert) VALUES (" .$user.", " .
		db_param() . ");",array($contents));
		echo "Certificate for " . user_get_name($user) . " added.\n";
	}
}
echo "</pre>\n";




html_page_bottom();
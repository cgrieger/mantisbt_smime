<?php
/**
 * 
 * Deletes a certificate
 * 
 */
$delete_id = gpc_get_int("delete",-1);
if($delete_id!=-1)
{
	$query = "DELETE FROM " . plugin_table("certificates") . " WHERE user_id=".$delete_id.";";
	$result = db_query_bound( $query);
	$t_redirect_url = 'plugin.php?page=Smime/manage_smime_page';
	html_meta_redirect( $t_redirect_url, 0, false );
}
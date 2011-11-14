<?php
/**
 * Displays the S/MIME Management page
 */
html_page_top1( lang_get( 'plugin_smime_title' ) );
html_page_top2();

$t_max_file_size = (int)min(
ini_get_number( 'upload_max_filesize' ),
ini_get_number( 'post_max_size' ),
config_get( 'max_file_size' )
);

$delete_id = gpc_get_int("delete",-1);

if($delete_id!=-1)
{
	$query = "DELETE FROM " . plugin_table("certificates") . " WHERE user_id=".$delete_id.";";
	$result = db_query_bound( $query);
}


?>

<div align="center">
		<table class="width75">
			<tr>
				<td class="form-title" colspan="5"><?php echo lang_get( 'plugin_smime_existing_user_title' ) ?>
				</td>
			</tr>
			<tr class="row-1">
				<td class="category">User</td>
				<td class="category">Aktion</td>
			</tr>			
			
				<?php 
				$query = "SELECT user_id FROM " . plugin_table("certificates") . ";";
				$result = db_query_bound( $query);
				$count = $result->RecordCount();
				foreach($result->getArray(-1) as $row)
				{
					echo '<tr class="row-1">';
					echo 	'<form name="delete_smime_user" method="post" enctype="multipart/form-data"
							action="'. plugin_page( 'delete_smime_user' ).'">';
					echo 	'<input type="hidden" name="delete" value="'. $row['user_id'] .'"></input>';
					echo "<td>";
					echo user_get_name($row['user_id']);
					echo "</td>";
					echo "<td>";
					echo '<input type="submit" class="button"
					value="'.plugin_lang_get( 'delete' ).'" />';
					echo "</form>";
					echo 	'<form name="send_test_smime_user" method="post" enctype="multipart/form-data"
							action="'. plugin_page( 'send_test_smime_user' ).'">';
					echo 	'<input type="hidden" name="recipient" value="'. $row['user_id'] .'"></input>';
					echo '<input type="submit" class="button"
					value="'.plugin_lang_get( 'test' ).'" />';
					echo "</form>";
					echo "</td>";
					
					echo "</tr>";
				}
				
				?>
			</tr>
		</table>
</div>

<div align="center">
	<form name="add_smime_user" method="post" enctype="multipart/form-data"
		action="<?php echo plugin_page( 'add_smime_user' )?>">
		<?php echo form_security_field( 'plugin_smime_action' ) ?>
		<table class="width75">
			<tr>
				<td class="form-title" colspan="5"><?php echo lang_get( 'plugin_smime_add_user_title' ) ?>
				</td>
			</tr>
			<tr class="row-1">
				<td class="category">User:</td>
				<td class="category"><select name="user_id[]" multiple="multiple"
					size="10">
					<?php print_user_option_list(ALL_USERS,ALL_PROJECTS); ?>
				</select></td>
			</tr>
			<tr class="row-1">
				<td class="category" width="25%"><?php echo plugin_lang_get( 'select_certificate_file' )?><br />
				<?php echo '<span class="small">(' . lang_get( 'max_file_size' ) . ': ' . number_format( $t_max_file_size / 1000 ) . 'k)</span>'?>
				</td>
				<td width="85%"><input type="hidden" name="max_file_size"
					value="<?php echo $t_max_file_size?>" /> <input type="hidden"
					name="step" value="1" /> <input name="file" type="file" size="40" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center"><input type="submit" class="button"
					value="<?php echo lang_get( 'plugin_smime_submit' )?>" />
				</td>
			</tr>
		</table>
	</form>
</div>
				<?php
				html_page_bottom1( __FILE__ );
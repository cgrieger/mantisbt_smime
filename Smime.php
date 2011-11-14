<?php

require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( 'SmimePhpmailer.php');

class SmimePlugin extends MantisPlugin
{
	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = lang_get( 'plugin_smime_title' );
		$this->description = lang_get( 'plugin_smime_description' );
		$this->page = 'config';

		$this->version = '1.0';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'Chris Grieger - syngenio AG';
		$this->contact = 'Chris.Grieger@syngenio.de';
		$this->url = 'http://www.syngenio.de';
	}

	/**
	 * Registering hooks
	 * @see MantisPlugin::hooks()
	 */
	function hooks( ) {
		$hooks = array(
			'EVENT_MENU_MANAGE' => 'manage_menu',
			'EVENT_NOTIFY_USER_EXCLUDE' => 'sendSmimeMail' /* This hook is called before any notification is sent */
		);
		return $hooks;
	}
	/**
	 *
	 * Callback for the EVENT_MENU_MANAGE hook
	 */
	function manage_menu( ) {
		return array( '<a href="' . plugin_page( 'manage_smime_page' ) . '">' . plugin_lang_get( 'menu_manage' ) . '</a>', );
	}

	/**
	 * Creates the DB schema for this plugin
	 */
	function schema()
	{
		$schema[] = array( 'CreateTableSQL', array( plugin_table("certificates"),
		"id			 I  UNSIGNED NOTNULL PRIMARY AUTOINCREMENT,
		  user_id 		 I  UNSIGNED NOTNULL DEFAULT '0'"));
		/*
		 * Tabellenspalte für das Zertifikat
		 */
		$schema[] = array( 'AddColumnSQL', array( plugin_table("certificates"), "
												cert		B NOTNULL DEFAULT ''" ) );
		return $schema;
	}
	/**
	 *
	 *  This event allows a plugin to selectively exclude indivdual
	 users from the recipient list for a notification. The event
	 is signalled for every user in the final reicipient list,
	 including recipients added by the event NOTIFY_USER_INCLUDE
	 as described above.

	 Parameters
	 * <Integer>: Bug ID
	 * <Integer>: User ID
	 * <String>: Notification type

	 Return Value
	 * <Boolean>: True to exclude the user, false otherwise
	 */
	static function sendSmimeMail($event,$bug_id, $type,$user_id)
	{		
		$pubkey = SmimePlugin::loadPubkey($user_id);
		/*
		 * Process normally if no key is available
		 */
		if($pubkey == false){
			return false;
		}

		/*
		 * Load the users email address
		 */
		$recipient_email = user_get_email($user_id);

		/*
		 * Prepare bug data <Array>
		 */
		$bug_data = email_build_visible_bug_data( $user_id, $bug_id, null /*Nicht bekannt*/);

		/*
		 * Create Mail Subject
		 */
		$subject = '[' . $bug_data['email_project'] . ' ' . bug_format_id( $bug_data['email_bug'] ) . ']: ' . $bug_data['email_summary'];

		/*
		 * Create the message which is displayed before the actual bug text.
		 * 
		 * TODO: Theres no possibility to display new relations like in normal mantis notifications.
		 * 
		 */
		$message = plugin_lang_get($type) . "\r\n";
		
		$message .= email_format_bug_message( $bug_data );

		/*
		 * Load config
		 */
		$from_email = config_get("from_email");
		$from_name = config_get("from_name");
		$smtp_user = config_get("smtp_username");
		$smtp_pass = config_get("smtp_password");
		$smtp_host = config_get("smtp_host");



		// Setup mail headers.
		$headers = array("From" => $from_email, "To" => $recipient_email, "Subject" => $subject, "X-Mailer" => "PHP/".phpversion());

		/*
		 * Build the message
		 */
		$body = $message;
		openssl_pkey_get_public	($pubkey);

		//instance of new Mailer
		$mail = new SmimePHPMailer();

		$mail->IsSMTP();  // telling the class to use SMTP		
		

		$mail->Host     = $smtp_host; // SMTP server
		$mail->SMTPAuth = true;
		$mail->Username     = $smtp_user;
		$mail->Password     = $smtp_pass;

		$mail->FromName     = $from_name;
		$mail->From     = $from_email;
		$mail->email_address = $recipient_email;
		$mail->AddAddress($recipient_email,user_get_name($user_id));

		$mail->Subject  = $subject;
		$mail->Body     = $body;
		$mail->WordWrap = 50;
		
		$mail->Encoding = 'base64';

		//add publickey array for encryption
		$mail->setEncrypt_key_files(array($pubkey));
		if(!$mail->Send()) {
			error_log('Message was not sent.');
			error_log('Mailer error: ' . $mail->ErrorInfo);
		} else {
			error_log('Message has been sent.');
		}
		return true;
	}
	/**
	 * 
	 * Loads the public key for the specified $user_id
	 * @param unknown_type $user_id
	 * @return Returns the Certificate as a string or false on error
	 */
	static function loadPubkey($user_id)
	{
		$query = "SELECT * FROM " . plugin_table("certificates") . " WHERE user_id=" . $user_id . ";";
		$result = db_query_bound( $query);
		/*
		 * No certificate found
		 */		
		if($result->RecordCount() == 0)
		{
			return false;
		}	
		/*
		 * Get the public key from the result
		 */
		$row = $result->getArray(-1);
		$pubkey = $row[0]['cert'];
		return $pubkey;
	}
}
<?php

/**
 * Der folgende Code wurde von
 * http://fdamico.de/projects/phpmailer/
 *
 * Übernommen und angepasst. Danke!
 */
require_once( BASE_PATH . DIRECTORY_SEPARATOR  . 'library' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'class.phpmailer.php' );
require_once( BASE_PATH . DIRECTORY_SEPARATOR  . 'library' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'class.smtp.php' );

class SmimePHPMailer extends PHPMailer {

	private $encrypt_key_files = false; //is openssl_get_publickey array
	public $email_address;
	/**
	 * Sends mail via SMTP using PhpSMTP (Author:
	 * Chris Ryan).  Returns bool.  Returns false if there is a
	 * bad MAIL FROM, RCPT, or DATA input.
	 *
	 * s/mime encryption added by FDA
	 *
	 * @access public
	 * @return bool
	 */
	public function SmtpSend($header, $body) {
		$error = '';
		$bad_rcpt = array();
		$this->smtp = new SMTP();
		$this->smtp->Connect($this->Host);
		$this->smtp->Hello();

		if (count($this->encrypt_key_files)>0) {
			//create temp files that are needed for encryption
			$file =  tempnam(sys_get_temp_dir(), "tmpmsg");
			$file2 = tempnam(sys_get_temp_dir(), "tmpenc");

			//write clear msg to file
			$fp = fopen($file, "w");
			fwrite($fp, $header . $body);			
			fclose($fp);
			// Setup mail headers.
			$headers = array("From" => $this->FromName, "To" => $this->email_address, "Subject" => $this->Subject, "X-Mailer" => "PHP/".phpversion());
			//encrypted datafile
			if (openssl_pkcs7_encrypt($file, $file2, $this->encrypt_key_files,$headers,0,OPENSSL_CIPHER_3DES)) {

			//read encrypted data from file
			$fp = fopen($file2, "r");
			$encData = '';
			while(!feof($fp)){
				$encData = $encData . fread($fp, 1024);
			}
			fclose($fp);
			// Mail is encrypted, send the data now
			if(config_get_global("smtp_connection_mode")=='tls')
			{
				$this->smtp->StartTLS();
			}
			$this->smtp->Authenticate($this->Username,$this->Password);
			$this->smtp->Mail($smtp_from);
			$this->smtp->Recipient($this->email_address);
			$res = $this->smtp->Data($encData);
          }
		} else {
			// no keys dont s/mime
			$res = $this->smtp->Data($header . $body);
		}



		if(!$res) {
			$this->SetError(('data_not_accepted'));
			$this->smtp->Quit();
			return false;
		}
		if($this->SMTPKeepAlive == true) {
			$this->smtp->Reset();
		} else {
			$this->smtp->Quit();
		}

		return true;
	}



	//setter for encrypt_key_files
	public function setEncrypt_key_files($encrypt_key_files) {
		$this->encrypt_key_files = $encrypt_key_files;
	}





}

?>
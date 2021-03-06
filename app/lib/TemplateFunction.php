<?php
/*

  Class Name					:	TemplateFunctions
  author 						:	Nicolas Naresh
  Date							:	June 16, 2014
  Purpose						:	asts as Template functions library contains functions to be used in laravel
  									templates.
*/

class TemplateFunction{
	public static function getIntegerRangeDropdown($from, $to){
		$arr = array_map(function($i)
			{
				return sprintf("%02s",$i);
			},
			range($from, $to)
		);
		$arr1 = array_combine($arr,$arr);
		return $arr1;
	}

	public static function getFullLeaveTypeName($ltype){
		$fullNames = array(
			"LEAVE" => "Full Day",
			"FH" => "First Half",
			"SH" => "Second Half",
			"CSR" => "CSR",
			"LONG" => "Long Leave"
		);
		if(array_key_exists($ltype, $fullNames)){
			return $fullNames[$ltype];
		}
		else{
			return $ltype;
		}
	}

	public static function getLeaveTypeSummary($ltype){
		return "(" . $ltype . ")";
	}

	public static function fakeName($name){
		$salt = Config::get("app.lms_key");
		return base64_encode(TemplateFunction::encrypt($name, $salt));
	}
	public static function originalName($enc_name){
		$salt = Config::get("app.lms_key");
		return TemplateFunction::decrypt(base64_decode($enc_name), $salt);
	}
	/**
	 * Returns an encrypted & utf8-encoded
	 */
	public static function encrypt($pure_string, $encryption_key) {
	    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
	    return $encrypted_string;
	}

	/**
	 * Returns decrypted original string
	 */
	public static function decrypt($encrypted_string, $encryption_key) {
	    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
	    return $decrypted_string;
	}

	public static function requireGoogleCalendarApi(){
		require_once( base_path() . '/vendor/google/apiclient/src/Google/Client.php' );
    	require_once( base_path() . '/vendor/google/apiclient/src/Google/Service/Calendar.php' );
	}

	public static function getGoogleCalendarCreds(){
		$googleCreds = array();
		$googleCreds['clientId'] = Config::get('google.client_id');
	    $googleCreds['serviceAccountName'] = Config::get('google.service_account_name');
	    $googleCreds['keyFileLocation'] = Config::get('google.key_file_location');
	    return $googleCreds;
	}

	public static function getUIDateClass($leaveOption, $leaveType){
		if($leaveOption == "CSR" || $leaveOption == ""){
			return "date-single";
		}
		else{
			switch($leaveType){
				case "MULTI":
					return "date-multi";
				case "LONG":
					return "date-long";
				case "LEAVE":
				case "FH":
				case "SH":
					return "date-single";
				default:
					return "date-single";
			}
		}
	}
}
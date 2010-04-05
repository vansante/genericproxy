<?php
/**
 * Gathers error messages and returns error output when queried to do such
 * 
 */
Class ErrorHandler{
	private static $arr_error_buffer;
	private static $output_buffer;
	
	/**
	 * Add a new error to the error buffer
	 * 
	 * @static
	 * @param String $type
	 * @param String $message
	 */
	public static function addError($type,$message){
		$index = count(self::$arr_error_buffer);
		
		self::$arr_error_buffer[$index]['type'] = $type;
		self::$arr_error_buffer[$index]['text'] = $message;
	}
	
	/**
	 * Echo the contents of the error buffer to screen
	 * 
	 * @static
	 */
	public static function returnOutput(){
		self::$output_buffer .= '<reply action="error">';
		foreach(self::$arr_error_buffer as $error){
			if($error['type'] == 'error'){
				self::$output_buffer .= '<message text="'.$error['text'].'" />';
			}
			elseif($error['type'] == 'formerror'){
				$tag = 'id';
				self::$output_buffer .= '<formfield id="'.$error['text'].'" />';
			}

		}
		self::$output_buffer .= '</reply>';
		echo self::$output_buffer;
	}
	
	/**
	 * Return a count of the number of errors in the buffer
	 * 
	 * @static
	 * @return int
	 */
	public static function errorCount(){
		return count(self::$arr_error_buffer);
	}
}
?>
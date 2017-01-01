<?php

class ErrHandlers {

	public static $callbacks = array(); # user added callbacks

	public static function init() {
		# args are variable length, a single array, or single rethrow item
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc>1) ErrHandlers::rethrow($argv);
		
		elseif ($argc===1) {
			if (is_array($argv[0])) {
				foreach ($argv[0] as $method=>$args) {
					if ($method==='rethrow') ErrHandlers::rethrow($args);
					elseif ($method==='categories') ErrHandlers::catergories($args);
				}
			} else ErrHandlers::rethrow($argv[0]);
		}
		set_error_handler(function ($errno, $errstr, $errfile, $errline){
			# call any function or methods added to static class before init()
			if (!empty(ErrHandlers::$callbacks)) {
				$args = array( $errno, $errstr, $errfile, $errline );
				foreach (ErrHandlers::$callbacks as $funcname_or_classmethod_arr) {
					if (call_user_func_array($funcname_or_classmethod_arr, $args))
						return true;
				}
			}
			# ErrHandlers::$rethrow is private but for some bizarre reason
			# is actually available inside this callback. ¯\_(ツ)_/¯
			if (isset(ErrHandlers::$rethrow['errno'][$errno])
			 || isset(ErrHandlers::$rethrow['errstr'][$errstr]) ) {
				if (false!== $c=ErrHandlers::constName($errno)) $errstr = "$c: $errstr";
				throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
				// return true; ### not needed since throw covers it?
			}
			return false;
		});
	}

	private static $rethrow = array( 'errno' =>array(), 'errstr'=>array(), );
	# sets ErrHandlers::$rethrow, dividing where it puts items in this array by type.
	# strings-> ErrHandlers::$rethrow['errstr'], ints-> ErrHandlers::$rethrow['errno']
	public static function rethrow() {
		# args are variable length, a single array, or single rethrow item
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc===0) return ErrHandlers::$rethrow;
		if ($argc===1) {
			if (is_array($argv[0])) $argv = $argv[0];
			else $argv = array($argv[0]);
		}
		foreach ($argv as $k=>$v) {
			if (is_string($v)) ErrHandlers::$rethrow['errstr'][$v] = 1;
			elseif (is_int($v)) ErrHandlers::$rethrow['errno'][$v] = 1;
			elseif (is_scalar($v) ) {
				if ($v) {
					if (is_string($k)) ErrHandlers::$rethrow['errstr'][$v] = 1;
					elseif (is_int($k)) ErrHandlers::$rethrow['errno'][$v] = 1;
				} else {
					if (is_string($k)) unset(ErrHandlers::$rethrow['errstr'][$v]);
					elseif (is_int($k)) unset(ErrHandlers::$rethrow['errno'][$v]);
				}
			}
		}
	}
	# just a helpful checker method to see if code or message is being rethrow
	public static function isRethrown() {
		foreach (func_get_args() as $v) {
			if (is_int($v)
			 && isset(ErrHandlers::$rethrow['errno'][$errno]))   return true;
			elseif (is_string($v)
			 && isset(ErrHandlers::$rethrow['errstr'][$errstr])) return true;
		}
		return false;
	}

	private static $categories = array( 'Core'=>1, 'user'=>1, );
	# getter setter for ErrHandlers::$categories which is a dictionary used
	# to make searching for constant names with get_defined_constants()
	# organized by category quicker by limiting the number of categories.
	public static function categories() {
		# args are variable length, a single array, or single category
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc===0) return ErrHandlers::$categories;
		if ($argc===1) {
			if (is_array($argv[0])) {
				$argv = $argv[0]; 
				if (is_string( $argv[key($argv)] )) $argv = array_flip($argv);
			} else $argv = array($argv[0]);
		}
		$consts = get_defined_constants(true); 
		foreach ($argv as $category=>$truthy) {
			if ($truthy) {
				if (isset($consts[$category]))
					ErrHandlers::$categories[$category] = 1;
				else unset(ErrHandlers::$categories[$category]);
			} else unset(ErrHandlers::$categories[$category]);
		}
	}
	# getter for constant name from it's value. Only catergories of constants
	# in ErrHandlers::$categories set by ErrHandlers::categories() is used.
	public static function constName($errno) {
		$consts = get_defined_constants(true);
		foreach (ErrHandlers::$categories as $category=>$b) {
			$constname = array_search($errno, $consts[$category],true);
			if ($constname!==false) return $constname;
		}
		return $constname;
	}
}

// // ErrHandlers::rethrow("Object of class stdClass could not be converted to string");
// function obstrerr($errno, $errstr, $errfile, $errline) {
// 	if ($errstr==="Object of class stdClass could not be converted to string")
// 		echo $errno."\n";
// 		// throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
// }
// ErrHandlers::$callbacks[] = 'obstrerr';

// echo ErrHandlers::constName(4096);

ErrHandlers::init(E_RECOVERABLE_ERROR);





// var_dump(ErrHandlers::rethrow());



try {
	echo new stdClass;
}
catch (Exception $err){
	echo $err->getMessage(), PHP_EOL;
}



// $arr = get_defined_constants(true);

// // var_dump(array_keys($arr));
// var_dump($arr['Core']);

// output is "MY_CONSTANT"
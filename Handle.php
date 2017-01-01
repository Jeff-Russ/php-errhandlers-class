<?php

namespace JR;

class Handle {

	public static $callbacks = array(); # user added callbacks

	public static function init() {
		# args are variable length, a single array, or single rethrow item
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc>1) Handle::rethrow($argv);
		
		elseif ($argc===1) {
			if (is_array($argv[0])) {
				foreach ($argv[0] as $method=>$args) {
					if ($method==='rethrow') Handle::rethrow($args);
					elseif ($method==='categories') Handle::catergories($args);
				}
			} else Handle::rethrow($argv[0]);
		}
		set_error_handler(function ($errno, $errstr, $errfile, $errline, $context){
			# call any function or methods added to static class before init()
			if (!empty(Handle::$callbacks)) {
				$args = array( $errno, $errstr, $errfile, $errline, $context );
				foreach (Handle::$callbacks as $funcname_or_classmethod_arr) {
					if (call_user_func_array($funcname_or_classmethod_arr, $args))
						return true;
				}
			}
			# Handle::$rethrow is private but available inside this callback.
			if (isset(Handle::$rethrow['errno'][$errno])
			 || isset(Handle::$rethrow['errstr'][$errstr]) ) {
				if (false!== $c=Handle::constName($errno)) $errstr = "$c: $errstr";
				throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
				// return true; ### not needed since throw covers it?
			}
			return false;
		});
	}

	private static $rethrow = array( 'errno' =>array(), 'errstr'=>array(), );
	# sets Handle::$rethrow, dividing where it puts items in this array by type.
	# strings-> Handle::$rethrow['errstr'], ints-> Handle::$rethrow['errno']
	public static function rethrow() {
		# args are variable length, a single array, or single rethrow item
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc===0) return Handle::$rethrow;
		if ($argc===1) {
			if (is_array($argv[0])) $argv = $argv[0];
			else $argv = array($argv[0]);
		}
		foreach ($argv as $k=>$v) {
			if (is_string($v)) Handle::$rethrow['errstr'][$v] = 1;
			elseif (is_int($v)) Handle::$rethrow['errno'][$v] = 1;
			elseif (is_scalar($v) ) {
				if ($v) {
					if (is_string($k)) Handle::$rethrow['errstr'][$v] = 1;
					elseif (is_int($k)) Handle::$rethrow['errno'][$v] = 1;
				} else {
					if (is_string($k)) unset(Handle::$rethrow['errstr'][$v]);
					elseif (is_int($k)) unset(Handle::$rethrow['errno'][$v]);
				}
			}
		}
	}
	# just a helpful checker method to see if code or message is being rethrow
	public static function isRethrown() {
		foreach (func_get_args() as $v) {
			if (is_int($v)
			 && isset(Handle::$rethrow['errno'][$errno]))   return true;
			elseif (is_string($v)
			 && isset(Handle::$rethrow['errstr'][$errstr])) return true;
		}
		return false;
	}

	private static $categories = array( 'Core'=>1, 'user'=>1, );
	# getter setter for Handle::$categories which is a dictionary used
	# to make searching for constant names with get_defined_constants()
	# organized by category quicker by limiting the number of categories.
	public static function categories() {
		# args are variable length, a single array, or single category
		$argc=func_num_args(); $argv=func_get_args();
		if ($argc===0) return Handle::$categories;
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
					Handle::$categories[$category] = 1;
				else unset(Handle::$categories[$category]);
			} else unset(Handle::$categories[$category]);
		}
	}
	# getter for constant name from it's value. Only catergories of constants
	# in Handle::$categories set by Handle::categories() is used.
	public static function constName($errno) {
		$consts = get_defined_constants(true);
		foreach (Handle::$categories as $category=>$b) {
			$constname = array_search($errno, $consts[$category],true);
			if ($constname!==false) return $constname;
		}
		return $constname;
	}
}
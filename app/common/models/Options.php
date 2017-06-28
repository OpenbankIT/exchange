<?php

namespace App\Models;

class Options extends ModelBase
{
	static $options = null;

	public static function set($key, $value)
	{
		if (empty($key)) {
			return false;
		}

		$key = trim($key);
		$option = self::findFirst(array(
	    	'opt_key = :opt_key:',
	        'bind' => array('opt_key' => $key)
	    ));

		if (!empty($value)) {
			if (is_array($value)) {
				$value = serialize($value);
			} else {
				$value = trim($value);
			}
		}

		if (empty($option)) {
			if (empty($value)) {
				return false;
			} else {
				# insert new
				$option = new Options();
				$option->opt_key = $key;
				$option->opt_value = $value;
				return $option->create();
			}
		} else {
			if (empty($value)) {
				# delete
				$option->delete();
				return true;
			} else {
				# update
				$option->opt_value = $value;
				return $option->save();
			}
		}
	}

	public static function get($key, $default = false)
	{
		self::init();

		$key = trim($key);
		if (empty($key) || empty(self::$options[$key])) {
	    	return $default;
		}

	    return self::$options[$key];
	}

	public static function getAll()
	{
		self::init();
		return self::$options;
	}

	public static function init()
	{
		if (is_null(self::$options)) {

			$options = array();
			foreach (self::find() as $key => $value) {
				$options[$value->opt_key] = self::is_serialized($value->opt_value)? @unserialize($value->opt_value) : $value->opt_value;
			}

			self::$options = $options;
		}

		return true;
	}

	private static function is_serialized($data, $strict = true)
	{
	    if (!is_string($data))
	        return false;

	    $data = trim($data);

	    if ('N;' == $data)
	        return true;

	    if (strlen( $data ) < 4)
	        return false;

	    if (':' !== $data[1])
	        return false;

	    if ($strict) {
	        $lastc = substr( $data, -1 );
	        if (';' !== $lastc && '}' !== $lastc) {
	                return false;
	        }
	    } else {
	        $semicolon = strpos( $data, ';' );
	        $brace     = strpos( $data, '}' );
	        // Either ; or } must exist.
	        if (false === $semicolon && false === $brace )
	                return false;
	        // But neither must be in the first X characters.
	        if (false !== $semicolon && $semicolon < 3 )
	                return false;
	        if (false !== $brace && $brace < 4 )
	                return false;
	    }

	    $token = $data[0];
	    switch ($token) {
	        case 's' :
                if ($strict) {
                        if ('"' !== substr( $data, -2, 1 )) {
                                return false;
                        }
                } elseif (false === strpos( $data, '"' )) {
                        return false;
                }
	        case 'a' :
	        case 'O' :
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
	        case 'b' :
	        case 'i' :
	        case 'd' :
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	    }
	    return false;
	}
}
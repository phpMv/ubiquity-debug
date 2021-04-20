<?php


namespace Ubiquity\debug\core;


class TypeError {
	const ERRORS=[E_ERROR=>'Error',E_PARSE=>'Parse exception',E_COMPILE_ERROR=>'Compile error',E_WARNING=>'Warning'];
	public static function asString($error){
		if($error instanceof \Error || $error instanceof \Exception){
			return \get_class($error);
		}
		if(\is_int($error)) {
			return self::ERRORS[$error] ?? $error;
		}
		if(is_string($error)) {
			return $error;
		}
		return 'Unknown error';
	}

}
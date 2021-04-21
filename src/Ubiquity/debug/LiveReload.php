<?php
namespace Ubiquity\debug;


use Ubiquity\utils\http\URequest;

/**
 * Class for livereload in dev mode.
 * Ubiquity\debug$LiveReload
 * This class is part of Ubiquity
 *
 * @author jc
 * @version 1.0.0
 *
 */
class LiveReload {
	public static function start(int $port=35729):string{
		if(!URequest::isAjax()) {
			return '<script>document.write(\'<script src="http://\' + (location.host || \'localhost\').split(\':\')[0] +
				\':' . $port . '/livereload.js?snipver=1"></\' + \'script>\')</script>';
		}
		return '';
	}
	
	public static function isActive():bool{
		$exitCode=0;
		\exec('livereload --version', $_, $exitCode);
		return $exitCode == 1;
	}

}

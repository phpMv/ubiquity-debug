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
	/**
	 * Add livereload webSocket.
	 * @param int $port
	 * @return string
	 */
	public static function start(int $port=35729):string{
		if(!URequest::isAjax()) {
			$nonce=self::getNonce();
			return '<script'.$nonce.'>document.write(\'<script'.$nonce.' src="http://\' + (location.host || \'localhost\').split(\':\')[0] +
				\':' . $port . '/livereload.js?snipver=1"></\' + \'script>\')</script>';
		}
		return '';
	}
	/**
	 * Check if Livereload js intallation.
	 * @return bool
	 */
	public static function hasLiveReload():bool{
		$exitCode=0;
		\exec('livereload --version', $_, $exitCode);
		return $exitCode == 1;
	}

	private static function getNonce($name='jsUtils'){
		if(\class_exists('\\Ubiquity\\security\\csp\\ContentSecurityManager')){
			if (\Ubiquity\security\csp\ContentSecurityManager::hasNonce($name)){
				$nonce=\Ubiquity\security\csp\ContentSecurityManager::getNonce($name);
				return " nonce=\"$nonce\" ";
			}
		}
		return '';
	}

}

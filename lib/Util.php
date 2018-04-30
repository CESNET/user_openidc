<?php
/**
 * ownCloud - user_openidc
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Miroslav Bauer, CESNET <bauer@cesnet.cz>
 * @copyright Miroslav Bauer, CESNET 2018
 * @license AGPL-3.0
 */

namespace OCA\UserOpenIDC;

use \OCP\IRequest;

/**
 * Helper function class
 */
class Util {

	const OIDC_COOKIE_NAME = 'mod_auth_openidc_session';

	/**
	 * Invalidates and removes an OIDC session cookie if present.
	 * TODO: DI, like in the \OC\User\Session:unsetMagicInCookie
	 *
	 * @param IRequest $request instance containing the cookie
	 *
	 * @return null;
	 */
	public static function unsetOIDCSessionCookie(IRequest $request) {
		$cookieName = Util::OIDC_COOKIE_NAME;
		if (in_array($cookieName, $_COOKIE)
			&& $request->getCookie($cookieName)
		) {
			$secure = $request->getServerProtocol() === 'https';
			unset($_COOKIE[$cookieName]);
			\setcookie(
				$cookieName, '',
				\time() - 3600, \OC::$WEBROOT, '', $secure, true
			);
			\setcookie(
				$cookieName, '', \time() - 3600,
				\OC::$WEBROOT . '/', '', $secure, true
			);
		}
	}
}

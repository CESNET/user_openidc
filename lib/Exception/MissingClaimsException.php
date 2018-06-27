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

namespace OCA\UserOpenIDC\Exception;

use \OCA\UserOpenIDC\Util;

/**
 * @package OCA\UserOpenIDC\Exception
 */
class MissingClaimsException extends \Exception {

	/* @var array(string) */
	private $claims;

	function __construct($claims) {
		$this->claims = $claims;
	}

	public function getMissingClaims() {
		return array_map(function($k) {
			$v = $k;
			switch ($k) {
				case Util::CLAIM_UID:
					$v = 'Identifier of user on a service';
					break;
				case Util::CLAIM_DN:
					$v = 'Full Name';
					break;
				case Util::CLAIM_EMAIL:
					$v = 'E-mail address';
					break;
				case Util::CLAIM_ALTUIDS:
					$v = 'Person principal usernames';
					break;
				case Util::CLAIM_ELIGIBLE:
					$v = 'IsCesnetEligibleLastSeen timestamp';
				default:
					break;
			}
			return $v;
		}, $this->claims);
	}
}

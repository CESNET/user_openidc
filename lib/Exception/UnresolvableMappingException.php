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


/**
 * @package OCA\UserOpenIDC\Exception
 */
class UnresolvableMappingException extends \Exception {

	/* @var array(string) */
	private $mappings;

	function __construct($mappings) {
		$this->mappings = $mappings;
	}

	public function getMappings() {
		return $this->mappings;
	}
}

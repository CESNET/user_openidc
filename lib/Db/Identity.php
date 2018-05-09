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

namespace OCA\UserOpenIDC\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @package OCA\UserOpenIDC\Db\Legacy;
 */
class Identity extends Entity {

	protected $oidcUserid;
	protected $ocUserid;
	protected $nickname;
	protected $lastSeen;
}

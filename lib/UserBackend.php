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

use \OC\AppConfig;
use \OC\User\Backend;
use \OCP\ILogger;
use \OCP\IUserManager;
use \OCP\Security\ISecureRandom;
use \OCP\IUserBackend;
use \OCA\UserOpenIDC\Util;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;

/**
 * OpenID Connect User Backend class
 *
 * @package OCA\UserOpenIDC
 */
class UserBackend extends Backend implements IUserBackend {

	private $appName;
	/** @var AppConfig */
	private $config;
	/** @var IUserManager */
	private $usrMgr;
	/** @var ISecureRandom */
	private $secRandom;
	/** @var ILogger */
	private $logger;
	/** @var array */
	private $logCtx;
	/** @var AttributeMapper */
	private $attrMapper;

	/**
	 * UserBackend constructor
	 *
	 * @param string $appName
	 * @param AppConfig $appConfig
	 * @param IUserManager $userMgr
	 * @param ISecureRandom $secRandom
	 * @param ILogger $logger
	 * @param AttributeMapper $attrMapper
	 */
	function __construct($appName, AppConfig $appConfig, IUserManager $userMgr,
		ISecureRandom $secRandom, ILogger $logger, AttributeMapper $attrMapper
	) {
		$this->appName = $appName;
		$this->config = $appConfig;
		$this->userMgr = $userMgr;
		$this->secRandom = $secRandom;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
		$this->attrMapper = $attrMapper;
	}
	/**
	 * Backend name to be shown in user management
	 *
	 * @return string the name of the backend to be shown
	 * @since 8.0.0
	 */
	public function getBackendName() {
		return 'OpenIDC';
	}
	/**
	 * Checks if OpenIDC ENV is provided and valid and
	 * maps OIDC sub claim to corresponding ownCloud account ID.
	 * It also autoprovisions new accounts when in provisioning mode.
	 *
	 * @param string $uid The username (should be empty)
	 * @param string $password The password (should be empty)
	 *
	 * @return string user account ID
	 *
	 * Check if the OpenIDC ENV is valid without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid=null, $password=null) {
		$mode = $this->config->getValue($this->appName, Util::MODE, 'inactive');
		if ($mode === 'inactive' || !$this->checkClaims()) {
			return false;
		}

		$userid = $this->attrMapper->getUserID();
		if (!$userid) {
			return false;
		}

		if (!$this->userMgr->userExists($userid)) {
			if ($mode === 'provisioning') {
				$this->logger->info(
					'Creating new account for: '
					. $userid, $this->logCtx
				);
				$this->userMgr->createUser(
					$userid, $this->secRandom->generate(
						30, ISecureRandom::CHAR_DIGITS
						. ISecureRandom::CHAR_LOWER
						. ISecureRandom::CHAR_UPPER
					)
				);
			} else {
				return false;
			}
		}
		return $userid;
	}

	/**
	 * Checks if all required OIDC claims are present and valid
	 *
	 * @return boolean true if all present and valid | false otherwise
	 */
	public function checkClaims() {
		$required = $this->attrMapper->getRequiredClaims();
		foreach ((array)$required as $claim) {
			if (!$this->attrMapper->getClaimValue($claim)) {
				$this->logger->warning(
					'Missing required OIDC claim:'
					. $claim, $this->logCtx
				);
				return false;
			}
		}
		return true;
	}
}

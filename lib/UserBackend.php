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
use \OC\User\AccountMapper;
use \OCP\ILogger;
use \OCP\IUserManager;
use \OCP\Security\ISecureRandom;
use \OCP\IUserBackend;
use \OCA\UserOpenIDC\Util;
use \OCA\UserOpenIDC\Db\IdentityMapper;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Db\Legacy\LegacyIdentityMapper;

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
	/** @var AccountMapper */
	private $accMapper;
	/** @var IdentityMapper */
	private $idMapper;
	/** @var LegacyIdentityMapper */
	private $legacyIdMapper;

	/**
	 * UserBackend constructor
	 *
	 * @param string $appName
	 * @param AppConfig $appConfig
	 * @param IUserManager $userMgr
	 * @param ISecureRandom $secRandom
	 * @param ILogger $logger
	 * @param AttributeMapper $attrMapper
	 * @param AccountMapper $accMapper
	 * @param IdentityMapper $idMapper
	 * @param LegacyIdentityMapper $legacyIdMapper
	 */
	function __construct($appName, AppConfig $appConfig, IUserManager $userMgr,
		ISecureRandom $secRandom, ILogger $logger,
		AttributeMapper $attrMapper, AccountMapper $accMapper,
		IdentityMapper $idMapper, LegacyIdentityMapper $legacyIdMapper
	) {
		$this->appName = $appName;
		$this->config = $appConfig;
		$this->userMgr = $userMgr;
		$this->secRandom = $secRandom;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
		$this->attrMapper = $attrMapper;
		$this->accMapper = $accMapper;
		$this->idMapper = $idMapper;
		$this->legacyIdMapper = $legacyIdMapper;
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

		$userid = $this->resolveUserID($userid, $this->attrMapper->getAltUserIDs());
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
				try {
					$account = $this->accMapper->getByUid($userid);
					$backend_class = \get_class($this);
					$this->logger->debug(
						'Updating Account backend to: '
						. $backend_class, $this->logCtx
					);
					$account->setBackend($backend_class);
					$this->accMapper->update($account);
				} catch (Exception $e) {
					$this->logger->error(
						'Account update failed', $this->logCtx
					);
				}
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
	/**
	 * Figures out the effective OC User ID for the given OIDC User ID
	 *
	 * @param string $oidcUserID OIDC User ID
	 * @param array $altUserIDs list of alternative User IDs
	 *
	 * @return string|null effective OC user account ID
	 */
	 public function resolveUserID($oidcUserID, $altUserIDs) {
		$userid = $this->idMapper->getOcUserID($oidcUserID);
		if (!$userid) {
			$uids = array();
			foreach ((array)$altUserIDs as $altUid) {
				$uids[] = $this->legacyIdMapper->getOcUid($altUid);
			}
			$uids = array_filter(array_unique($uids));
			if (count($uids) > 1) {
				//TODO: This should raise some fatal exception
				// this situation must be handled by admins manually
			} else {
				$userid = array_pop($uids);
				if (!$userid) {
					// If the user doesn't have any ID
					// mapping, use his current User ID
					$userid = $oidcUserID;
				}
				$this->idMapper->addIdentity(
					$oidcUserID, $userid, '', 0
				);
			}
		}
		$this->logger->info(
			'OIDC UID: '. $oidcUserID . ' resolved to: '. $userid,
			$this->logCtx
		);
		return $userid;
	 }
}

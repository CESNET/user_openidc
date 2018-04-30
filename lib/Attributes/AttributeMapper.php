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

namespace OCA\UserOpenIDC\Attributes;

use \OC\AppConfig;
use \OCP\IRequest;
use \OCP\ILogger;
use \OCA\UserOpenIDC\Util;

/**
 * Attribute mapper class repsponsible for mapping
 * OIDC claims to internal ownCloud User attributes
 *
 * @package OCA\UserOpenIDC
 */
class AttributeMapper {

	private $appName;
	/** @var IRequest */
	private $request;
	/** @var AppConfig */
	private $config;
	/** @var ILogger */
	private $logger;

	/**
	 * AttributeMapper constructor
	 *
	 * @param AppConfig $config application config
	 * @param IRequest $request
	 * @param ILogger $logger
	 */
	function __construct(AppConfig $config, IRequest $request, ILogger $logger) {
		$this->appName = 'user_openidc';
		$this->config = $config;
		$this->request = $request;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Returns a configured prefix of all OIDC claims
	 *
	 * @return string configured prefix or an empty string
	 */
	public function getClaimPrefix() {
		return $this->config->getValue(
			$this->appName, Util::CLAIM_PREFIX, Util::CLAIM_PREFIX_DEFAULT
		);
	}
	/**
	 * Returns a prefixed OIDC claim name
	 *
	 * @param string $attribute attribute name
	 *
	 * @return string prefixed claim name | null for undefined attribute mapping
	 */
	public function getClaimName($attribute) {
		$claimName = $this->config->getValue($this->appName, $attribute, null);
		if ($claimName) {
			$prefix = $this->getClaimPrefix();
			if (substr($claimName, 0, strlen($prefix)) !== $prefix) {
				return $prefix . $claimName;
			} else {
				return $claimName;
			}
		}
	}
	/**
	 * Gets a value of OIDC claim for an OC attribute
	 *
	 * @param string $attribute name
	 *
	 * @return string value of claim corresponding to attribute name | null if not exists
	 */
	public function getClaimValue($attribute) {
		$claimName = $this->getClaimName($attribute);
		if ($claimName && isset($this->request->server[$claimName])) {
			return $this->request->server[$claimName];
		}
	}
	/**
	 * Obtain ownCloud User ID from OIDC claim
	 *
	 * @return string user account id | null for invalid or missing sub
	 */
	public function getUserID() {
		$userid = $this->getClaimValue(Util::CLAIM_UID);
		if (!preg_match("/^[a-zA-Z0-9_\.@-]*$/", $userid, $match)) {
			$this->logger->warning('Invalid or missing OIDC sub:' . $userid, $this->logCtx);
			return null;
		}
		return $userid;
	}
	/**
	 * Obtain display name from OIDC claim
	 *
	 * @return string display name | null for invalid or missing dn claim
	 */
	public function getDisplayName() {
		$dn = $this->getClaimValue(Util::CLAIM_DN);
		if (!preg_match("/^[^<>$#!%&\*\\_\+\.@-]*$/", $dn, $match)) {
			$this->logger->warning('Invalid or missing OIDC DN:' . $dn, $this->logCtx);
			return null;
		}
		return $dn;
	}
	/**
	 * Obtain e-mail from OIDC claim
	 *
	 * @return string e-mail address | null for invalid or missing address
	 */
	public function getEMailAddress() {
		$email = $this->getClaimValue(Util::CLAIM_EMAIL);
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		} else {
			$this->logger->warning('Invalid or missing OIDC e-mail:' . $email, $this->logCtx);
			return null;
		}
	}
	/**
	 * Returns attribute names that must have corresponding OIDC claims set
	 *
	 * @return array required attribute names | array('claim_userid') as default
	 */
	public function getRequiredClaims() {
		$required = explode(
			',', $this->config->getValue(
				$this->appName,
				Util::REQUIRED_CLAIMS,
				Util::CLAIM_UID
			)
		);
		$required = array_filter($required);
		if (!in_array(Util::CLAIM_UID, $required)) {
			$required[] = Util::CLAIM_UID;
		}
		return $required;

	}
}

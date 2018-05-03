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
		$name = $this->getClaimName($attribute);
		if ($name && isset($this->request->server[$name])) {
			$value = $this->request->server[$name];
			if ($this->validate($attribute, $value)) {
				return $value;
			}
		}
		$this->logger->warning('Invalid or missing OIDC claim:' . $name, $this->logCtx);
	}
	/**
	 * Obtain ownCloud User ID from OIDC claim
	 *
	 * @return string user account id | null for invalid or missing sub
	 */
	public function getUserID() {
		return $this->getClaimValue(Util::CLAIM_UID);
	}
	/**
	 * Obtain a list of alternative UIDs of the User
	 *
	 * @return array list of all known User IDs | null if not found
	 */
	public function getAltUserIDs() {
		$altuids = $this->getClaimValue(Util::CLAIM_ALTUIDS);
		if ($altuids) {
			return (array)array_filter(explode(',', $altuids));
		}
	}
	/**
	 * Obtain display name from OIDC claim
	 *
	 * @return string display name | null for invalid or missing dn claim
	 */
	public function getDisplayName() {
		return $this->getClaimValue(Util::CLAIM_DN);
	}
	/**
	 * Obtain e-mail from OIDC claim
	 *
	 * @return string e-mail address | null for invalid or missing address
	 */
	public function getEMailAddress() {
		return $this->getClaimValue(Util::CLAIM_EMAIL);
	}
	/**
	 * Returns attribute names that must have corresponding OIDC claims set
	 *
	 * @return array required attribute names | array('claim_userid') as default
	 */
	public function getRequiredClaims() {
		$required = array_filter(
			explode(
				',', $this->config->getValue(
					$this->appName,
					Util::REQUIRED_CLAIMS,
					Util::CLAIM_UID
				)
			)
		);
		if (!in_array(Util::CLAIM_UID, $required)) {
			$required[] = Util::CLAIM_UID;
		}
		return (array)$required;

	}
	/**
	 * Checks if a claim value matches the expected format
	 *
	 * @param string $name claim name
	 * @param string $value claim value
	 *
	 * @return bool
	 */
	protected function validate($name, $value) {
		$valid = false;
		switch ($name) {
			case Util::CLAIM_UID:
				$valid = preg_match("/^[a-zA-Z0-9_\.@-]*$/", $value, $match);
				break;
			case Util::CLAIM_DN:
				$valid = preg_match("/^[^<>$#!%&\*\\_\+\.@-]*$/", $value, $match);
				break;
			case Util::CLAIM_EMAIL:
				$valid = filter_var($value, FILTER_VALIDATE_EMAIL);
				break;
			case Util::CLAIM_ALTUIDS:
				$uids = array_filter(explode(',', $value));
				$valid = true;
				foreach ($uids as $uid) {
					if (!$this->validate(Util::CLAIM_UID, $uid)) {
						$valid = false;
						break;
					}
				}
				break;
			default:
				$this->logger->warning('Unknown OIDC claim:' . $name, $this->logCtx);
				break;
		}
		return $valid;
	}
}

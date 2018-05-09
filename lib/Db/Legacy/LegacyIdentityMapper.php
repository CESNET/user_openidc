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

namespace OCA\UserOpenIDC\Db\Legacy;

use \OCP\ILogger;
use \OCP\IUserManager;
use \OCP\IDBConnection;
use \OCP\AppFramework\Db\Mapper;
use \OCP\AppFramework\Db\DoesNotExistException;
use \OCP\AppFramework\Db\MultipleObjectsReturnedException;
use \OCA\UserOpenIDC\Db\Legacy\LegacyIdentity;

/**
 * This mapper class is able to read user mappings from the
 * legacy db table created by the user_shib user backend app.
 * It doesn't modify the table contents in any way, however.
 *
 * @package OCA\UserOpenIDC\Db\Legacy
 */
class LegacyIdentityMapper extends Mapper {

	private $appName;
	/** @var array */
	private $logCtx;
	/** @var ILogger */
	private $logger;
	/** @var IUserManager */
	private $userManager;
	/** @var IDBConnection */
	protected $db;

	public function __construct(
		$appName, ILogger $logger, IDBConnection $db, IUserManager $userManager
	) {
		parent::__construct($db, 'legacy_users_mapping');
		$this->appName = $appName;
		$this->logger = $logger;
		$this->db = $db;
		$this->userManager = $userManager;
		$this->logCtx = array('app' => $this->appName);
	}
	/**
	 * Returns all identities associated with the OC user
	 *
	 * @param string $ocUid OC user uid
	 *
	 * @return array(LegacyIdentity) associated legacy idenitites
	 */
	public function getAllIdentities($ocUid) {
		$result = array();
		if (!$this->userManager->userExists($ocUid)) {
			$this->logger->error(
				sprintf('OC user %s doesn\'t exist', $ocUid),
				$this->logCtx
			);
			return $result;
		}
		$sql = sprintf('SELECT * FROM `%s` WHERE `oc_uid`=?', $this->getTableName());
		return $this->findEntities($sql, [$ocUid]);
	}
	/**
	 * Finds an identity by its SAML uid
	 *
	 * @param string $samlUid SAML identity uid
	 *
	 * @return LegacyIdentity|null identity DB entity
	 * or null if not found
	 */
	public function getIdentity($samlUid) {
		$identity = null;
		if (! $samlUid || $samlUid === '') { return null; }

		$sql = sprintf('SELECT * FROM `%s` WHERE `saml_uid` = ?', $this->getTableName());
		try {
			$identity = $this->findEntity($sql, [$samlUid]);
		} catch (DoesNotExistException $e) {
			$this->logger->warning(
				'Legacy identity for SAML uid: ' . $samlUid
				. ' not found.', $this->logCtx
			);
			return null;
		} catch (MultipleObjectsReturnedException $e) {
			$this->logger->error(
				'There are multiple Legacy identities for SAML uid:'
				. $samlUid, $this->logCtx
			);
			return null;
		}
		return $identity;
	}
	/**
	 * Finds all identities by its $samlEmail or $samlUid
	 *
	 * @param string $search pattern
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 *
	 * @return array(LegacyIdentity) identities found
	 */
	public function findIdentities($search='', $limit=null, $offset=null) {
		$sql = sprintf('SELECT * FROM `%s` WHERE LOWER(`saml_uid`) = LOWER(?)'
			. ' OR LOWER(`saml_email`) = LOWER(?)',
			$this->getTableName());
		return $this->findEntities($sql,
			array($search, $search), $limit, $offset);
	}
	/**
	 * Find OC uids having all linked identities
	 * last_seen beyond an expiration treshold
	 *
	 * @param int $expirationTreshold timestamp
	 *
	 * @return array(string) expired OC uids
	 */
	public function findExpired($expirationTreshold) {
		$sql = sprintf('SELECT `oc_uid` FROM `%s` GROUP BY `oc_uid`'
			.' HAVING MAX(`last_seen`) <= ?',
			$this->getTableName());
		$result = $this->findEntities($sql, [$expirationTreshold]);
		return array_unique(array_map(function ($id) {
			return $id->getOcUid(); }, $result));
	}
	/**
	 * Returns an OC account uid assigned with the SAML uid
	 *
	 * @param string $samlUid SAML identity uid
	 *
	 * @return string|false owncloud user id or false
	 * if mapping to oc uid doesn't exist or is invalid
	 */
	public function getOcUid($samlUid) {
		$identity = $this->getIdentity($samlUid);
		if ($identity) {
			return $identity->getOcUid();
		} else {
			return false;
		}
	}
	/**
	 * Sets a migrated flag on an identity record.
	 * This means that the record has been migrated to a new
	 * IdentityMapper table schema.
	 *
	 * @param string $ocUid that has been migrated
	 */
	 public function setMigrated($ocUid) {
		 $ids = $this->getAllIdentities($ocUid);
		 foreach((array)$ids as $id) {
			$identity->setMigrated(1);
			$this->update($identity);
		 }
	 }
}

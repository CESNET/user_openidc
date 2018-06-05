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

 namespace OCA\UserOpenIDC\Hooks;

use \OC\User\Backend;
use \OC\User\SyncService;
use \OC\User\AccountMapper;
use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IAppConfig;
use \OCP\IUserManager;
use \OCP\UserInterface;
use \OCA\UserOpenIDC\Util;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;

/**
 * @package OCA\UserOpenIDC\Hooks
 */
class UserHooks {

	/** @var string **/
	private $appName;
	/** @var IAppConfig **/
	private $config;
	/** @var IRequest **/
	private $request;
	/** @var AttributeMapper */
	private $attrMapper;
	/** @var AccountMapper */
	private $accMapper;
	/** @var IUserManager */
	private $userManager;
	/** @var UserInterface */
	private $userBackend;
	/** @var SyncService */
	private $syncService;
	/** @var ILogger */
	private $logger;
	/** @var array */
	private $logCtx;

	/**
	 * UserHooks constructor
	 *
	 * @param string $appName
	 * @param IAppConfig $config
	 * @param IRequest $request
	 * @param AttributeMapper $attrMapper
	 * @param AccountMapper $accMapper
	 * @param IUserManager $userManager
	 * @param UserInterface $userBackend
	 * @param SyncService $syncService
	 * @param ILogger $logger
	 */
	public function __construct($appName, IAppConfig $config, IRequest $request,
		AttributeMapper $attrMapper, AccountMapper $accMapper, IUserManager $userManager,
		UserInterface $userBackend, SyncService $syncService, ILogger $logger
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->attrMapper = $attrMapper;
		$this->accMapper = $accMapper;
		$this->userManager = $userManager;
		$this->userBackend = $userBackend;
		$this->syncService = $syncService;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
	}

	/**
	 * Connects all hooks to User specific events
	 *
	 * @return null
	 */
	public function connectHooks() {
		$this->userManager->listen(
			'\OC\User', 'postLogin', function ($user) {
				$this->postLoginHook($user);
			}
		);
		$this->userManager->listen(
			'\OC\User', 'logout', function () {
				$this->logoutHook();
			}
		);
	}
	/**
	 * Handles update of user attributes after login
	 *
	 * @param IUser $user
	 *
	 * @return null
	 */
	public function postLoginHook(IUser $user) {
		if ($this->config->getValue($this->appName, Util::AUTOUPDATE, 'no') !== 'yes') {
			return;
		}
		$storedDn = $user->getDisplayName();
		$actualDn = $this->attrMapper->getDisplayName();
		$storedEMail = $user->getEMailAddress();
		$actualEMail = $this->attrMapper->getEMailAddress();

		if ($user) {
			if ($actualDn && $storedDn !== $actualDn) {
				if ($this->userBackend->implementsActions(Backend::SET_DISPLAYNAME)) {
					$this->userBackend->setDisplayName(
						$user->getUID(), $actualDn
					);
				}
			}
			if ($actualEMail && $storedEMail !== $actualEMail) {
				$user->setEMailAddress($actualEMail);
			}
			try {
				$account = $this->accMapper->getByUid($user->getUID());
			} catch (Exception $e) {
				$this->logger->error('Could not find Account for '
				. $user->getUID() . '. Not syncing.', $this->logCtx);
				return;
			}
			/*
			 * WARN: This forces account sync to be done against a UserBackend,
			 * where the metadata about the user is actually stored
			 * (most often the \OC\User\Database), but leaves the
			 * Account's backend set to \OCA\UserOpenIDC\UserBackend.
			 * This is needed @since 10.0.8 for login with this backend
			 * to be possible.
			 */
			$account = $this->syncService->syncAccount($account, $this->userBackend);
			$this->accMapper->update($account);
		}
	}
	/**
	 * Destroys an OIDC session for the user if it exists and then
	 * initiates a standard logout.
	 *
	 * @return null
	 */
	public function logoutHook() {
		$this->logger->info('Invalidating OIDC session', $this->logCtx);
		Util::unsetOIDCSessionCookie($this->request);
	}
}

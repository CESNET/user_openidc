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

use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IAppConfig;
use \OCP\IUserManager;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Util;

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
	/** @var IUserManager */
	private $userManager;
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
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 */
	public function __construct($appName, IAppConfig $config, IRequest $request,
		AttributeMapper $attrMapper, IUserManager $userManager, ILogger $logger
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->attrMapper = $attrMapper;
		$this->userManager = $userManager;
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
			'\OC\User', 'postCreateUser', function ($user, $password) {
				$this->postCreateUserHook($user, $password);
			}
		);
		$this->userManager->listen(
			'\OC\User', 'postDelete', function ($user) {
				$this->postDeleteHook($user);
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
		if ($this->config->getValue($this->appName, 'backend_autoupdate', 'no') !== 'yes') {
			return;
		}
		$storedDn = $user->getDisplayName();
		$actualDn = $this->attrMapper->getDisplayName();
		$storedEMail = $user->getEMailAddress();
		$actualEMail = $this->attrMapper->getEMailAddress();

		if ($user) {
			if ($actualDn && $storedDn !== $actualDn) {
				$user->setDisplayName($actualDn);
			}
			if ($actualEMail && $storedEMail !== $actualEMail) {
				$user->setEMailAddress($actualEMail);
			}
		}
	}
	/**
	 * Destroys an OIDC session for the user if it exists and then
	 * initiates a standard logout.
	 *
	 * @return null
	 */
	public function logoutHook() {
		Util::unsetOIDCSessionCookie($this->request);
	}
}

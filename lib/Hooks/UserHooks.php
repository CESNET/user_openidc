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
use \OCP\IL10N;
use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IAppConfig;
use \OCP\IUserManager;
use \OCP\Mail\IMailer;
use \OCP\UserInterface;
use \OCP\IURLGenerator;
use \OCA\UserOpenIDC\Util;
use \OCA\UserOpenIDC\UserBackend;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Db\IdentityMapper;


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
	/** @var IdentityMapper */
	private $idMapper;
	/** @var IUserManager */
	private $userManager;
	/** @var UserInterface */
	private $userBackend;
	/** @var SyncService */
	private $syncService;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ILogger */
	private $logger;
	/** @var IMailer */
	private $mailer;
	/** @var \OC_Defaults */
	private $defaults;
	/** @var IL10N */
	private $l10n;
	private $defaultMailAddress;
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
	 * @param IdentityMapper $idMapper
	 * @param IUserManager $userManager
	 * @param UserInterface $userBackend
	 * @param SyncService $syncService
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param IMailer $mailer
	 * @param \OC_Defaults $defaults
	 * @param IL10N $l10n
	 * @param string $defaultMailAddress
	 */
	public function __construct($appName, IAppConfig $config, IRequest $request,
		AttributeMapper $attrMapper, AccountMapper $accMapper,
		IdentityMapper $idMapper, IUserManager $userManager,
		UserInterface $userBackend, SyncService $syncService,
		IURLGenerator $urlGenerator, ILogger $logger, IMailer $mailer,
		\OC_Defaults $defaults, IL10N $l10n, $defaultMailAddress
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->attrMapper = $attrMapper;
		$this->accMapper = $accMapper;
		$this->idMapper = $idMapper;
		$this->userManager = $userManager;
		$this->userBackend = $userBackend;
		$this->syncService = $syncService;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->defaultMailAddress = $defaultMailAddress;
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
		$this->userManager->listen(
			'\OC\User', 'postCreateUser', function($user) {
				$this->postCreateUserHook($user);
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
			// TODO: Admin Customizable list of users which backends shouldn't be touched
			if ($user->getUID() !== 'admin') {
				$account->setBackend(UserBackend::class);
			}
			$this->accMapper->update($account);

			$identity = $this->idMapper->getIdentityForOCUser($user->getUID());
			if ($identity) {
				$identity->setLastSeen(\time());
				$this->idMapper->update($identity);
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
		$this->logger->info('Invalidating OIDC session', $this->logCtx);
		Util::unsetOIDCSessionCookie($this->request);
	}
	/**
	 * Notifies a newly created user that
	 * an account has been created for him
	 *
	 * @return null
	 */
	 public function postCreateUserHook($user) {
		$email = $this->attrMapper->getEMailAddress();
		if ($email) {
			try {
				$mailBodyData = array(
					'username' => $user->getUID(),
					'url' => $this->urlGenerator->getAbsoluteURL('/')
				);
				$mailBodyTemplatePlain = new TemplateResponse(
					'settings',
					'email.new_user_plain_text',
					$mailBodyData,
					'blank'
				);
				$mailBodyTemplateHtml = new TemplateResponse(
					'settings',
					'email.new_user',
					$mailBodyData,
					'blank'
				);
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $this->attrMapper->getDisplayName()]);
				$message->setFrom([$this->defaultMailAddress]);
				$message->setSubject(
					$this->l10n->t('Your %s account was created',
					[$this->defaults->getName()])
				);
				$message->setHtmlBody($mailBodyTemplateHtml->render());
				$message->setPlainBody($mailBodyTemplatePlain->render());
				$this->mailer->send($message);
			} catch (\Exception $e) {
				$this->logger->error(
					'Sending new account e-mail failed for:'
					. $email . '. Error:' . $e->getMessage(),
					$this->logCtx
				);

			}
			$this->logger->info(
				'Sent new account notification to: ' . $email,
				$this->logCtx
			);
		}
	 }

}

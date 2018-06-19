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

namespace OCA\UserOpenIDC\Controller;

use \OC_Util;
use \OCP\IRequest;
use \OCP\ILogger;
use \OCP\IURLGenerator;
use \OCP\IUserSession;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCA\UserOpenIDC\Util;
use \OCA\UserOpenIDC\Exception\UnresolvableMappingException;

/**
 * LoginController class responsible for handling
 * logon of the users using OpenID Connect attributes
 */
class LoginController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserSession */
	private $session;
	/** @var ILogger */
	private $logger;
	private $logCtx;

	/**
	 * LoginController constructor
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct($appName, IRequest $request,
		IURLGenerator $urlGenerator, IUserSession $userSession, ILogger $logger
	) {

		parent::__construct($appName, $request);

		$this->urlGenerator = $urlGenerator;
		$this->session = $userSession;
		$this->logger = $logger;
		$this->logCtx = array('app' => $this->appName);
	}
	/**
	 * This method is associated with a route on which the OpenID Connect
	 * protection must be enforced by Apache.
	 * It initiates an internal login procedure and then, if successfull,
	 * redirects to the desired page. Call to $session->login() is
	 * handled by this app's UserBackend 'checkPassword' method.
	 *
	 * @param string $redirectUrl where to redirect to after succesfull login
	 *
	 * @PublicPage
	 * @NoAdminRequired
	 * @UseSession
	 *
	 * @return RedirectResponse redirection to $redirectUrl
	 */
	public function tryLogin($redirectUrl=null) {
		$this->logger->debug('Initiating OIDC login', $this->logCtx);
		try {
			$loginResult = $this->session->login('', null);
		} catch (UnresolvableMappingException $e) {
			return new TemplateResponse('core', '403', ['unresolved_uids' => $e->getMappings()], 'guest');
		}
		if ($loginResult) {
			$user = $this->session->getUser();
			if ($user) {
				$uid = $user->getUID();
				$this->logger->info('OIDC logged in: ' . $uid, $this->logCtx);
				$this->session->createSessionToken($this->request, $uid, $uid);

				if (!is_null($redirectUrl)
					&& $this->session->isLoggedIn()
					&& !strpos($redirectUrl, $this->appName)
				) {
					$location = $this->urlGenerator->getAbsoluteURL(urldecode($redirectUrl));
					// Deny the redirect if the URL contains a @
					// This prevents unvalidated redirects like ?redirect_url=:user@domain.com
					if (!is_null($location) && (strpos($location, '@') === false)) {
						   return new RedirectResponse($location);
					} else {
						return new RedirectResponse($this->getDefaultUrl());
					}
				} else {
					return new RedirectResponse($this->getDefaultUrl());
				}
			}
		} else {
			/**
			 * Something went wrong on the OIDC Provider side
			 * (e.g. the User didn't provide all the scopes required).
			 * Let the User try again by removing the OIDC session cookie.
			 */
			Util::unsetOIDCSessionCookie($this->request);

			$this->logger->error('OIDC login failed.', $this->logCtx);
			return new TemplateResponse('core', '403', array(), 'guest');
		}
	}

	/**
	 * @return string
	 */
	protected function getDefaultUrl() {
		return OC_Util::getDefaultPageUrl();
	}
}

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

use OCA\UserOpenIDC\UserBackend;
use OCA\UserOpenIDC\AppInfo\Application;

$app = new Application();
$c = $app->getContainer();
$urlGenerator = $c->query('URLGenerator');
$appName = $c->query('AppName');
$request = $c->query('Request');

if (!\OC::$CLI) {
	// properly register OIDC user & group backends and event hooks
	$c->query('UserManager')->registerBackend($c->query('UserBackend'));
	$c->query('GroupManager')->addBackend($c->query('GroupBackend'));
	$c->query('UserHooks')->connectHooks();

	// build an alternative login link url
	$requestParams = $request->getParams();
	$loginParams = array('requesttoken' => \OCP\Util::callRegister());
	if (array_key_exists('redirect_url', $requestParams)) {
		$loginParams['redirect_url'] = $requestParams['redirect_url'];
	}
	$loginRoute = $urlGenerator->linkToRoute(
		'user_openidc.login.tryLogin',
		$loginParams
	);
	\OC_App::registerLogIn(
		array(
			'name' => 'Log In with OpenID',
			'href' => $loginRoute
		)
	);
}

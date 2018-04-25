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

if (!\OC::$CLI) {
	// properly register OIDC user & group backends
	$c->query('UserManager')->registerBackend($c->query('UserBackend'));
	$c->query('GroupManager')->addBackend($c->query('GroupBackend'));

	$loginRoute = $urlGenerator->linkToRoute(
		'user_openidc.login.tryLogin',
		array('requesttoken' => \OCP\Util::callRegister())
	);
	\OC_App::registerLogIn(
		array(
			'name' => 'Log In with OpenID',
			'href' => $loginRoute
		)
	);
}

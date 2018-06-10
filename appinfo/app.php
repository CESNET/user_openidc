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
$l10n = $c->query('L10N');

// It is not necessary to activate backend
// for these URLs. The list comes from here:
// https://doc.owncloud.com/server/8.2/admin_manual/enterprise_user_management/user_auth_shibboleth.html#apache-configuration
$excludedUrls = '^/'
. '(status.php'
. '|remote.php'
. '|index.php/s/'
. '|public.php'
. '|cron.php'
. '|core/img/'
. '|index.php/apps/files_sharing/ajax/publicpreview.php$'
. '|index.php/apps/files/ajax/upload.php$'
. '|apps/files/templates/fileexists.html$'
. '|index.php/apps/files/ajax/mimeicon.php$'
. '|apps/gallery/templates/slideshow.html$'
. '|index.php/apps/gallery/ajax/getimages.php'
. '|index.php/apps/gallery/ajax/thumbnail.php'
. '|index.php/apps/gallery/ajax/image.php'
. '|.*\.css$'
. '|.*\.js$'
. '|.*\.woff$'
// Following routes requires this backend to be inactive
. '|index.php/settings/personal/changepassword'
. '|ocs'
. ')';
$excludedRegex = '/' . str_replace('/', '\/', $excludedUrls) . '/i';
$requestUri = $c->query('Request')->getRequestUri();

if (!\OC::$CLI && !preg_match($excludedRegex, $requestUri)) {
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
			'name' => $l10n->t('Sign In with OpenID'),
			'href' => $loginRoute
		)
	);
}

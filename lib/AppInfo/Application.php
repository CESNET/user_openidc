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

namespace OCA\UserOpenIDC\AppInfo;

use \OC\User\Database;
use \OC\User\SyncService;
use \OCP\AppFramework\App;
use \OCA\UserOpenIDC\UserBackend;
use \OCA\UserOpenIDC\GroupBackend;
use \OCA\UserOpenIDC\Hooks\UserHooks;
use \OCA\UserOpenIDC\Db\IdentityMapper;
use \OCA\UserOpenIDC\Controller\LoginController;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Db\Legacy\LegacyIdentityMapper;

/**
 * Main Application container class
 */
class Application extends App {

	/**
	 * Application constructor
	 *
	 * @param array $urlParams
	 */
	function __construct(array $urlParams=array()) {
		parent::__construct('user_openidc', $urlParams);

		$container = $this->getContainer();

		/**
		 * App services
		 */
		$container->registerService(
			'LoginController', function ($c) {
				return new LoginController(
					$c->query('AppName'),
					$c->query('Request'),
					$c->query('URLGenerator'),
					$c->query('UserSession'),
					$c->query('Logger')
				);
			}
		);
		$container->registerService(
			'UserBackend', function ($c) {
				return new UserBackend(
					$c->query('AppName'),
					$c->query('AppConfig'),
					$c->query('UserManager'),
					$c->query('SecureRandom'),
					$c->query('Logger'),
					$c->query('AttributeMapper'),
					$c->query('AccountMapper'),
					$c->query('IdentityMapper'),
					$c->query('LegacyIdentityMapper')
				);
			}
		);
		$container->registerService(
			'UserHooks', function ($c) {
				return new UserHooks(
					$c->query('AppName'),
					$c->query('AppConfig'),
					$c->query('Request'),
					$c->query('AttributeMapper'),
					$c->query('AccountMapper'),
					$c->query('IdentityMapper'),
					$c->query('UserManager'),
					$c->query('DatabaseBackend'),
					$c->query('SyncService'),
					$c->query('URLGenerator'),
					$c->query('Logger'),
					$c->query('Mailer'),
					$c->query('Defaults'),
					$c->query('L10N'),
					\OCP\Util::getDefaultEmailAddress('no-reply')
				);
			}
		);
		$container->registerService(
			'GroupBackend', function ($c) {
				return new GroupBackend(
				);
			}
		);
		$container->registerService(
			'AttributeMapper', function ($c) {
				return new AttributeMapper(
					$c->query('AppConfig'),
					$c->query('Request'),
					$c->query('Logger')
				);
			}
		);
		$container->registerService(
			'IdentityMapper', function($c) {
				return new IdentityMapper(
					$c->query('AppName'),
					$c->query('Logger'),
					$c->query('Db'),
					$c->query('UserManager')
				);
			}
		);
		$container->registerService(
			'LegacyIdentityMapper', function($c) {
				return new LegacyIdentityMapper(
					$c->query('AppName'),
					$c->query('Logger'),
					$c->query('Db'),
					$c->query('UserManager')
				);
			}
		);
		/**
		 * OC Server Services
		 */
		$container->registerService(
			'Db', function($c) {
				return $c->query('ServerContainer')->getDb();
			}
		);
		$container->registerService(
			'Logger', function ($c) {
				return $c->query('ServerContainer')->getLogger();
			}
		);
		$container->registerService(
			'Config', function ($c) {
				return $c->query('ServerContainer')->getConfig();
			}
		);
		$container->registerService(
			'AppConfig', function ($c) {
				return $c->query('ServerContainer')->getAppConfig();
			}
		);
		$container->registerService(
			'UserManager', function ($c) {
				return $c->query('ServerContainer')->getUserManager();
			}
		);
		$container->registerService(
			'GroupManager', function ($c) {
				return $c->query('ServerContainer')->getGroupManager();
			}
		);
		$container->registerService(
			'URLGenerator', function ($c) {
				return $c->query('ServerContainer')->getUrlGenerator();
			}
		);
		$container->registerService(
			'SecureRandom', function ($c) {
				return $c->query('ServerContainer')->getSecureRandom();
			}
		);
		$container->registerService(
			'UserSession', function ($c) {
				return $c->query('ServerContainer')->getUserSession();
			}
		);
		$container->registerService(
			'AccountMapper', function ($c) {
				return $c->query('ServerContainer')->getAccountMapper();
			}
		);
		$container->registerService(
			'Mailer', function ($c) {
				return $c->query('ServerContainer')->getMailer();
			}
		);
		$container->registerService(
			'L10N', function($c) {
				return $c->query('ServerContainer')->getL10N('settings');
			}
		);
		$container->registerService(
			'Defaults', function($c) {
				return new \OC_Defaults();
			}
		);
		$container->registerService(
			'SyncService', function ($c) {
				return new SyncService(
					$c->query('Config'),
					$c->query('Logger'),
					$c->query('AccountMapper')
				);
			}
		);
		$container->registerService(
			'DatabaseBackend', function ($c) {
				return new Database();
			}
		);
	}
}

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

use OCP\AppFramework\App;
use OCA\UserOpenIDC\UserBackend;
use OCA\UserOpenIDC\GroupBackend;
use OCA\UserOpenIDC\Hooks\UserHooks;
use OCA\UserOpenIDC\Controller\LoginController;
use OCA\UserOpenIDC\Attributes\AttributeMapper;

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
					$c->query('AttributeMapper')
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
					$c->query('UserManager'),
					$c->query('Logger')
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
		/**
		 * OC Server Services
		 */
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
	}
}

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

namespace OCA\UserOpenIDC\Tests\Integration;

use OCP\AppFramework\App;
use Test\TestCase;

/**
 * AppTest
 */
class AppTest extends TestCase {

	private $container;

	/**
	 * setUp
	 *
	 * @return null
	 */
	public function setUp() {
		parent::setUp();
		$app = new App('user_openidc');
		$this->container = $app->getContainer();
	}

	/**
	 * testAppInstalled
	 *
	 * @return null
	 */
	public function testAppInstalled() {
		$appManager = $this->container->query('OCP\App\IAppManager');
		$this->assertTrue($appManager->isInstalled('user_openidc'));
	}

}

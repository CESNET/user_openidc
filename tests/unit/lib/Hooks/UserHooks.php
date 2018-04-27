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

namespace OCA\UserOpenIDC\Tests\Unit\Hooks;

use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IUserManager;
use \Test\TestCase;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Hooks\UserHooks;

/**
 * Class UserHooksTest
 *
 * @package OCA\UserOpenIDC\Tests\Unit\Hooks
 */
class UserHooksTest extends TestCase {

	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var AttributeMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $attrMapper;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IUser | \PHPUnit_Framework_MockObject_MockObject */
	private $user;
	/** @var UserHooks | \PHPUnit_Framework_MockObject_MockObject */
	private $userHooks;

	/**
	 * @return null
	 */
	public function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->attrMapper = $this->createMock(AttributeMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getDisplayName')
			->willReturn('John Doe');
		$this->user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user0@nomail.com');
		$this->userHooks = new UserHooks(
			'user_openidc',
			$this->attrMapper,
			$this->userManager,
			$this->logger
		);
	}
	/**
	 * @return null
	 */
	public function testPostLoginHookWillUpdateDisplayNameIfChanged() {
		$expected = 'Joe Doe';
		$this->attrMapper->expects($this->once())
			->method('getDisplayName')
			->willReturn('Joe Doe');
		$this->user->expects($this->once())
			->method('setDisplayName')
			->with($expected);
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginHookWontUpdateDisplayNameIfNotChanged() {
		$this->attrMapper->expects($this->once())
			->method('getDisplayName')
			->willReturn($this->user->getDisplayName());
		$this->user->expects($this->never())
			->method('setDisplayName');
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginWontSetDisplayNameToNull() {
		$this->attrMapper->expects($this->once())
			->method('getDisplayName')
			->willReturn(null);
		$this->user->expects($this->never())->method('setDisplayName');
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginHookWillUpdateEmailIfChanged() {
		$expected = 'user0@mail.com';
		$this->attrMapper->expects($this->once())
			->method('getEMailAddress')
			->willReturn($expected);
		$this->user->expects($this->once())
			->method('setEMailAddress')
			->with($expected);
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginHookWontUpdateEmailIfNotChanged() {
		$this->attrMapper->expects($this->once())
			->method('getEMailAddress')
			->willReturn($this->user->getEMailAddress());
		$this->user->expects($this->never())
			->method('setEMailAddress');
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginHookWontSetEmailToNull() {
		$this->attrMapper->expects($this->once())
			->method('getEMailAddress')
			->willReturn(null);
		$this->user->expects($this->never())->method('setEMailAddress');
		$this->userHooks->postLoginHook($this->user);
	}
}

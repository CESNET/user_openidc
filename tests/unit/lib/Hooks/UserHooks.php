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

use \OC\User\Backend;
use \OC\User\Account;
use \OC\User\SyncService;
use \OC\User\AccountMapper;
use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IAppConfig;
use \OCP\IUserManager;
use \OCP\UserInterface;
use \Test\TestCase;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Hooks\UserHooks;
use \OCA\UserOpenIDC\Util;

/**
 * Class UserHooksTest
 *
 * @package OCA\UserOpenIDC\Tests\Unit\Hooks
 */
class UserHooksTest extends TestCase {

	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IAppConfig | PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IRequest | PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var AttributeMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $attrMapper;
	/** @var AccountMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $accountMapper;
	/** @var UserInterface | \PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var SyncService | \PHPUnit_Framework_MockObject_MockObject */
	private $syncService;
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
		$this->config = $this->createMock(IAppConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->request->expects($this->any())
			->method('getServerProtocol')
			->willReturn('https');
		$this->attrMapper = $this->createMock(AttributeMapper::class);
		$this->accMapper = $this->createMock(AccountMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userBackend = $this->createMock(UserInterface::class);
		$this->userBackend->expects($this->any())
			->method('implementsActions')
			->with(Backend::SET_DISPLAYNAME)
			->willReturn(true);
		$this->syncService = $this->createMock(SyncService::class);
		$this->user = $this->createMock(IUser::class);
		$account = $this->createMock(Account::class);
		$syncedAccount = $this->createMock(Account::class);
		$this->accMapper->expects($this->any())
			->method('getByUid')
			->willReturn($account);
		$this->syncService->expects($this->any())
			->method('syncAccount')
			->with($account, $this->userBackend)
			->willReturn($syncedAccount);
		$this->user->expects($this->any())
			->method('getDisplayName')
			->willReturn('John Doe');
		$this->user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user0@nomail.com');
	}
	/**
	 * @param string $autoupdate yes|no
	 *
	 * @return null
	 */
	public function constructUserHooks($autoupdate='yes') {
		$this->config->expects($this->any())
			->method('getValue')
			->with('user_openidc', 'backend_autoupdate', 'no')
			->willReturn($autoupdate);
		$this->userHooks = new UserHooks(
			'user_openidc',
			$this->config,
			$this->request,
			$this->attrMapper,
			$this->accMapper,
			$this->userManager,
			$this->userBackend,
			$this->syncService,
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
		$this->userBackend->expects($this->once())
			->method('setDisplayName');
		$this->constructUserHooks();
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
		$this->constructUserHooks();
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
		$this->constructUserHooks();
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
		$this->constructUserHooks();
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
		$this->constructUserHooks();
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
		$this->constructUserHooks();
		$this->userHooks->postLoginHook($this->user);
	}
	/**
	 * @return null
	 */
	public function testPostLoginWontUpdateAnythingWhenAutoupdateOff() {
		$this->attrMapper->method('getEMailAddress')->willReturn('never@called.com');
		$this->attrMapper->method('getDisplayName')->willReturn('Never Called');
		$this->user->expects($this->never())->method('setDisplayName');
		$this->user->expects($this->never())->method('setEMailAddress');
		$this->constructUserHooks('no');
		$this->userHooks->postLoginHook($this->user);
	}
}

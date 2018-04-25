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

namespace OCA\UserOpenIDC\Tests\Unit\Controller;

use \OC\User\Session;
use \OCP\IUser;
use \OCP\ILogger;
use \OCP\IRequest;
use \OCP\IURLGenerator;
use \Test\TestCase;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCA\UserOpenIDC\Controller\LoginController;
/**
 * Class LoginControllerTest
 *
 * @package OCA\UserOpenIDC\Tests\Unit\Controller
 */
class LoginControllerTest extends TestCase {

	/** @var LoginController */
	private $loginController;
	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var Session | \PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var string */
	private $indexPage;
	/** @var IUser | PHPUnit_Framework_MockObject_MockObject */
	private $user;

	/**
	 * @return null
	 */
	public function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->userSession = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()
			->getMock();
		$this->loginController = $this->getMockBuilder(LoginController::class)
			->setMethods(['getDefaultUrl'])
			->setConstructorArgs(
				[
					'user_openidc',
					$this->request,
					$this->urlGenerator,
					$this->userSession,
					$this->logger
				]
			)->getMock();
		$this->indexPage = 'defaultUrl';
		$this->loginController->expects($this->any())
			->method('getDefaultUrl')
			->willReturn($this->indexPage);
		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->atLeastOnce())
			->method('getUID')
			->will($this->returnValue('user0'));
	}
	/**
	 * @return null
	 */
	private function mockLoginSuccess() {
		$this->userSession->expects($this->once())
			->method('login')
			->with(null, null)
			->will($this->returnValue(true));
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->user));
		$this->userSession->expects($this->once())
			->method('createSessionToken')
			->with(
				$this->request,
				$this->user->getUID(),
				$this->user->getUID()
			);
		$this->userSession->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(true));
	}
	/**
	 * @return null
	 */
	private function mockLoginFailure() {
		$this->userSession->expects($this->once())
			->method('login')
			->with(null, null)
			->will($this->returnValue(false));
		$this->userSession->expects($this->never())
			->method('getUser');
		$this->userSession->expects($this->never())
			->method('createSessionToken')
			->with(
				$this->request,
				$this->user->getUID(),
				$this->user->getUID()
			);
		$this->userSession->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(false));
	}
	/**
	 * @param string $redirectUrl
	 *
	 * @return null
	 */
	private function mockGetAbsoluteUrl($redirectUrl) {
		$absoluteUrl = 'https://server1' . $redirectUrl;
		$this->urlGenerator->expects($this->atLeastOnce())
			->method('getAbsoluteURL')
			->will($this->returnValue($absoluteUrl));
		return $absoluteUrl;
	}
	/**
	 * @return null
	 */
	public function testSuccessfullLoginWithNoRedirectUrl() {
		$this->mockLoginSuccess();

		$expected = new RedirectResponse($this->indexPage);
		$actual = $this->loginController->tryLogin();
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testFailedLoginWithNoRedirectUrlWillShowForbidden() {
		$this->mockLoginFailure();

		$expected = new TemplateResponse('', '403', array(), 'guest');
		$actual = $this->loginController->tryLogin();
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testFailedLoginWithAnyRedirectUrlWillShowForbidden() {
		$this->mockLoginFailure();

		$redirectUrl = '/redirected/here';
		$expected = new TemplateResponse('', '403', array(), 'guest');
		$actual = $this->loginController->tryLogin($redirectUrl);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testSuccessfullLoginWithValidRedirectUrl() {
		$this->mockLoginSuccess();
		$redirectUrl = '/redirected/here';

		$expected = new RedirectResponse($this->mockGetAbsoluteUrl($redirectUrl));
		$actual = $this->loginController->tryLogin($redirectUrl);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testRedirectToInvalidLocationIsPrevented() {
		$this->mockLoginSuccess();
		$redirectUrl = ':user0@anotherdomain.com';

		$expected = new RedirectResponse($this->indexPage);
		$actual = $this->loginController->tryLogin($redirectUrl);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testRedirectToAnotherDomainIsPrevented() {
		$this->mockLoginSuccess();
		$redirectUrl = 'https://anotherdomain.com/redirecthere';

		$expected = new RedirectResponse($this->mockGetAbsoluteUrl($redirectUrl));
		$actual = $this->loginController->tryLogin($redirectUrl);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testRedirectLoopIsPrevented() {
		$this->mockLoginSuccess();

		$redirectUrl = '/apps/user_openidc/login';

		$expected = new RedirectResponse($this->indexPage);
		$actual = $this->loginController->tryLogin($redirectUrl);
		$this->assertEquals($expected, $actual);
	}
}

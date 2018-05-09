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

namespace OCA\UserOpenIDC\Tests\Unit;

use \OC\AppConfig;
use \OC\User\Backend;
use \OCP\ILogger;
use \OCP\IUserManager;
use \OCP\Security\ISecureRandom;
use \Test\TestCase;
use \OCA\UserOpenIDC\UserBackend;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;
use \OCA\UserOpenIDC\Db\IdentityMapper;
use \OCA\UserOpenIDC\Db\Legacy\LegacyIdentityMapper;

/**
 * Class UserBackendTest
 *
 * @package OCA\UserOpenIDC\Tests\Unit
 */
class UserBackendTest extends TestCase {

	/** @var AppConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $appConfig;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userMgr;
	/** @var ISecureRandom | \PHPUnit_Framework_MockObject_MockObject */
	private $secRandom;
	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var AttributeMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $attrMapper;
	/** @var UserBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $userBackend;
	/** @var IdentityMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $idMapper;
	/** @var LegacyIdentityMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $legacyIdMapper;

	/**
	 * @return null
	 */
	public function setUp() {
		parent::setUp();

		$this->appConfig = $this->createMock(AppConfig::class);
		$this->userMgr = $this->createMock(IUserManager::class);
		$this->secRandom = $this->createMock(ISecureRandom::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->attrMapper = $this->createMock(AttributeMapper::class);
		$this->secRandom->method('generate')
			->with(
				'30', ISecureRandom::CHAR_DIGITS
				. ISecureRandom::CHAR_LOWER
				. ISecureRandom::CHAR_UPPER
			)
			->willReturn('securePassw0rd');
	}
	/**
	 * @param string $mode mocked backend mode
	 *
	 * @return null
	 */
	private function constructUserBackend($mode='logon_only') {
		if (!$this->idMapper) {
			$this->idMapper = $this->createMock(IdentityMapper::class);
		}
		if (!$this->legacyIdMapper) {
			$this->legacyIdMapper = $this->createMock(LegacyIdentityMapper::class);
		}
		$this->appConfig->method('getValue')
			->with('user_openidc', 'backend_mode')
			->willReturn($mode);
		$this->userBackend = new UserBackend(
			'user_openidc',
			$this->appConfig,
			$this->userMgr,
			$this->secRandom,
			$this->logger,
			$this->attrMapper,
			$this->idMapper,
			$this->legacyIdMapper
		);
	}
	/**
	 * @return null
	 */
	public function mockUserWithIdMapping() {
		$this->idMapper = $this->createMock(IdentityMapper::class);

		$this->idMapper->expects($this->any())
			->method('getOcUserID')
			->with('user0@domain.com')
			->willReturn('user0@domain.com');
	}
	/**
	 * @return null
	 */
	public function mockUserWithoutIdMapping() {
		$this->idMapper = $this->createMock(IdentityMapper::class);
		$this->idMapper->expects($this->any())
			->method('getOcUserID')
			->with('user0@domain.com')
			->willReturn(false);
	}
	/**
	 * @return null
	 */
	public function mockUserWithoutLegacyIdMapping() {
		$this->legacyIdMapper = $this->createMock(LegacyIdentityMapper::class);
		$this->legacyIdMapper->expects($this->any())
			->method('getOcUid')
			->with('user0@domain.com')
			->willReturn(false);
	}
	/**
	 * @return null
	 */
	public function mockUserWithConvergentLegacyIdMappings() {
		$this->legacyIdMapper = $this->createMock(LegacyIdentityMapper::class);
		$this->legacyIdMapper->expects($this->any())
			->method('getOcUid')
			->will($this->returnValueMap(
				[
					['user0@domain.com', 'user1@domain.com'],
					['user1@domain.com', 'user1@domain.com'],
				]
			));
	}
	/**
	 * @return null
	 */
	public function mockUserWithNonConvergentLegacyIdMappings() {
		$this->legacyIdMapper = $this->createMock(LegacyIdentityMapper::class);
		$this->legacyIdMapper->expects($this->any())
			->method('getOcUid')
			->will($this->returnValueMap(
				[
					['user0@domain.com', 'user0@domain.com'],
					['user1@domain.com', 'user1@domain.com'],
				]
			));
	}
	/**
	 * @return null
	 */
	public function testProvidesBackendName() {
		$this->constructUserBackend();
		$expected = 'OpenIDC';
		$actual = $this->userBackend->getBackendName();
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testSupportsRequiredActionsOnly() {
		$this->constructUserBackend();
		$actions = Backend::CHECK_PASSWORD;
		$this->assertTrue($this->userBackend->implementsActions($actions));
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordFailsWhenBackendDisabled() {
		$this->attrMapper->expects($this->never())->method('getUserID');
		$this->constructUserBackend('inactive');
		$this->assertFalse($this->userBackend->checkPassword());
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordProvisionsUsersWhenInProvisioningMode() {
		$uid = $this->getUniqueID();
		$this->userMgr->expects($this->any())
			->method('userExists')
			->willReturn(false);
		$this->userMgr->expects($this->once())
			->method('createUser')
			->with($uid, 'securePassw0rd');
		$this->attrMapper->expects($this->once())
			->method('getUserID')
			->willReturn($uid);
		$this->assertFalse($this->userMgr->userExists($uid));
		$this->constructUserBackend('provisioning');

		$actual = $this->userBackend->checkPassword();
		$this->assertEquals($uid, $actual);
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordDoesntProvisionMissingUsersWhenLogonOnly() {
		$uid = $this->getUniqueID();
		$this->userMgr->expects($this->any())
			->method('userExists')
			->willReturn(false);
		$this->userMgr->expects($this->never())->method('createUser');
		$this->attrMapper->expects($this->once())
			->method('getUserID')
			->willReturn($uid);
		$this->assertFalse($this->userMgr->userExists($uid));
		$this->constructUserBackend('logon_only');

		$actual = $this->userBackend->checkPassword();
		$this->assertFalse($actual);
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordWithoutSubFails() {
		$this->attrMapper->expects($this->once())
			->method('getUserID')
			->willReturn(null);
		$this->constructUserBackend();
		$this->assertFalse($this->userBackend->checkPassword());
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordWithSubOfExistingUserSucceeds() {
		$this->attrMapper->expects($this->once())
			->method('getUserID')
			->willReturn('user0@domain.com');
		$this->userMgr->expects($this->any())
			->method('userExists')
			->willReturn(true);
		$this->constructUserBackend();

		$expected = 'user0@domain.com';
		$actual = $this->userBackend->checkPassword();
		$this->assertEquals($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testCheckClaimsFailsWhenRequiredClaimIsMissing() {
		$this->attrMapper->expects($this->once())
			->method('getRequiredClaims')
			->willReturn(array('claim_userid', 'claim_email'));
		$claimMap = [
			['claim_userid', 'user0@domain.com'],
			['claim_email', null]
		];
		$this->attrMapper->expects($this->atLeastOnce())
			->method('getClaimValue')
			->will($this->returnValueMap($claimMap));
		$this->constructUserBackend();
		$this->assertFalse($this->userBackend->checkClaims());
	}
	/**
	 * @return null
	 */
	public function testCheckClaimsSucceedsWhenRequirementsFulfilled() {
		$this->attrMapper->expects($this->once())
			->method('getRequiredClaims')
			->willReturn(array('claim_userid', 'claim_email'));
		$claimMap = [
			['claim_userid', 'user0@domain.com'],
			['claim_email', 'user0@mail.com']
		];
		$this->attrMapper->expects($this->atLeastOnce())
			->method('getClaimValue')
			->will($this->returnValueMap($claimMap));
		$this->constructUserBackend();
		$this->assertTrue($this->userBackend->checkClaims());
	}
	/**
	 * @return null
	 */
	public function testCheckPasswordFailsWhenRequirementsNotSatisfied() {
		$this->attrMapper->expects($this->once())
			->method('getRequiredClaims')
			->willReturn(array('claim_userid', 'claim_email'));
		$claimMap = [
			['claim_userid', 'user0@domain.com'],
			['claim_email', null]
		];
		$this->attrMapper->expects($this->atLeastOnce())
			->method('getClaimValue')
			->will($this->returnValueMap($claimMap));
		$this->constructUserBackend();
		$this->assertFalse($this->userBackend->checkPassword());
	}
	/**
	 * @return null
	 */
	public function testResolveUserIDReturnsOCUidOfExistingIdentity() {
		$this->mockUserWithIdMapping();
		$this->constructUserBackend();
		$expected = 'user0@domain.com';
		$actual = $this->userBackend->resolveUserID($expected, [$expected]);
		$this->assertSame($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testResolveUserIDReturnsOCUidOfExistingLegacyIdentity() {
		$this->mockUserWithoutIdMapping();
		$this->mockUserWithConvergentLegacyIdMappings();
		$this->constructUserBackend();
		$expected = 'user1@domain.com';
		$actual = $this->userBackend->resolveUserID(
			'user0@domain.com', ['user0@domain.com', $expected]);
		$this->assertSame($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testResolveUserIDCreatesMissingIdMapping() {
		$this->mockUserWithoutIdMapping();
		$this->mockUserWithoutLegacyIdMapping();
		$this->constructUserBackend();
		$this->idMapper->expects($this->once())
			->method('addIdentity')
			->with(
				'user0@domain.com', 'user0@domain.com', '', 0
			);
		$this->userBackend->resolveUserID(
			'user0@domain.com', ['user0@domain.com', 'user1@domain.com']
		);
	}
	/**
	 * @return null
	 */
	public function testResolveUserIDCreatesMissingIdMappingAndMarksLegacyMappingMigrated() {
		$this->mockUserWithoutIdMapping();
		$this->mockUserWithConvergentLegacyIdMappings();
		$this->constructUserBackend();
		$this->idMapper->expects($this->once())
			->method('addIdentity')
			->with(
				'user0@domain.com', 'user1@domain.com', '', 0
			);
		$this->legacyIdMapper->expects($this->once())
			->method('setMigrated')
			->with('user1@domain.com');
		$this->userBackend->resolveUserID(
			'user0@domain.com', ['user0@domain.com', 'user1@domain.com']
		);
	}
}

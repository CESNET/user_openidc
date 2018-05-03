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

namespace OCA\UserOpenIDC\Tests\Unit\Attributes;

use \OCP\IRequest;
use \OCP\ILogger;
use \OC\AppConfig;
use \Test\TestCase;
use \OCA\UserOpenIDC\Attributes\AttributeMapper;

/**
 * Class AttributeMapperTest
 *
 * @package OCA\UserOpenIDC\Tests\Unit\Attributes
 */
class AttributeMapperTest extends TestCase {

	/** @var AppConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $appConfig;
	/** @var IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var ILogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var AttributeMapper | \PHPUnit_Framework_MockObject_MockObject */
	private $attrMapper;

	/**
	 * @return null
	 */
	public function setUp() {
		$this->appConfig = $this->createMock(AppConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->attrMapper = new AttributeMapper(
			$this->appConfig,
			$this->request,
			$this->logger
		);
		$configMap = [
			['user_openidc', 'claim_prefix', 'OIDC_CLAIM_', 'USERINFO_'],
			['user_openidc', 'claim_userid', null, 'sub'],
			['user_openidc', 'claim_displayname', null, 'name'],
			['user_openidc', 'claim_email', null, 'email'],
			['user_openidc', 'claim_altuids', null, 'altuids'],
			['user_openidc', 'claim_groups', null, 'USERINFO_groups'],
			['user_openidc', 'backend_required_claims', 'claim_userid', 'claim_userid']
		];
		$this->appConfig->method('getValue')
			->will($this->returnValueMap($configMap));
	}

	/**
	 * @return array
	 */
	public function providesOidcClaimNames() {
		return [
			['USERINFO_sub', 'claim_userid'],
			['USERINFO_name', 'claim_displayname'],
			['USERINFO_email', 'claim_email'],
			['USERINFO_altuids', 'claim_altuids'],
			'already prefixed claim' => ['USERINFO_groups', 'claim_groups'],
			'nonexistent attribute' => [null, 'bob']
		];
	}
	/**
	 * @return array
	 */
	public function providesOidcSubClaims() {
		return [
			'sub missing' => [null, []],
			'valid sub' => ['user0@domain.com', ['USERINFO_sub' => 'user0@domain.com']],
			'invalid sub' => [null, ['USERINFO_sub' => ' ;<>bob$;@evil.corp']]
		];
	}
	/**
	 * @return array
	 */
	public function providesOidcDNClaims() {
		return [
			'DN missing' => [null, []],
			'valid DN' => ['John Doe', ['USERINFO_name' => 'John Doe']],
			'invalid DN' => [null, ['USERINFO_name' => ';<>_bob$; @evil.corp']]
		];
	}
	/**
	 * @return array
	 */
	public function providesOidcEmailClaims() {
		return [
			'e-mail missing' => [null, []],
			'valid e-mail' => ['user0@mail.com', ['USERINFO_email' => 'user0@mail.com']],
			'invalid e-mail' => [null, ['USERINFO_email' => ' ;<>_bob$;@evil.corp']]
		];
	}
	/**
	 * @return array
	 */
	public function providesOidcAltUidsClaims() {
		return [
			'empty altuids' => [null, ['USERINFO_altuids' => '']],
			'single valid altuid' => [
				['altuser@domain.com'],
				['USERINFO_altuids' => 'altuser@domain.com']
			],
			'multiple valid altuids' => [
				['altuser0@domain.com', 'altuser1@domain.com'],
				['USERINFO_altuids' => 'altuser0@domain.com,altuser1@domain.com']
			],
			'invalid altuid' => [
				null,
				['USERINFO_altuids' => ' ;<>bob$;@evil.corp,altuser0@domain.com']
			]
		];
	}
	/**
	 * @return array
	 */
	public function providesOidcRequiredClaims() {
		return [
			'no required claims' => [['claim_userid'], ''], //sub is always required
			'require sub only' => [['claim_userid'], 'claim_userid'],
			'multiple claims required' => [
				['claim_userid', 'claim_email', 'claim_displayname'],
				'claim_userid,claim_email,claim_displayname'],
			'claim_userid missing' => [
				['claim_email', 'claim_userid'], 'claim_email']
		];
	}
	/**
	 * @return null
	 */
	public function testGetClaimPrefix() {
		$expected = 'USERINFO_';
		$actual = $this->attrMapper->getClaimPrefix();
		$this->assertSame($expected, $actual);
	}

	/**
	 * @dataProvider providesOidcClaimNames
	 * @param mixed $expected expected result
	 * @param array $claimName Claims names array
	 *
	 * @return null
	 */
	public function testGetClaimName($expected, $claimName) {
		$actual = $this->attrMapper->getClaimName($claimName);
		$this->assertSame($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testGetExistingClaimValue() {
		$expected = 'user0@domain0';
		$this->request->server['USERINFO_sub'] = $expected;
		$actual = $this->attrMapper->getClaimValue('claim_userid');
		$this->assertSame($expected, $actual);
	}
	/**
	 * @return null
	 */
	public function testGetNonexistentClaimValue() {
		$actual = $this->attrMapper->getClaimValue('foo');
		$this->assertNull($actual);
	}
	/**
	 * @dataProvider providesOidcSubClaims
	 * @param mixed $expected expected result
	 * @param array $oidcClaims Claims array to be set in $_SERVER env
	 *
	 * @return null
	 */
	public function testGetUserID($expected, $oidcClaims) {
		$this->request->server = $oidcClaims;
		$actual = $this->attrMapper->getUserID();
		$this->assertSame($expected, $actual);
	}
	/**
	 * @dataProvider providesOidcAltUidsClaims
	 * @param mixed $expected expected result
	 * @param array $oidcClaims Claims array to be set in $_SERVER env
	 *
	 * @return null
	 */
	public function testGetAltUserIDs($expected, $oidcClaims) {
		$this->request->server = $oidcClaims;
		$actual = $this->attrMapper->getAltUserIDs();
		$this->assertSame($expected, $actual);
	}
	/**
	 * @dataProvider providesOidcDNClaims
	 * @param mixed $expected expected result
	 * @param array $oidcClaims Claims array to be set in $_SERVER env
	 *
	 * @return null
	 */
	public function testGetDisplayName($expected, $oidcClaims) {
		$this->request->server = $oidcClaims;
		$actual = $this->attrMapper->getDisplayName();
		$this->assertSame($expected, $actual);
	}
	/**
	 * @dataProvider providesOidcEmailClaims
	 * @param mixed $expected expected result
	 * @param array $oidcClaims Claims array to be set in $_SERVER env
	 *
	 * @return null
	 */
	public function testGetEMailAddress($expected, $oidcClaims) {
		$this->request->server = $oidcClaims;
		$actual = $this->attrMapper->getEMailAddress();
		$this->assertSame($expected, $actual);
	}
	/**
	 * @dataProvider providesOidcRequiredClaims
	 * @param mixed $expected expected result
	 * @param array $oidcClaims Claims array to be set in $_SERVER env
	 *
	 * @return null
	 */
	public function testGetRequiredClaims($expected, $oidcClaims) {
		$this->appConfig = $this->createMock(AppConfig::class);
		$this->appConfig->method('getValue')
			->with('user_openidc', 'backend_required_claims', 'claim_userid')
			->will($this->returnValue($oidcClaims));
		$this->attrMapper = new AttributeMapper(
			$this->appConfig,
			$this->request,
			$this->logger
		);
		$actual = $this->attrMapper->getRequiredClaims();
		$this->assertSame($expected, $actual);
	}
}

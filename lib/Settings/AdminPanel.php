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

namespace OCA\UserOpenIDC\Settings;

use \OC\AppConfig;
use OCP\Settings\ISettings;
use OCP\Template;
use OCP\IRequest;
use OCA\UserOpenIDC\Attributes\AttributeMapper;

/**
 * AdminPanel class registered to admin
 * settings section.
 */
class AdminPanel implements ISettings {

	private $appName;
	/** @var IRequest */
	private $request;
	/** @var AppConfig */
	private $config;
	/** @var AttributeMapper */
	private $attrMapper;

	/**
	 * AdminPanel constructor.
	 *
	 * @param AppConfig $config
	 * @param IRequest $request
	 * @param AttributeMapper $attrMapper
	 */
	public function __construct(AppConfig $config, IRequest $request,
		AttributeMapper $attrMapper
	) {
		$this->appName = 'user_openidc';
		$this->config = $config;
		$this->request = $request;
		$this->attrMapper = $attrMapper;
	}
	/**
	 * Priority of the panel in the section
	 *
	 * @return int section priority
	 */
	public function getPriority() {
		return 50;
	}
	/**
	 * Section string id
	 *
	 * @return string section string ID
	 */
	public function getSectionID() {
		return 'authentication';
	}
	/**
	 * AdminPanel content template
	 *
	 * @return Template panel content
	 */
	public function getPanel() {
		$backendMode = $this->config->getValue($this->appName, 'backend_mode', 'inactive');
		$backendAutoupdate = $this->config->getValue($this->appName, 'backend_autoupdate', 'no');
		$oidcPrefix = $this->attrMapper->getClaimPrefix();
		$oidcClaims = array_filter(
			$this->request->server,
			function ($key) use ($oidcPrefix) {
				return substr($key, 0, strlen($oidcPrefix)) === $oidcPrefix;
			},
			ARRAY_FILTER_USE_KEY
		);
		$claimUserid = $this->attrMapper->getClaimName('claim_userid');
		$claimDn = $this->attrMapper->getClaimName('claim_displayname');
		$claimEmail = $this->attrMapper->getClaimName('claim_email');
		$requiredClaims = $this->attrMapper->getRequiredClaims();

		$t = new Template('user_openidc', 'settings-admin');
		$t->assign('backend_mode', $backendMode);
		$t->assign('backend_autoupdate', $backendAutoupdate);
		$t->assign('mapping_prefix', $oidcPrefix);
		$t->assign('mapping_userid', $claimUserid);
		$t->assign('mapping_dn', $claimDn);
		$t->assign('mapping_email', $claimEmail);
		$t->assign('oidc_claims', $oidcClaims);
		$t->assign('required_claims', $requiredClaims);
		return $t;
	}
}

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

namespace OCA\UserOpenIDC\Command;

use \OCA\UserOpenIDC\UserBackend;
use \OCA\UserOpenIDC\AppInfo\Application;

use \OC\User\Account;
use \OC\User\AccountMapper;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

class EnableLogin extends Command {

	/**
	 * @param AccountMapper $accountMapper
	 */
	public function __construct(AccountMapper $accountMapper) {
		parent::__construct();
		$this->accMapper = $accountMapper;
	}

	protected function configure() {
		$this->setName('user_openidc:enablelogin')
			->setDescription('Enables user to log in using the OIDC backend')
			->addOption(
				'userid', 'u',
				InputOption::VALUE_REQUIRED,
				'user account UID to be enabled for OIDC'
			)
			->addOption(
				'all', 'a',
				InputOption::VALUE_NONE,
				'Enable OIDC login for all existing accounts'
			);
	}
	/**
	 * Changes Account's user backend to OIDC, so it is
	 * possible to log in using this backend.
	 *
	 * @param Account $account User Account
	 */
	private function enableAccountOIDCLogin($account) {
		$account->setBackend(UserBackend::class);
		$this->accMapper->update($account);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('all')) {
			$this->accMapper->callForAllUsers(
				function($account) {
					$this->enableAccountOIDCLogin($account);
				},
				null, false
			);
		} else {
			$uid = $input->getOption('userid');
			$output->writeln('Enabling OIDC login for user UID: '. $uid);
			try {
				$acc = $this->accMapper->getByUid($uid);
			} catch (Exception $e) {
				$output->writeln("<error>User Account with this uid doesn't exist.</error>");
				return;
			}
			$this->enableAccountOIDCLogin($acc);
			$output->writeln("<info>Account backend successfully switched to " . UserBackend::class . "</info>");
		}
		$output->writeln('All Done.');
	}

}

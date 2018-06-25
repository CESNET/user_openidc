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

script('user_openidc', 'settings');
style('user_openidc', 'main');
style('user_openidc', 'settings-admin');
?>
<div class="section" id="user_openidc">
	<h2 class="app-name inlineblock"><?php p($l->t('User OpenID Connect')); ?></h2>
	<a target="_blank" rel="noreferrer" class="icon-info"
	   title="<?php p($l->t('Open documentation'));?>"
	   href="<?php p(link_to_docs('admin-openidc')); ?>"></a>

	<div id="user-openidc-save-indicator" class="msg success inlineblock" style="display: none;">Saved</div>

	<div id="user-openidc-settings">
		<div id="user-openidc-choose-mode">
			<br/>
			<?php p($l->t('Please choose the mode of backend operation. Logon only mode depends on a separate backend (e.g. LDAP, AD, database) for user provisioning.')) ?>
			<br/>
			<label for="user-openidc-backend-mode">
				<?php p($l->t('Backend Mode')); ?>
			</label>
			<select name='backend_mode' class="user-openidc-setting"
				id='user-openidc-backend-mode'>
				<option value="inactive"
					<?php if ($_['backend_mode'] === 'inactive') : ?>
						selected='selected'
					<?php endif; ?>>
					<?php p($l->t('Inactive')); ?>
				</option>
				<option value="logon_only"
					<?php if ($_['backend_mode'] === 'logon_only') : ?>
						selected='selected'
					<?php endif; ?>>
					<?php p($l->t('Logon only')); ?>
				</option>
				<option value="provisioning"
					<?php if ($_['backend_mode'] === 'provisioning') : ?>
						selected='selected'
					<?php endif; ?>>
					<?php p($l->t('User Provisioning')); ?>
				</option>
			</select>
		</div>

		<div class="warning" id="user-openidc-warning-admin-user">
			<?php p($l->t('Make sure to configure an administrative user that can access the instance via OIDC Provider before logging out. Logging-in with your regular account won\'t be possible anymore.')); ?>
		</div>

		<div id="user-openidc-claim-mapping">
			<h3><?php p($l->t('Attribute mapping configuration')) ?></h3>
			<table class="grid">
			<tbody>
			<tr>
				<td>
				<label for="user-openidc-prefix">
					<?php p($l->t('Claim prefix')); ?>
				</label>
				</td><td>
				<input type="text" name="claim_prefix"
					class="user-openidc-setting"
					id="user-openidc-prefix"
					value="<?php p($_['mapping_prefix']); ?>"
					placeholder="OIDC_CLAIM_">
				</input>
				<span class="info">
					<?php p($l->t('Reload the page after changing this setting.')); ?>
				</span>
				</td>
			</tr><tr>
				<td>
				<label for="user-openidc-userid">
					<?php p($l->t('Username')); ?>
				</label>
				</td><td>
				<select name='claim_userid' id='user-openidc-userid'
					class="user-openidc-setting">
					<?php foreach ($_['oidc_claims'] as $svar => $svalue): ?>
						<option value="<?php p($svar); ?>"
						<?php if ($svar === $_['mapping_userid']) : ?>
							selected='selected'
						<?php endif; ?>>
							<?php p($svar . ' (' . $svalue . ')'); ?>
						</option>
					<?php endforeach;?>
				</select>
				<em><?php p($l->t('Required')); ?></em>
				</td>
			</tr><tr>
				<td>
				<label for="user-openidc-altuids">
					<?php p($l->t('Alternative usernames')); ?>
				</label>
				</td><td>
				<select name="claim_altuids" id="user-openidc-altuids"
					class="user-openidc-setting">
					<?php foreach ($_['oidc_claims'] as $svar => $svalue): ?>
						<option value="<?php p($svar); ?>"
						<?php if ($svar === $_['mapping_altuids']): ?>
							selected='selected'
						<?php endif; ?>>
						<?php p($svar . ' (' . $svalue . ')'); ?>
						</option>
					<?php endforeach;?>
				</select>
				<input type="checkbox" class="checkbox user-openidc-required"
					name="claim_altuids"
					id="user-openidc-altuids_required" value="0"
			<?php if (in_array('claim_altuids', $_['required_claims'])) {
					print_unescaped('checked="checked"');
			} ?> >
				</input>
				<label for="user-openidc-altuids_required">
					<em><?php p($l->t('Required')); ?></em>
				</label>
				</td>
			</tr><tr>
				<td>
				<label for="user-openidc-dn">
					<?php p($l->t('Full name')); ?>
				</label>
				</td><td>
				<select name="claim_displayname" id="user-openidc-dn"
					class="user-openidc-setting">
					<?php foreach ($_['oidc_claims'] as $svar => $svalue): ?>
						<option value="<?php p($svar); ?>"
						<?php if ($svar === $_['mapping_dn']) : ?>
							selected='selected'
						<?php endif; ?>>
							<?php p($svar . ' (' . $svalue . ')'); ?>
						</option>
					<?php endforeach;?>
				</select>
				<input type="checkbox" class="checkbox user-openidc-required"
					name="claim_displayname"
					id="user-openidc-dn_required" value="0"
<?php if (in_array('claim_displayname', $_['required_claims'])) {
					print_unescaped('checked="checked"');
} ?> >
				</input>
				<label for="user-openidc-dn_required">
					<em><?php p($l->t('Required')); ?></em>
				</label>
				</td>
			</tr><tr>
				<td>
				<label for="user-openidc-email">
					<?php p($l->t('E-mail')); ?>
				</label>
				</td><td>
				<select name="claim_email" id="user-openidc-email"
					class="user-openidc-setting">
				<?php foreach ($_['oidc_claims'] as $svar => $svalue) : ?>
					<option value="<?php p($svar); ?>"
					<?php if ($svar === $_['mapping_email']) : ?>
						selected='selected'
					<?php endif; ?>>
					<?php p($svar . ' (' . $svalue . ')'); ?>
					</option>
				<?php endforeach;?>
				</select>
				<input type="checkbox" class="checkbox user-openidc-required"
					name="claim_email"
					id="user-openidc-email_required" value="0"
<?php if (in_array('claim_email', $_['required_claims'])) {
					print_unescaped('checked="checked"');
} ?> >
				</input>
				<label for="user-openidc-email_required">
					<em><?php p($l->t('Required')); ?></em>
				</label>
				</td>
			</tr>
			</tr><tr>
				<td>
				<label for="user-openidc-eligible">
					<?php p($l->t('Is Eligible')); ?>
				</label>
				</td><td>
				<select name="claim_eligible" id="user-openidc-eligible"
					class="user-openidc-setting">
				<?php foreach ($_['oidc_claims'] as $svar => $svalue) : ?>
					<option value="<?php p($svar); ?>"
					<?php if ($svar === $_['mapping_eligible']) : ?>
						selected='selected'
					<?php endif; ?>>
					<?php p($svar . ' (' . $svalue . ')'); ?>
					</option>
				<?php endforeach;?>
				</select>
				<input type="checkbox" class="checkbox user-openidc-required"
					name="claim_eligible"
					id="user-openidc-eligible_required" value="0"
	<?php if (in_array('claim_eligible', $_['required_claims'])) {
					print_unescaped('checked="checked"');
	} ?> >
				</input>
				<label for="user-openidc-eligible_required">
					<em><?php p($l->t('Required')); ?></em>
				</label>
				</td>
			</tr>
			</tbody>
			</table>
		</div>


		<div id="user-openidc-backend">
			<h3><?php p($l->t('OpenID Connect backend configuration')) ?></h3>
			<table>
			<tbody>
			<tr><td>
			<input type="checkbox" class="checkbox user-openidc-setting"
				name="backend_autoupdate" id="user-openidc-autoupdate" value="1"
				<?php if ($_['backend_autoupdate'] === 'yes')
					print_unescaped('checked="checked"'); ?> >
			</input>
			<label for="user-openidc-autoupdate">
				<?php p($l->t('Update user information on login')); ?>
			</label>
			</td></tr><tr><td>
			<input type="checkbox" class="checkbox user-openidc-setting"
				name="backend_stripdomain" id="user-openidc-stripdomain" value="1"
				<?php if ($_['backend_stripdomain'] === 'yes')
					print_unescaped('checked="checked"'); ?> >
			</input>
			<label for="user-openidc-stripdomain">
				<?php p($l->t('Strip domain part of username')); ?>
			</label>
			</td></tr>
			</tbody>
			</table>
		</div>
	</div>
</div>

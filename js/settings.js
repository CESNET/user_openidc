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
(function (OCA) {
    "use strict";

    OCA.UserOpenIDC = OCA.UserOpenIDC || {};

    /**
     * @namespace OCA.UserOpenIDC.Admin
     */
    OCA.UserOpenIDC.Admin = {
        validateSetting: function (settingName) {
                var validSettings = [
                        'claim_userid', 'claim_email', 'claim_displayname',
                        'claim_prefix', 'claim_altuids', 'backend_autoupdate',
                        'backend_stripdomain', 'backend_mode'
                ];
                if ($.inArray(settingName, validSettings) > -1) {
                        return true;
                } else {
                        return false;
                }
        },
        setOidcSetting: function (name, value) {
                OC.msg.startSaving('#user-openidc-save-indicator');
                if (OCA.UserOpenIDC.Admin.validateSetting(name)) {
                        OC.AppConfig.setValue('user_openidc', name, value);
                        OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'success', data: {message: t('user_openidc', 'Saved')}});
                } else {
                        OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'error', data: {message: t('user_openidc', 'Unsupported setting')}});
                }
        },
        setOidcClaimRequired: function (name) {
                OC.msg.startSaving('#user-openidc-save-indicator');
                $('#user-openidc-save-indicator').data('save-set-claim-required', name);
                var cRequired = OC.AppConfig.getValue('user_openidc', 'backend_required_claims', 'claim_userid', function (value) {
                        var cRequiredArray = value.split(',');
                        var claimName = $('#user-openidc-save-indicator').data('save-set-claim-required');
                        if ($.inArray(claimName, cRequiredArray) === -1 && OCA.UserOpenIDC.Admin.validateSetting(claimName)) {
                                cRequiredArray.push(claimName);
                                OC.AppConfig.setValue('user_openidc', 'backend_required_claims', cRequiredArray.join());
                                $('#user-openidc-save-indicator').removeData('save-set-claim-required');
                                OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'success', data: {message: t('user_openidc', 'Saved')}});
                        } else {
                                OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'error', data: {message: t('user_openidc', 'Unsupported claim')}});
                        }
                });
        },
        unsetOidcClaimRequired: function (name) {
                OC.msg.startSaving('#user-openidc-save-indicator');
                $('#user-openidc-save-indicator').data('save-unset-claim-required', name);
                var cRequired = OC.AppConfig.getValue('user_openidc', 'backend_required_claims', 'claim_userid', function (value) {
                        var cRequiredArray = value.split(',');
                        var claimName = $('#user-openidc-save-indicator').data('save-unset-claim-required');
                        if ($.inArray(claimName, cRequiredArray) !== -1 && OCA.UserOpenIDC.Admin.validateSetting(claimName)) {
                                cRequiredArray.splice($.inArray(claimName, cRequiredArray),1);
                                OC.AppConfig.setValue('user_openidc', 'backend_required_claims', cRequiredArray.join());
                                $('#user-openidc-save-indicator').removeData('save-unset-claim-required');
                                OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'success', data: {message: t('user_openidc', 'Saved')}});
                        } else {
                                OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'error', data: {message: t('user_openidc', 'Unsupported claim')}});
                        }
                });
        }
    }
})(OCA);

$(document).ready(function () {
        "use strict";

        $('input[type="checkbox"].user-openidc-required').change(function(e) {
                e.preventDefault();
                var el = $(this);
                var name = el.attr('name');
                if (el.is(':checked')) {
                        OCA.UserOpenIDC.Admin.setOidcClaimRequired(name);
                } else {
                        OCA.UserOpenIDC.Admin.unsetOidcClaimRequired(name);
                }

        });

        $('.user-openidc-setting').change(function(e) {
                e.preventDefault();
                var el = $(this);
                var name = $(this).attr('name');
                var type = $(this).attr('type');
                if (type === 'checkbox') {
                        if (el.is(':checked')) {
                                OCA.UserOpenIDC.Admin.setOidcSetting(name, 'yes');
                                return false;
                        } else {
                                OCA.UserOpenIDC.Admin.setOidcSetting(name, 'no');
                                return false;
                        }
                } else {
                        $.when(el.focusout()).then(function() {
                                OCA.UserOpenIDC.Admin.setOidcSetting(name, $(this).val());
                                return false;
                        });
                        if (e.keyCode === 13) {
                                OCA.UserOpenIDC.Admin.setOidcSetting(name, $(this).val());
                                return false;
                        }
                }
        });
});

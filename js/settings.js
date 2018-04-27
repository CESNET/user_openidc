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
        validateClaim: function (claimName) {
                var validClaims = ['claim_userid', 'claim_email', 'claim_displayname', 'claim_prefix'];
                if ($.inArray(claimName, validClaims) > -1) {
                        return true;
                } else {
                        return false;
                }
        },
        setOidcClaimMapping: function (name, value) {
                OC.msg.startSaving('#user-openidc-save-indicator');
                if (OCA.UserOpenIDC.Admin.validateClaim(name)) {
                        OC.AppConfig.setValue('user_openidc', name, value);
                        OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'success', data: {message: t('user_openidc', 'Saved')}});
                } else {
                        OC.msg.finishedSaving('#user-openidc-save-indicator', {status: 'error', data: {message: t('user_openidc', 'Unsupported claim')}});
                }
        },
        setOidcClaimRequired: function (name) {
                OC.msg.startSaving('#user-openidc-save-indicator');
                $('#user-openidc-save-indicator').data('save-set-claim-required', name);
                var cRequired = OC.AppConfig.getValue('user_openidc', 'backend_required_claims', 'claim_userid', function (value) {
                        var cRequiredArray = value.split(',');
                        var claimName = $('#user-openidc-save-indicator').data('save-set-claim-required');
                        if ($.inArray(claimName, cRequiredArray) === -1 && OCA.UserOpenIDC.Admin.validateClaim(claimName)) {
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
                        if ($.inArray(claimName, cRequiredArray) !== -1 && OCA.UserOpenIDC.Admin.validateClaim(claimName)) {
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
                var el = $(this);
                var name = el.attr('name');
                if (el.is(':checked')) {
                        OCA.UserOpenIDC.Admin.setOidcClaimRequired(name);
                } else {
                        OCA.UserOpenIDC.Admin.unsetOidcClaimRequired(name);
                }

        });
        $('.user-openidc-setting').change(function(e) {
                var el = $(this);
                $.when(el.focusout()).then(function() {
                        var name = $(this).attr('name');
                        OCA.UserOpenIDC.Admin.setOidcClaimMapping(name, $(this).val());
                });
                if (e.keyCode === 13) {
                        var name = $(this).attr('name');
                        OCA.UserOpenIDC.Admin.setOidcClaimMapping(name, $(this).val());
                }
        });
});

*Please note, that this application is still under development and it is not recommended
for production use yet.*

# user_openidc
User backend app providing OpenID Connect user authentication to ownCloud.

This app utilizes an OpenID Connect Resource Provider (client, RP) and authenticates
users against a configured OpenID Provider (OP). It is capable of
autoprovisioning of the users based on OpenID claims received from the OP.

# Getting started

This application relies on a 3rd-party module for handling of the communication
between the RP and OP. For Apache webserver, we recommend installing the [mod_auth_openidc](https://github.com/zmartzone/mod_auth_openidc)
module.

The following Apache siteconfig settings will configure a special Location for handling
user logins using the OIDC backend, the required special OIDC callback URI and OIDC coverage of
Admin settings. Other locations are then covered by the ownCloud session management. This
allows for multiple authentication methods to still be possible on your ownCloud
instance (e.g. OAuth2 for sync clients, Apache baseauth). Please refer to the [wiki](https://github.com/zmartzone/mod_auth_openidc/wiki)
for a full module configuration documentation.
```
OIDCRedirectURI https://oidc.client.com/oidc_callback
OIDCProviderMetadataURL https://oidc.provider.com/.well-known/openid-configuration
OIDCScope "openid profile email"
OIDCPassClaimsAs environment
OIDCClientID <clientID>
OIDCClientSecret <clientSecret>
OIDCClientName ownCloud
OIDCCryptoPassphrase <passphrase>

# Special callback Location handled by mod_auth_openidc
<Location /oidc_callback>
        AuthType openid-connect
        Require valid-user
</Location>

# This location handles creation of ownCloud user sessions
<Location /index.php/apps/user_openidc/login>
        AuthType openid-connect
	Require valid-user
</Location>

# This is needed for OIDC claims to be visible in
# the Admin configuration of attribute mappings
<Location /index.php/settings/admin>
        AuthType openid-connect
        Require valid-user
</Location>
```

# Admin configuration

Settings for this app can be found on the _Admin_ page under the _User Authentication_ section.
OIDC mapping to ownCloud and user backend settings could be configured from here.

## User Backend configuration

* **Backend mode** - operational mode. The following options are possible:
        * inactive - completely disables authentication through this backend
        * Logon only - this mode means that only existing ownCloud accounts can log in.
                        Assumes that user provisioning is done by another method (e.g. manual, LDAP, AD,...).
        * Provisioning - this mode autoprovisions new user accounts from provided OIDC claims on logon when necessary.
* **Update user information on login** - if enabled, an user account will be updated with provided information (such as e-mail address, display name,...) when the user logs in using this app.

## Mapping configuration

* **Claim prefix** - prefix for all claims provided by mod_auth_openidc (see _OIDCClaimPrefix_ setting).
* **Username** - claim to be used as ownCloud Account ID (username).
* **Alternative usernames** - a comma separated list of known alternative usernames of the user.
* **Full Name** - claim to be used for User display name.
* **Email** - claim to be used as Account contact e-mail address.
* **Required (checkbox)** - when checked, users must provide this claim in order to be logged in successfully.

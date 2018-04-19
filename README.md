Garlic User bundle
=====================
This bundle helps create Users service based on Garlic framework

### User bundle based on FOSUserBundle

### Installation

#### 1. Run:

```bash
$ composer require garlic/user
```

#### 2. Add to .env.dist (.env):

```bash
# Social login
FACEBOOK_CLIENT_ID=example_client_id
FACEBOOK_CLIENT_SECRET=example_secret
SOCIAL_ERROR_TTL=300

# Mailer config
MAILER_USER=admin
MAILER_PASSWORD=null
MAILER_FROM_EMAIL=admin@example.host

# Main admin credentials
ROLE_ADMIN_USER=admin_user_name
ROLE_ADMIN_PASSWORD=admin_password

# Ldap host (use if configured)
LDAP_HOST=example.ldap.host

APPLICATION_TOKEN=31ae66c47c0c6373434b3e431fdf8gh

PROTOCOL=http

# Image settings
AVATAR_DIRECTORY=public/avatar
AVATAR_RELATIVE_DIRECTORY=public/avatar

```

#### 3. Add routing configuration (change config/routes.yaml):
```yaml
registration:
    resource: "@GarlicUserBundle/Controller/RegistrationController.php"
    type:     annotation

user:
    resource: "@GarlicUserBundle/Controller/UserController.php"
    type:     annotation

resetting:
    resource: "@GarlicUserBundle/Controller/ResettingController.php"
    type:     annotation

two_factor:
    resource: "@GarlicUserBundle/Controller/TwoFactorController.php"
    type:     annotation

avatar:
    resource: "@GarlicUserBundle/Controller/AvatarController.php"
    type:     annotation

jwt:
    resource: "@GarlicUserBundle/Controller/JwtController.php"
    type:     annotation

social_connect:
    resource: "@GarlicUserBundle/Controller/SocialConnectController.php"
    type:     annotation
```

#### 4. Add Security configuration (change packages/security.yaml):
```yaml
security:
    encoders:
        Symfony\Component\Security\Core\User\User: plaintext
        FOS\UserBundle\Model\UserInterface: bcrypt
        Garlic\User\Security\LdapUser: bcrypt

    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN

    providers:
        chain_provider:
            chain:
                providers: [in_memory, fos_userbundle, ldap]

        in_memory:
            memory:
                users:
                    admin_user_name: # User name
                        password: admin_password # password
                        roles:    ROLE_ADMIN

        fos_userbundle:
            id: fos_user.user_provider.username_email

        ldap:
            ldap:
                service:         Symfony\Component\Ldap\Ldap
                base_dn:         cn=users,cn=accounts,dc=rghub,dc=pro
                search_dn:       ~
                search_password: ~
                default_roles:   ROLE_EDITOR
                uid_key:         uid

    firewalls:
        # disables authentication for assets and the profiler, adapt it according to your needs
        dev:
            pattern:  ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern:   ^/login
            stateless: true
            anonymous: true
            provider:  chain_provider

            form_login:
                check_path:               /login_check
                success_handler:          lexik_jwt_authentication.handler.authentication_success
                failure_handler:          lexik_jwt_authentication.handler.authentication_failure
                require_previous_session: false

            form_login_ldap:
                check_path:               /login_check
                success_handler:          lexik_jwt_authentication.handler.authentication_success
                failure_handler:          lexik_jwt_authentication.handler.authentication_failure
                require_previous_session: false
                service:                  Symfony\Component\Ldap\Ldap
                query_string:             'uid={username}'
                dn_string:                'cn=users,cn=accounts,dc=rghub,dc=pro'

        secured_area:
            pattern:  ^/social/
            provider: chain_provider
            oauth:
                resource_owners:
                    facebook:  /social/login_facebook
                    google:    /social/login_google
                    youtube:   /social/login_youtube
                    instagram: /social/login_instagram
                login_path:    /social/login
                failure_path:  /social/login
                check_path:    /social/login_check
                success_handler: hwi_oauth_authentication.handler.authentication_success
                oauth_user_provider:
                    service: hwi_oauth.user.provider.entity

            anonymous: ~

        editor:
            pattern:          ^/admin/
            http_basic_ldap:
                provider:     ldap
                service:      Symfony\Component\Ldap\Ldap
                query_string: 'uid={username}'
                dn_string:    'cn=users,cn=accounts,dc=rghub,dc=pro'

        main:
            pattern:   ^/
            provider:  fos_userbundle
            stateless: true
            anonymous: true
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

    access_decision_manager:
        strategy: unanimous

    access_control:
        - { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/register/*, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/social/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }

```

#### 5. Add two factor login configuration (change packages/scheb_two_factor.yaml):
```yaml
parameters:
    scheb_two_factor.security.check_path: two_factor_login

services:

    scheb_two_factor.security.google.provider:
        class: Garlic\User\Security\TwoFactorProvider
        arguments:
          - '@scheb_two_factor.security.google.code_validator'
          - '%scheb_two_factor.parameter_names.auth_code%'
        tags:
          - { name: 'scheb_two_factor.provider', alias: 'google'}

    scheb_two_factor.trusted_filter:
        class: Garlic\User\Security\TrustedFilter
        arguments:
            - '@scheb_two_factor.provider_registry'
            - '@scheb_two_factor.trusted_cookie_manager'
            - '%scheb_two_factor.trusted_computer.enabled%'
            - '%scheb_two_factor.parameter_names.trusted%'

    scheb_two_factor.security.google.renderer:
        synthetic: true

scheb_two_factor:

    trusted_computer:
        enabled: true
        cookie_name: two_factor_trusted_computer
        cookie_lifetime: 5184000 # 60 days

    # Google Authenticator config
    google:
        enabled: true                    # If Google Authenticator should be enabled, default false
        server_name: example.server.name   # Server name used in QR code
        issuer: Sequrity                 # Issuer name used in QR code
        template: ~                      # Template used to render the authentication form

    security_tokens:
        - Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken
        - Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken

    model_manager_name: ~

    persister: scheb_two_factor.persister.doctrine

    parameter_names:
        auth_code: _auth_code          # Name of the parameter containing the authentication code
        trusted: _trusted              # Name of the parameter containing the trusted flag

```

# Changelog

## 0.2.2

- Accepts `dmlab/module-sso-core` and `dmlab/module-customer-sso` 0.3.

## 0.2.1

- Requires Magento 2.4.7+ and PHP 8.2–8.5: `magento/*` floors from 2.4.7, `phpunit/phpunit` `^11.5||^12.5`.
- CI tests PHP 8.2–8.5 and sets `notify-on-install false`.
- Composer keywords.
- README: intro, screenshot, install, requirements, Hyvä status.

## 0.2.0

- **Org domain must be `https`**: `http://` and other schemes, paths, queries, fragments and userinfo are rejected on save and at login. Bare hosts get `https://`.
- **`groups` scope is requested only from the org authorization server.** With a custom `auth_server`, define a `groups` claim on the server's Claims tab instead (fixes `invalid_scope`).
- i18n: login-button label and config phrases in `i18n/en_US.csv`.
- Requires `dmlab/module-customer-sso` / `dmlab/module-sso-core` `^0.2.0`.
- Packaging: `dmlab/module-sso-core`, `magento/module-store` and `magento/module-config` declared, `DmLab_SsoCore` / `Magento_Config` sequenced, composer metadata, OSL-3.0 headers.

## 0.1.1

- Initial release under the `dmlab` vendor and `DmLab` namespace.

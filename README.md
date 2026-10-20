# Joey Project

## SSH Connection and Key Hygiene

### Requirements

- Windows with OpenSSH client available (`ssh` on PATH)
- PowerShell
- Access to source key: `D:\Dropbox\Code\joey\keys\StardustPrivateOpenKey`
- Recommended: run first setup in an elevated terminal (Run as Administrator) so ownership/ACL fixes can succeed

### Connection Script

- Script: `D:\Dropbox\Code\joey\keys\connect.ps1`
- The script now:
1. Logs each step and errors
2. Checks whether it is running as Administrator
3. Repairs source key ACLs when elevated
4. Copies key to `C:\Users\Joey\.ssh\StardustPrivateOpenKey`
5. Applies strict ACLs to destination key
6. Connects with `ssh -o IdentitiesOnly=yes`

### Logging

- Log file location: `D:\Dropbox\Code\joey\keys\connect-YYYYMMDD.log`
- Includes step-by-step commands and failure points

### Key Hygiene Rules

- Keep private keys out of shared/public folders
- Use a local runtime copy in `~/.ssh`
- Do not grant broad access (`Users`, `Everyone`, `Authenticated Users`) on private key files
- Use one key per machine/user where possible
- Rotate/revoke keys if a key is exposed

### Troubleshooting

- `Access is denied` on source key:
1. Run script in elevated PowerShell at least once
2. Confirm file exists and is not locked by another process

- `Load key ... Permission denied`:
1. Confirm destination key exists in `~/.ssh`
2. Re-run script so destination ACL hardening is reapplied

- Password prompt appears instead of key auth:
1. Verify key is readable by current user only
2. Verify server has matching public key in `authorized_keys`
3. Run with `ssh -vvv` for detail

## Running Tests

This project uses Pest (with PHPUnit underneath).

### 1. Set PHP config for required extensions

PowerShell:

```powershell
$env:PHPRC = (Resolve-Path php.ini.testing)
```

### 2. Run test suite (Pest)

```powershell
composer test
```

Direct Pest command:

```powershell
php -c php.ini.testing vendor/bin/pest --colors=always
```

Direct PHPUnit command (if you need it):

```powershell
php -c php.ini.testing vendor/bin/phpunit --configuration phpunit.xml
```

### 3. Coverage commands

```powershell
composer test:coverage
composer test:coverage:gate
```

Coverage output paths:

- `build/coverage/clover.xml`
- `build/coverage/html`

Note: coverage requires a PHP coverage driver (`xdebug` or `pcov`). If no driver is installed, coverage commands will not run.

## Spotify Authorization

The `/spotify` authorization route is unavailable by default and is never registered in production. To perform an operator-initiated authorization, use an access-controlled assembly or staging environment, set `SPOTIFY_AUTH_ENABLED=true`, and configure `SPOTIFY_AUTH_USERNAME` plus a `password_hash()` value in `SPOTIFY_AUTH_PASSWORD_HASH`. Disable the setting immediately after authorization is complete.

The route accepts credentials only over HTTPS. The web server or reverse proxy must forward the `Authorization` header to PHP and preserve the HTTPS request scheme. Staging access controls are still required because this maintenance flow is not an end-user login or a complete administrator authentication system.

## Site Listener Authentication

Configure `SITE_LISTENER_TOKENS` as a comma-separated list of independently generated secrets. Callers must send a token in the `Authorization: Bearer <token>` header; query-string tokens are rejected. The three tokens previously committed to this repository must be considered compromised and must not be reused.

Migrate every caller before deployment. Missing or invalid credentials return a generic `401`, while missing server configuration fails closed with `503`.

## Request and Error Protection

Unsafe requests are protected by one-use, session-backed CSRF tokens. Contact-form validation preserves only visible form fields for the next request and never retains CSRF, honeypot, or session safeguards.

Production uses branded 404, 405, and 500 pages without exception details. The `/debug/`, `/test/`, and Spotify authorization routes are not registered in production.

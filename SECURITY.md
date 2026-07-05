# Security Policy

## Supported Versions

Security fixes are intended for the current stable release line.

## Default Exposure

Admin and API routes are disabled by default. Applications that enable them should configure middleware deliberately:

- Keep admin routes behind application authentication and use `admin.gate` for package-level authorization when needed.
- Keep public API exposure behind the application's normal API middleware, throttling, and authentication rules where appropriate.
- Do not expose admin routes without reviewing package-owned record edit protection and application authorization.

## Reporting A Vulnerability

Please report security issues privately through the repository security contact or maintainer contact listed on Packagist. Include a clear description, affected versions, reproduction steps, and any relevant impact.

Please do not publish exploit details before maintainers have had a reasonable opportunity to investigate and release a fix.

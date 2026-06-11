# Security Policy

This project includes user roles, media uploads, private storage, AI-assisted analysis, and database records. Security is a core requirement.

## Reporting Issues

Please report sensitive security problems privately rather than through a public issue.

Include:

- Affected module
- Steps to reproduce
- Impact
- Suggested fix, if known

## Security Priorities

- Secure authentication
- Role-based access control
- CSRF protection
- Prepared database queries
- Protected media access
- Upload validation
- Environment-based secrets

## Production Hardening

Before production use:

- Enforce HTTPS
- Move secrets to environment variables
- Disable debug output
- Add automated tests for authorization flows
- Add dependency scanning
- Review uploaded media access rules

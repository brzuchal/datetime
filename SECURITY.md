# Security Policy

This document describes how the Brzuchal DateTime library monitors, evaluates, and
responds to security risks across code, tooling, and dependencies. The goal is to
maintain a trustworthy temporal library whose behaviour remains deterministic and
resilient across releases.

## Reporting a Vulnerability
- Email: security@brzuchal.dev (or another direct maintainer contact if preferred).
- GitHub: use the repository "Report a vulnerability" workflow to open a private
  Security Advisory discussion.
- Do **not** open public issues for unpatched vulnerabilities.
- Include precise reproduction steps, affected versions, and any relevant logs or
  proof-of-concept material. Encrypt sensitive diagnostics when possible.
- Maintainers will acknowledge reports within 48 hours and provide status updates at
  least every seven days until resolution.

## Supported Versions
Security fixes are backported as capacity allows:

| Version Branch | Status             |
| -------------- | ------------------ |
| main           | actively supported |
| release-x.y.z  | supported when explicitly tagged as LTS |
| older tags     | receive no guarantees; upgrade recommended |

## Security Objectives
- Protect consumers from incorrect temporal calculations that could lead to financial
  or legal impact (e.g. overflow, epoch miscalculations, DST transitions).
- Maintain integrity of timezone and leap-second datasets bundled with releases.
- Minimise attack surface for supply-chain vectors (malicious dependencies, tampered
  build artifacts, compromised tooling).
- Detect and remediate undefined behaviour that could be exploited for denial of
  service, information disclosure, or privilege escalation in consumer systems.

## Threat Monitoring & Risk Assessment
- Track upstream advisories (PHP core, ICU, tzdb) weekly; file issues for relevant
  CVEs within two business days.
- Subscribe to Composer security advisories (`composer audit`) and GitHub Dependabot
  alerts; review alerts within two business days.
- Monitor for time-based logic flaws (e.g. arithmetic overflow, invalid date parsing,
  data truncation) through fuzzing, property-based tests, and boundary regression
  suites.
- Review third-party contributions for malicious payloads: enforce signed commits or
  verified GitHub accounts where possible, and scrutinise large diffs touching build
  scripts, CI, or dependency manifests.

## Secure Development Lifecycle
- All changes require peer review; reviewers verify security impact, test coverage,
  and adherence to coding standards.
- Run `vendor/bin/phpunit`, `vendor/bin/phpstan analyse`, and `vendor/bin/phpcs` for
  every merge request; block merges on failures.
- Add regression tests for every security fix, including DST boundaries, leap years,
  and high-range epoch values to prevent recurrence.
- Document any assumptions about external input validation when exposing new public
  APIs.
- Perform threat modelling for new calendar systems or parsing features prior to
  implementation; record outcomes in issue tracker or design docs.

## Dependency & Supply-Chain Security
- Use locked dependencies (`composer.lock`) and review diffs on every update.
- Run `composer audit` at least monthly and after each dependency update.
- Prefer well-maintained libraries with transparent release histories; avoid
  transitive dependencies lacking security metadata.
- Verify integrity of timezone data fetched via `make tz` by checking upstream
  checksums; store fetch logs for auditing and avoid committing downloaded archives.
- Ensure CI workflows pin specific action versions and avoid unreviewed scripts.

## Vulnerability Response Process
1. Triage: classify severity (critical, high, medium, low) and affected versions.
2. Mitigation: develop fix or workaround; keep advisory stakeholders informed.
3. Validation: extend tests and static analysis to cover the exploit scenario.
4. Release: publish a patched release, update CHANGELOG, and notify reporters.
5. Disclosure: coordinate public advisory publication; acknowledge researchers.
6. Post-mortem: capture lessons learned, update checklists, and automate tests where
   possible.

## Release & Distribution Security
- Tag releases using signed (GPG) tags and attach build artifacts generated from a
  clean environment.
- Build automation runs in hardened CI runners with restricted secrets and least
  privilege scopes.
- Validate artifacts before publishing to Packagist; ensure changelog highlights
  security fixes prominently.

## Continuous Monitoring & Metrics
- Review open security issues and advisories during each sprint or monthly planning.
- Track mean time to acknowledge (MTTA) and mean time to remediate (MTTR); target
  MTTA < 48 hours and MTTR < 30 days for high-severity findings.
- Maintain a checklist of security controls in AGENTS.md and update as capabilities
  evolve.

## Responsible Security Research
- We welcome good-faith security testing that respects user privacy and does not
  degrade service for others.
- Exploit attempts must stay within your own systems; social engineering of
  maintainers or community members is out of scope.
- We will not pursue legal action for research performed in accordance with this
  policy and coordinated disclosure timelines.

## Contact & Escalation
- For urgent issues affecting production consumers, escalate via the security email
  and mark the subject line with "URGENT".
- If no acknowledgement arrives within 48 hours, ping maintainers via alternative
  channels listed in README.md or community forums.
- Keep communication confidential until a fix is available or mutually agreed upon.

This policy is reviewed at least twice per year. Propose updates via pull request and
record major revisions in CHANGELOG.md under "Security".

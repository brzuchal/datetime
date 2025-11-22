# Contributing to brzuchal/datetime

## Quality Gates

### Pre-Commit Requirements

**CRITICAL**: Before **every** commit, you MUST run all tests locally:

```bash
# Run PHPUnit (must pass - 0 failures)
./vendor/bin/phpunit --colors=always

# Run PHPStan (must pass - 0 errors)
./vendor/bin/phpstan analyse --memory-limit=512M --level=max src tests
```

**DO NOT COMMIT** if either of these fails.

### Pull Request Requirements

All changes MUST go through Pull Requests. Direct commits to version branches (`1.0`, `2.0`, etc.) are **forbidden**.

Required status checks for PR merge:
- ✅ `tests (8.4, lowest)` - PHPUnit with lowest dependencies
- ✅ `tests (8.4, highest)` - PHPUnit with highest dependencies  
- ✅ `analysis` - PHPStan static analysis (level max)

### Release Process

**Tags** can only be created on version branches (`1.0`, `2.0`, etc.) when **all tests are green**.

For every tag (e.g., `v1.0.0`), a **GitHub Release** MUST be created with:
- Tag name (e.g., `v1.0.0`)
- Release title (e.g., `Release 1.0.0`)
- Release notes extracted from `CHANGELOG.md`

Example:
```bash
# After tag is pushed
gh release create v1.0.0 --title "Release 1.0.0" --notes-file RELEASE_NOTES.md
```

### Branch Protection Rules

Version branches (`1.0`, `2.0`, etc.) are protected with:
- Pattern: `[0-9]+\.[0-9]+` (regex for version branches)
- Require pull request before merging
- **Require branches to be up to date before merging**
- **Allow squash merging ONLY** (no merge commits, no rebase)
- Require status checks to pass:
  - `tests (8.4, lowest)`
  - `tests (8.4, highest)`
  - `analysis`

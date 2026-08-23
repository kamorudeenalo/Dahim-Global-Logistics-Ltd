# Git & GitHub Workflow

## Branching

- `main` = production-ready history
- `stage-*` = stage planning/foundation work
- `feature/*` = feature development
- `fix/*` = bug fixes
- `docs/*` = documentation-only changes

## Commit rules

Use clear Conventional Commit-style messages:

- `feat:` new functionality
- `fix:` bug fix
- `refactor:` structural change without intended behaviour change
- `docs:` documentation
- `test:` tests
- `chore:` maintenance/tooling
- `perf:` performance improvement
- `security:` security-related change

Example:

```text
feat(insights): add simplified insight editor
```

## Issue tracking

A meaningful feature or problem should have a GitHub Issue when it requires planning, discussion, testing, or acceptance.

## Pull requests

Changes that affect production behaviour should normally be reviewed through a Pull Request before merging into `main`.

PR descriptions should include:

- What changed
- Why it changed
- Testing performed
- Documentation updated
- Migration implications
- Screenshots where UI changed

## Releases

Use versioned releases for meaningful milestones.

Suggested early milestones:

- `v0.1.0` Foundation
- `v0.2.0` Authentication
- `v0.3.0` Insights
- `v0.4.0` SEO
- `v0.5.0` Media
- `v0.6.0` Shipments
- `v0.7.0` Inquiries
- `v0.8.0` Public website
- `v0.9.0` Migration/cutover candidate
- `v1.0.0` Production platform

## Documentation rule

If implementation changes architecture, data structures, security, APIs, or user-facing behaviour, update the relevant documentation in the same change.

## Environment rule

Never commit:

- passwords
- API keys
- database credentials
- production secrets
- private certificates
- `.env.production`

Commit only safe examples such as `.env.example`.

## Deployment rule

No direct production editing. Changes flow through Git and the agreed staging/production process.

# Release dependency and deployment gate implementation plan

**Goal:** resolve api-community#381 without reverting common 1.0.12 and prevent failed validation from reaching a server.

**Architecture:** preserve the orders v1.0.11 runtime and allow compatible common 1.x releases from 1.0.11. Publish a new immutable orders release before activating its exact pin. Reuse backend checks as a deployment prerequisite, return the tested SHA, and deploy that SHA rather than a moving branch.

**Constraints:** numeric exact first-party pins in the application; no aliases, replacement repositories, ignored platform requirements or tracked locks. Preserve maintenance operations and the existing user .env.dev. Repository administration and tag publication require capabilities unavailable through the current connector; never report them completed without evidence.

## Tasks

- [x] Reproduce Composer conflict with cached Packagist metadata. Verify orders v1.0.11 runtime baseline and common compatibility using Composer's actual solver.
- [x] Update orders common constraint to ^1.0.11, keeping queue and runtime unchanged. Prepare v1.0.12 metadata-only release and an exact API orders 1.0.12 pin, clearly pending publication.
- [x] Add regression coverage for validation failure, cancellation, successful validation, maintenance operations and a moving deployment branch. Make Pull Request Checks reusable and require it before SSH; pass its checked SHA to the server. Keep existing pull request check names.
- [ ] Run focused tests, Composer validation, YAML/shell checks and full suites if available. Publish task branches and reviewable PRs. Record publication/protection blockers and exact next actions.

## Review focus

- Failed, cancelled or skipped validation must never allow a normal deployment.
- Manual target selection must validate the target branch, not the workflow branch.
- Maintenance inspection/cache recovery must remain available without dependency installation.
- A branch advancing after validation must not deploy an untested revision.
- A new orders version must retain the existing published runtime; immutable tags must not be moved.

## Execution evidence

- Original conflict reproduced with Composer (exit 2).
- Candidate graph resolves 217 packages without ignored platform requirements.
- 14 focused PHPUnit tests pass (87 assertions); workflow shell blocks pass bash -n.
- Independent review found and corrected appearance source revision mismatch and independent server dependency resolution. The validated lock now travels as an artifact and is checksum-verified before checkout.
- Existing DeployShellControlFlowTest had an obsolete `nohup php` boundary; updated to the existing operation guard so its real shell permission check runs.
- Full backend command attempted and blocked: vendor/bin/phpunit absent. Current archives cannot be downloaded in this environment.
- Remaining: publish orders v1.0.12, validate actual installation/runtime, activate repository ruleset with Administration access, promote via RC. Legacy manual LaveGo workflows remain outside this primary Deploy gate.

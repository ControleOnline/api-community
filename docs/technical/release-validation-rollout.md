# Release validation rollout — api-community#381

## Incident

PR #380 merged at 2026-11-15T13:44:56Z. Its baseline-checks job failed at
13:44:57Z because orders v1.0.11 requires common exactly 1.0.11 while the
application requires common 1.0.12. Source-policy passed; it checks the RC
source/manifest, not dependency compatibility.

## Package publication (required before the API pin is activated)

The orders task-381 branch starts from published v1.0.11, which already
contains origin/master. Only composer.json changes: common ^1.0.11, queue
still 1.0.3. This avoids republishing the older orders/master product code.

1. Review/validate the orders task commit and publish a new immutable v1.0.12
   tag through the release process; never move the existing v1.0.11 tag.
2. Wait until Packagist exposes v1.0.12 with the new common requirement.
3. Run a clean `composer install --no-interaction --no-progress --prefer-dist
   --no-scripts`, application boot and both backend suites with the API pin.
4. Promote the API through dev, frozen RC, staging and master only with green
   checks. The API PR stays draft until actual publication/runtime validation.

## Merge protection (repository administrator)

The reviewed payload is `.github/rulesets/release-validation.json`.
It adds a separate active ruleset for staging/master, preserves existing
rulesets, requires baseline-checks and source-policy against the latest base,
and grants no bypass actors. Committing the JSON does not activate a GitHub
ruleset.

With a GitHub account that has repository Administration write permission:

```sh
# Inspect existing rules and avoid creating a duplicate Release validation rule.
gh api --paginate repos/ControleOnline/api-community/rulesets
# Create only if this named rule does not already exist. Otherwise inspect and
# update that exact rule ID with PUT after preserving any additional policies.
gh api --method POST repos/ControleOnline/api-community/rulesets \
  --input .github/rulesets/release-validation.json
# Verify effective rules and an RC PR: failed or pending checks must block merge.
gh api repos/ControleOnline/api-community/rules/branches/master
gh api repos/ControleOnline/api-community/rules/branches/staging
```

API reference: https://docs.github.com/en/rest/repos/rules#create-a-repository-ruleset
Do not use administrative bypass to promote this fix. A draft/failed PR is not
proof that live protection has been activated.

## Deploy behavior

The Deploy workflow calls the same backend validation used for PRs before
opening SSH. Pushes validate github.sha; manual deploys resolve the selected
target branch. Validation returns the checked commit SHA; the server checks out
that SHA even if the branch advances. The CI-generated lock is transferred as
a short-lived run artifact and verified with SHA-256 before checkout; installation
uses that lock and never updates packages on the server. Staging appearance activation also uses
the validated inspector source. Inspection and cache recovery retain their
existing maintenance behavior and do not install packages.

## Evidence and limitations

- Composer reproduced the original conflict with cached Packagist metadata.
- Composer's solver accepted 217 packages with the candidate orders metadata
  and common v1.0.12, without ignored requirements or aliases. This is an
  offline candidate check, not proof of publication or runtime compatibility.
- Focused PHPUnit regression tests cover gate outcomes and a real Git remote
  advancing between validation and checkout.
- Full runtime suites cannot run in this checkout without installed vendor
  packages. The shell cannot reach GitHub/Packagist; its metadata cache lacks
  several current package archives.
- The connected GitHub tools can publish task branches/PRs but expose no tag
  publication or repository-rules mutation. The browser failed to open the
  settings page. The protection payload has not been applied remotely.

The two legacy manual LaveGo workflows remain outside this primary Deploy
change; they are not covered by this validation gate. Their automatic triggers
are restricted to the placeholder branch `xxx`. Do not use them to promote
this fix or claim that every manual deployment path is protected.

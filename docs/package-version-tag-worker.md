# Package version tag worker

The scheduled worker handles the 20 backend Composer package repositories
allowlisted in `scripts/tag-package-versions.mjs`. Frontend npm packages use
their repository's `.github/workflows/publish-npm.yml` worker. For Composer,
the worker creates an immutable `vMAJOR.MINOR.PATCH` tag only when the package
version and exact 40-character commit SHA are listed in
`package-release-approvals.json`. Composer package versions are derived from
VCS tags, so package `composer.json` files normally omit a `version` property;
the reviewed approval entry supplies that version.

Add or update an approval through a reviewed pull request after the package
release has passed its security and release gates:

```json
{
  "releases": [
    {
      "repository": "ui-example",
      "version": "1.2.3",
      "sha": "0123456789abcdef0123456789abcdef01234567"
    }
  ]
}
```

The SHA must contain the named package. Merge the approval change to the
default branch only after Security accepts the package change and the Manager
revalidates it. The worker checks the package name, approved stable version,
full commit SHA, and reachability from the package repository's default branch.
Existing tags are never moved. It ignores versions without a matching approved
entry and rejects a SHA with a mismatched package name or declared version.

The workflow checks out and runs only from the trusted default branch. When
approved releases exist, configure the `api-community` Actions secret
`PACKAGE_TAG_TOKEN` with a fine-grained token limited to the 20 allowlisted
package repositories and `Contents: read and write`. Do not reuse a broad
organization token. The worker looks up those repositories individually and
checks the same allowlist before writing. An empty approval list exits cleanly.
Dispatching from another ref will be skipped. Use `workflow_dispatch` with
`dry_run` to preview approved tags.

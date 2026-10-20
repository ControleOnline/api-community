# Local MANAGER collection read overlay

Local experiment; no branch, issue, publication, schema change or business writes.

## Ownership and source

The running sources are Composer dist files in `vendor/controleonline/common`, package `v1.0.5`, source reference `1ed215c1c2270575fe7264c0b6d4fd9a705a4dd0` (api-platform-common). There is no separate Git checkout for this package here. This directory keeps the exact before/after sources, a review diff and a checksum manifest so the change is reviewable and can be reproduced after reinstalling dependencies.

**The before snapshot includes existing local normalizer changes. It is not an assertion that these bytes match the stock published package.** Applying to a different baseline deliberately fails rather than overwriting it. Nothing edits Composer identity or claims a new package release.

From the API root:

```sh
python3 scripts/apply-manager-read-batch.py
python3 scripts/apply-manager-read-batch.py --apply
python3 scripts/apply-manager-read-batch.py --revert
```

The default is a check only. All versions and destination/payload hashes are checked before any mutation. Apply/revert are idempotent and refuse unexpected local edits. The script accesses neither network nor database. Revert restores only this overlay's exact before snapshot, including work already present before this overlay. It does not reset the repository.

## What changes

`CollectionSummaryNormalizer` optionally preloads existing relationships for People/Product collections after the existing provider has chosen the authorized, filtered and paginated roots. The original collection/paginator, operation, filters, totals, summary and serializer groups continue through the existing serializer.

`CollectionReadBatchHydrator` uses separate fetch joins per to-many relationship, constrained to those root objects, in chunks of 100. It does not move the page limit onto a to-many join and does not choose new visible roots. People preloads seven relationships, including the address→street→district/CEP→city→state→country to-one chain; product links also preload their product reference. Products preloads product-file/file and product-people relationships.

`ExtraDataService` can fetch metadata and extra fields for each batch of selected products. `ExtraDataNormalizer` consumes this response-local WeakMap, including empty entries, preserving the existing metadata shape. It still uses the existing individual read when there is no preload. There is no persistent or cross-user metadata cache.

One-pass iterators are left untouched. A plain Doctrine paginator may execute its root page query again when the downstream serializer iterates it; preserving the original paginator avoids changing counts/envelopes. The expensive N+1 relationship queries are still reduced. The API Platform paginator already caches its iterator.

No global eager-loading configuration, security filters, tenant filters, linkType/company parameters, delete flags, published catalog semantics or writes were changed. Do not substitute the published showcase catalog for administrative product management: their membership rules differ.

## Evidence and limits

Before this overlay, existing dev profiles showed People 414 queries for a 50-row page: 50 queries each for seven relationship tables, plus 45 related product reads. Products showed 104 queries, including 45 extra-data and 36 file reads. These historical measurements establish the N+1 pattern; they are not an after measurement.

Isolated tests: **24 tests, 1923 assertions, exit 0**, PHP 8.5.11. They cover bounded query construction restricted to supplied page objects, empty/unrelated/single-pass collections, batch metadata with absent values and duplicate objects, preservation of paginator/envelope/summary/filter context, existing provider and security extension behavior, and response-local serialization caches. There are 17 deprecations and 2 PHPUnit notices in the selected existing tests; none are failures. No integration test or application DB write was executed.

Syntax checks passed for the four changed/new production PHP files. Overlay apply/idempotent apply/revert, original hashes, conflict refusal and version refusal were exercised against a temporary filesystem fixture. No current-runtime latency reduction is claimed yet. Next validation: an authenticated read-only navigation on the same company/query/page before/after, inspect status and total, compare query counts and timings, then separately verify another authorized and an unauthorized company context. Low query count alone is not proof of equivalent access control.

Selected test command:

```sh
vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php \
  vendor/controleonline/common/tests/Service/CollectionReadBatchHydratorTest.php \
  vendor/controleonline/common/tests/Service/ExtraDataBatchReadTest.php \
  vendor/controleonline/common/tests/Serializer/CollectionSummaryNormalizerTest.php \
  vendor/controleonline/common/tests/Serializer/ExtraDataNormalizerTest.php \
  vendor/controleonline/common/tests/State/HydratedReadProviderTest.php \
  vendor/controleonline/people/tests/Doctrine/Extension/PeopleSecurityExtensionTest.php \
  vendor/controleonline/common/tests/Service/ExtraDataServiceTest.php
```


## Runtime activation finding (2026-10-15)

The dev container compiled before the overlay still constructed `CollectionSummaryNormalizer` with only RequestStack. Thus the initial installed PHP patch was **not active** in the requests that still showed 212 queries for 20 people. Explicit service wiring is now included in the package overlay. Rebuild the local dev container before measuring; Composer vendor service signatures are not reliably invalidated by the existing container. Coordinate this with the running local server. Reapply after reinstalling dependencies and rebuild again. No application DB migration is needed.

A profiler response at 00:05:14 UTC on 2026-10-16 (21:05:14 Brasilia on 2026-10-15) recorded 212 queries / 6329 ms, including seven relationships queried 20 times each and street/CEP/district 17 times each. This is evidence of inactive batching, not an after-optimization result. Separate 1-row count requests were about 1.1 s / 30 queries and are not comparable to the 20-row collection.

## Verified activation and local read (2026-10-15, 21:38 Brasilia)

A second activation defect was reproduced with the real Symfony Serializer: `getSupportedTypes()` advertised `iterable`, which Symfony does not select for ordinary collection objects. The new regression failed with zero `prepare()` calls, then passed after advertising context-dependent `object` and `array` support. CollectionSummaryResult retains its explicit supported type and the recursion guard remains in place. The exact overlay and checksums include this repair.

With the same authorized company/filter/page of 20 clients, authenticated read-only HTTP returned 20 rows and total 648: before 5687 ms (profiler 5587 ms / 212 SQL); after 1035 ms (profiler 994 ms / 25 SQL). A 10-row read returned total 648 in 997 ms (25 SQL). Browser isolated authenticated reads also returned 20 rows in 1154 ms. These are API/transport observations, not a passed end-to-end journey or proof of all permission contexts. The local PHP development server was restarted through the installed php8.5 wrapper with PHP_CLI_SERVER_WORKERS=4 to remove its single-worker request queue; no DB migration or business write was made.

Selected suite now passes 25 tests / 1927 assertions, exit 0; 17 existing deprecations and 2 PHPUnit notices remain.

## Local read cycles — 2026-10-16

The overlay now also guards the installed controleonline/people package identity.
PeopleLink adds an optional `listFields` PropertyFilter with a restricted whitelist
(id, enable, linkType, person id/name/alias, company id). The manager collaborator
table opts in using PHP bracket query keys. Ordinary collection reads, serializer
groups, security filters, totals, write operations and detail reads remain intact.
The authorized full read and sparse read returned the same seven root links and
identical projected fields. This avoids eagerly joining contacts and media that
are not presented by this table; it is not a new authorization or business filter.

Latest real console integration: collaborator list 664 ms / 7 rows; the preceding
full-read warm baseline was 2849–2942 ms. Cold container rebuild timings are not
compared as improvements. Full page paint and device UX are separate gates.

Selected suite including PeopleLinkListFieldsTest: 26 tests / 1931 assertions,
exit 0; 17 pre-existing deprecations and 2 PHPUnit notices. APP report:
`app-community/docs/manager-read-cycle-validation.md` at the workspace root.
Use `scripts/serve-local.sh` for the reproducible four-worker local PHP server.
No schema change, business write, release, commit or push was performed.


## Product collection metadata — 2026-10-19

The local overlay now forwards serializer context to the batch hydrator. Only the exact product:read group uses partial File metadata (id/fileType/fileName/context/extension/public), excluding binary content from collection preloading. Other serializer groups retain full selection; authenticated file downloads keep their original path. No permission, pagination, price or inventory rule changed.

Same authenticated native query, company/filter/page: five products and total five, full member payload SHA256 identical before/after. Transport 3255 → 878 ms; profiler after 778 ms / 20 SQL / 523 ms DB. The previous binary-file query alone took 2494 ms. These timings do not measure full screen paint. Overlay suite: 26 tests / 1933 assertions, exit zero, 17 existing deprecations and three notices. Security metadata wiring: two tests / 70 assertions, exit zero, existing PHP 8.5 deprecations. No DB writes or publication. App audit: docs/manager-pdv-core-review-20261019.md in app-community.

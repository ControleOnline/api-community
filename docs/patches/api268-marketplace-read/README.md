# API #268: marketplace metadata read snapshot

The catalog-status endpoint repeatedly reads `ExtraFields` and `ExtraData` for each scalar field. The common helper now reuses the existing `findByEntityNameEntityId` repository read once for each entity actually inspected, and resolves each field definition once. The repository's ascending row order preserves the scalar lookup's latest-row rule; blank values remain null.

Snapshots live on the current Request only and apply exclusively to GET `/marketplace/integrations` and GET `/marketplace/integrations/catalog/status`. No database writes, shared cache, provider filter changes, network calls or cross-request state are introduced. Mutation routes use the original scalar path. The helper does not prefetch other entities or tenants.

The separate `config/routes/aaa_translate_overview.yaml` reserves the static overview segment before the generic translate identifier route. It retains the existing API Platform operation, provider and authorization.

## Local overlay

The Composer package remains locked. This overlay builds on the pre-existing manager-read-batch overlay; apply that overlay first on a clean installation. Exact package identity and content checks reject unrelated local content before writing files. Do not run the earlier overlay's revert through this later layer; revert in reverse order.

```sh
python3 scripts/apply-api268-marketplace-read.py          # inspect only
python3 scripts/apply-api268-marketplace-read.py --apply
python3 scripts/apply-api268-marketplace-read.py --revert
```

Versioned regression commands and actual results are in `validation.md`. This directory preserves both source states and their SHA-256 hashes so local Composer reinstalls do not lose the fix.

## Authenticated runtime comparison

Reuse the current authenticated local session privately. Compare the same provider/company and query parameters on both marketplace GETs before and after the overlay; compare parsed response JSON, then inspect only status, elapsed time, SQL count and table totals from the existing Symfony profiler. Keep headers, tokens, payloads and commercial values out of evidence exports. Replay GET `/translates/overview` and confirm that the overview provider executes with its existing role rule. Fresh local request results, response parity and the opcode revalidation caveat are now recorded in `validation.md` and `runtime-comparison.json`. Production behavior remains unverified.

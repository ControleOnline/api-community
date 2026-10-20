# API #268 local validation

Local only; no commit, remote publication, or deployment proof.

**Baseline follow-up (19/10/2026):** the full-suite failures below have now been compared against repository HEAD plus the exact published common/people package references in isolated copies. All 51 errors and 15 failures also occur on that baseline, with identical normalized diagnostics; no regression was detected. The full suite still exits 2. See [baseline-comparison.md](baseline-comparison.md) and `baseline-comparison.json` for identities, controls and limits. The earlier lack of baseline evidence described below is historical.

- Marketplace regression first failed at the unexpected per-field repository call, then passed with batching.
- Router regression first selected `Translate` for `/translates/overview`, then selected `TranslateOverview`; resource security remains `ROLE_HUMAN`.
- `php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Metadata`: 5 tests, 24 assertions, exit 0; 2 framework deprecations.
- `python3 -B -m unittest discover -s tests/Metadata -p 'test_*.py'`: 3 tests, apply/revert/idempotence/local-change rejection/package mismatch.
- PHP syntax checks and `git diff --check` passed.
- Full backend suite: 986 tests, 7702 assertions, 51 errors, 15 failures, exit 2. Run with `DATABASE_URL` forced to an unreachable localhost test-only address to prohibit commercial database writes. Numerous existing package/runtime contract mismatches were observed, but an identical full-suite baseline run without this overlay has not been performed; they are unresolved findings, not a proven regression classification. Full log was saved outside Git at `/tmp/co-api268-full.log`.

## Authenticated local runtime

GET-only replay reused the same local authenticated request context privately (provider/company 3); no token, header, cookie, payload or commercial value was exported. Source was toggled to the exact saved pre-overlay bytes for BEFORE and restored unconditionally in `finally`; final overlay hash verification passed. The static overview route fix remained installed throughout this metadata comparison.

| Endpoint | BEFORE | Stable AFTER | Response parity |
| --- | --- | --- | --- |
| Catalog status | HTTP 200; 675 SQL; 25.150 s | HTTP 200; 106 SQL; 8.014 s | Canonical JSON SHA identical |
| Integrations | HTTP 200; 222 SQL; 6.059 s | HTTP 200; 87 SQL; 2.980 s | Canonical JSON SHA identical |
| Translates overview | HTTP 200; 15 SQL; 1.362 s | HTTP 200; 15 SQL; 1.358 s | Canonical JSON SHA identical |

These are local individual timings, not production benchmarks or proof that all remaining latency has been removed. The immediate post-restore status request retained the baseline opcode (675 SQL); a later stable request confirmed the intended 106 SQL. That intermediate result is retained rather than omitted. All four phases, including initial AFTER, have identical response hashes. Safe counts, timings and hashes are preserved in `runtime-comparison.json`. Existing `/files/` HTTP 500 profiler entries were absent; no new file request was made.

## Full-suite failing test names

- `ControleOnline\Tests\Service\CteEmissionServiceTest::testIntegrateDelegatesExistingInvoiceTask`
- `ControleOnline\Tests\Service\CteEmissionServiceTest::testIntegrateCreatesTaskFromLegacyIdsAndDelegates`
- `ControleOnline\Tests\Service\EmitCteServiceTest::testEmitBindsSelectedInvoicesToCreatedTaskBeforeSefaz`
- `ControleOnline\Tests\Service\EmitCteServiceTest::testEmitRejectsWhenInvoiceAlreadyHasTask`
- `ControleOnline\Tests\Service\EmitCteServiceTest::testEmitRejectsWhenInvoiceAlreadyHasCte`
- `ControleOnline\Tests\Service\MaintenanceRoutineServiceTest::testRunsOpenOverdueOpportunitiesRoutineWhenServiceAvailable`
- `ControleOnline\Tests\Controller\CashRegisterControllerIncomeStatementsTest::testGetIncomeStatementsReadsQueryBagWithoutRequestGet`
- `ControleOnline\Tests\Entity\InvoiceListGroupTest::testInvoiceListCoversDisplayedFields`
- `ControleOnline\Tests\Entity\InvoiceListGroupTest::testInvoiceListDoesNotExpandHeavyPeopleCollections`
- `ControleOnline\Tests\Entity\InvoiceListGroupTest::testInvoiceListDoesNotExpandCategoryCompany`
- `ControleOnline\Tests\Entity\InvoiceReadGroupTest::testInvoiceReadDoesNotExpandPeopleCollections`
- `ControleOnline\Tests\Entity\InvoiceReadGroupTest::testInvoiceReadDoesNotExpandCategoryCompany`
- `ControleOnline\Tests\Service\NotifyOverdueChargesServiceTest::testNotifyOverdueChargesMarksNotifiedAndGroupsByPayer`
- `ControleOnline\Tests\Service\NotifyOverdueChargesServiceTest::testNotifyOverdueChargesSkipsWhenNoEligibleInvoices`
- `ControleOnline\Tests\Service\NotifyOverdueChargesServiceTest::testNotifyOverdueChargesIsolatesChannelFailures`
- `ControleOnline\Integration\Tests\Controller\Webhook\CaptureControllerTest::testGithubWebhookKeepsLegacyProductionPath`
- `ControleOnline\Integration\Tests\Service\IntegrationServiceTest::testIfoodMarketplaceFinancialGenerationTriggersOnlyForConclusionEvents`
- `ControleOnline\Orders\Tests\Controller\DiscoveryCartTest::testRequiresClient`
- `ControleOnline\Orders\Tests\Controller\DiscoveryCartTest::testRequiresProvider`
- `ControleOnline\Orders\Tests\Controller\DiscoveryCartTest::testResolvesCartWithProviderClientAndOrderTypeQuery`
- `ControleOnline\Orders\Tests\Controller\DiscoveryCartTest::testDefaultsStillRequireClientAndProviderWhenOrderTypeAbsent`
- `ControleOnline\Orders\Tests\Entity\OrderProductApiResourceTest::testMutationResponseContainsOnlyScalarOrderProductFields`
- `ControleOnline\Orders\Tests\Entity\OrderTrackingApiResourceTest::testTrackingContractContainsOnlyTheOperationalRelationsItNeeds`
- `ControleOnline\Orders\Tests\Service\MarkOrderAsPaidAuthorizationTest::testServiceRefusesTenantCrossLinkReferences`
- `ControleOnline\Orders\Tests\Service\MarkOrderAsPaidAuthorizationTest::testServiceRefusesPublicResourceWithoutCompany`
- `ControleOnline\Tests\Service\MarkOrderAsPaidServiceTest::testApplyPaidOrderStatusDoesNotFallBackToClosedClosed`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testAddingEquivalentSimpleProductIncrementsExistingQuantity`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testAddingDifferentSimpleProductKeepsSeparateRootLine`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testAddingLoyaltyGiftCommentKeepsSeparateRootLineAndPreservesComment`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testAddingEquivalentCustomizedProductIncrementsTheWholeTree`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testAddingDifferentCustomizedProductPersistsAnotherTree`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceConsolidationTest::testSameCustomizationWithDifferentComponentQuantityStaysSeparate`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPrePersistRejectsSaleOrderOnAddProductsRoute`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPrePersistRejectsSaleOrderOnReplaceProductsRoute`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPrePersistAllowsCartOrderOnAddProductsRoute`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPrePersistAllowsCartOrderOnReplaceProductsRoute`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPreUpdateAllowsCartOrderQuantityChanges`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPreUpdateRejectsSaleOrderQuantityChanges`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPreRemoveRejectsSaleOrderDeletion`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPreRemoveRejectsQuoteOrderDeletion`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceMutationGuardTest::testPreRemoveAllowsCartOrderDeletion`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceRecursiveTreeTest::testPersistsARecursiveComponentTree`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceRecursiveTreeTest::testEquivalentRecursiveTreeIncrementsEveryLevel`
- `ControleOnline\Orders\Tests\Service\OrderProductServiceRecursiveTreeTest::testDifferentGrandchildKeepsTheRootTreesSeparate`
- `ControleOnline\Tests\Service\ProductShowcaseCatalogServiceTest::testResolvedEmptyShowcaseDoesNotLeakLegacyProducts`
- `ControleOnline\Tests\Service\ProductShowcaseCatalogServiceTest::testLegacyFallbackIsExplicitOnlyWhenNoShowcaseResolves`
- `ControleOnline\Tests\Service\ProductShowcaseCatalogServiceTest::testClientIdsCannotExpandTrustedShowcaseCategoryProjection`
- `ControleOnline\Tests\Service\ProductShowcaseCatalogServiceTest::testPosResolvesConfiguredShowcaseFromDeviceWithinCompany`
- `ControleOnline\Tests\Service\ProductShowcaseCatalogServiceTest::testShopResolvesShowcaseFromTrustedDomain`
- `ControleOnline\Queue\Tests\Entity\DisplayPresentationTest::testPresentationFieldsAreAvailableToTheDisplayEditor`
- `ControleOnline\Tests\Service\PeopleAttendanceReportServiceTest::testBuildRowsFromDataHighlightsLateWorkAndAbsenceStates`
- `App\Tests\Service\DeployShellControlFlowTest::testWritableNonRootDeploymentContinuesPastPermissionsCheck`
- `ControleOnline\Tests\Service\Cte\CteFiscalConfigTest::testLoadWithoutCompanyReturnsEmptyKeysAndNextNumberOne`
- `ControleOnline\Tests\Service\Cte\CteXmlBuilderTest::testBuildRequiresRntrcInsteadOfUsingFallback`
- `ControleOnline\Tests\Service\CteEmissionServiceTest::testIntegrateThrowsWhenPayloadIncomplete`
- `ControleOnline\Tests\Service\CteEmissionServiceTest::testIntegrateThrowsWhenLegacyNfsMissing`
- `ControleOnline\Tests\Entity\FileDownloadRouteTest::testDownloadRoutesIncludeBareAndTenantPrefixedTemplates`
- `ControleOnline\Tests\Service\FileServiceSecurityFilterTest::testSecurityFilterBlocksWhenUserHasNoCompanies`
- `ControleOnline\Tests\Service\FileServiceSecurityFilterTest::testSecurityFilterScopesReadsToUserCompanies`
- `ControleOnline\Tests\Listener\OrderSingleInvoiceCancellationListenerTest::testOnFlushCancelsUnitaryInvoicesWhenOrderTransitionsToCanceled`
- `ControleOnline\Integration\Tests\Service\IfoodStoreOperationsServiceTest::testPersistOrderIntegrationStateMergesIfoodBlockIntoOrderOtherInformations`
- `ControleOnline\Integration\Tests\Service\UberServiceTest::testBuildAddressPayloadIncludesFormattedAddressAndLocation`
- `ControleOnline\Orders\Tests\Service\DeliveryOrderPushServiceTest::testPostPersistSendsNotificationToDeliveryDeviceConfig`
- `ControleOnline\Orders\Tests\Service\DeliveryOrderPushServiceTest::testPostUpdateSendsOnlyWhenOrderTransitionsToAwaitingAcceptance`
- `ControleOnline\Orders\Tests\Service\OrderDeliveryMapServiceTest::testNormalizeDeliveryOrderFormatsAddressAndDisplayCode`
- `ControleOnline\Orders\Tests\Service\OrderLoyaltySnapshotServiceTest::testBuildForClientAcrossProvidersReturnsOneOpenCardPerFranchise`

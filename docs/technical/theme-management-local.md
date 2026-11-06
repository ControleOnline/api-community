# Local theme management

Local-only extension; no task, commit, publication, schema migration or real theme writes were performed during validation.

## Model and authorization

Theme remains the shared palette entity, with no owner column. PeopleDomain.theme remains the binding used by themes-colors.css. No company/domain links are reassigned implicitly by creating or editing a palette.

ROLE_HUMAN permits entering the management endpoints, but does not grant global theme visibility. Default scope=companies follows PeopleRoleService.getAccessibleCompaniesForPeople, the active-company policy used by the selector. Explicit scope=global requires superadministrator, even if the request is sent directly. Unbound themes are not assigned to a company by name. DatabaseSwitchListener still selects the tenant for these normal authenticated HTTP requests. In company scope, link details are disclosed only for companies in that user list; in authorized global scope, all link details may be shown. Other references contribute to a hidden count, so they still block deletion. Shared catalog deletion requires the existing superadministrator role; no new role or ownership model was introduced.

## Endpoints

- GET /theme-management?domain=...&page=1&scope=companies (or explicit authorized global): 50 themes per page, nextPage, usage counts, permitted link details, domain binding and action capabilities. The domain must match exactly and its company authority is evaluated independently of the UI-selected company.
- PUT /theme-management/domains/{id}/theme: integer themeId and explicit nullable expectedThemeId. Transaction locks the target theme and domain, refreshes the domain, checks its company authority and compares its current binding. Only its theme relation changes. Stale binding produces409.
- DELETE /theme-management/themes/{id}: superadministrator only. Transaction locks theme, explicitly loads PeopleDomain metadata plus other discoverable mapped owning references, rejects usage with409 and deletes only an unbound theme. Foreign-key failures, including concurrent bindings and legacy references, also produce409.

Implementation is in App controllers/services, leaving installed module entities and metadata untouched.

## UI and validation

Theme Manager keeps the existing creation, components, preview groups and editors. Gerenciar temas e vínculos opens a themed searchable catalog. It shows IDs, current domain application, usage details and blocked deletion. Apply confirmation identifies old/new theme, exact domain and its effect on all domain users; no automatic colors are added. Delete confirmation states permanence. The server rechecks permissions and links. Full colors are fetched before opening the existing color editor; catalog summaries cannot initialize blank color drafts.

Session/domain/lifetime guards prevent a stale dialog from dispatching writes or publishing results after context changes. Successful application uses the existing domain-theme reload path. Closing or cancelling does not send writes.

Automated tests use mocked ORM/entities/API only:10 PHP tests (34 assertions; existing People.setLanguage PHP8.5 deprecation and PHPUnit mock notices),59 frontend tests. Symfony container lint and route discovery passed. Local web export passed at /tmp/controle-theme-management. Real authenticated UI inspection is pending login in the conversation browser; no real PUT or DELETE is allowed for this verification.


## Company scope correction

Production metadata enumeration returned only the local app mapping and omitted lazily loaded module entities. The original simulated test supplied PeopleDomain in enumeration and missed this case. ThemeUsageService now explicitly requests PeopleDomain metadata and deduplicates it by class before scanning references. A regression test deliberately enumerates no module metadata and still finds the domain reference. This fixes the false “Sem vínculos” result for an applied theme and blocks deletion by the actual domain relation.

Default company scope applies even to superadministrators; a separate Gestão global switch is advertised by the server capability. Only explicit global scope shows deletion controls and unbound themes. API collection/item query extensions also restrict the original /themes routes for non-super users. Public /themes-colors.css remains the existing domain appearance transport. HTTP ORM write protection rejects unowned global creation/deletion by non-super users and palette edits without administration of every affected company, and protects direct PeopleDomain theme rebinding. Console/internal module flows and unrelated domain fields are preserved. No roles, owner columns or tenant bindings were created.

Validation after correction:21 backend tests (69 assertions; same installed-module PHP deprecation and mock notices),62 frontend tests; container lint, registered query extension/listener and web export /tmp/controle-theme-filter passed. No real PUT/DELETE, rebinding or theme deletion was tested. The initial restricted read-only database check failed during hostname resolution. A subsequent authorized read-only check succeeded outside that restriction: theme #11 had2 PeopleDomain references and ThemeUsageService returned count2 (hiddenCount2 without a signed-in HTTP user), writes0. Browser tab is at login, so real-account visual verification remains pending. Previous broad-catalog validation counts above describe the earlier iteration, not proof of this live-account case.

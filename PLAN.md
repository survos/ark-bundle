# SurvosArkBundle - Remaining Work Plan

Most of the bundle skeleton is done. What remains is mostly polish and correctness hardening.

## Done

- Survos rename complete (namespace, bundle class, extension, service keys).
- Core contracts, trait, listener, registry, controller, and console commands are present.
- PHPStan is wired in GitHub Actions.
- `.gitignore` and `README.md` added.
- `ArkQualifiableInterface` added.

## Remaining

1. Implement full qualifier resolution in `ArkRegistry`
   - Current behavior strips qualifier and falls back to parent/base ARK.
   - Add child lookup strategy for `ArkQualifiableInterface` entities.

2. Tighten Doctrine listener update logic
   - Rebind should track target URL changes, not ARK field changes.
   - Add deterministic comparison path for `getArkTarget()` before/after update.

3. Align `NoidMinterService` with real Noid backend
   - Replace file-backed placeholder implementation with `daniel-km/noid` integration.
   - Validate db handler configuration (`lmdb`, etc.) and failure modes.

4. Raise static analysis strictness
   - Move PHPStan toward level 9.
   - Remove temporary ignore for unused trait once tests/fixtures consume it.

5. Add tests
   - Unit: `NoidMinterService`, `ArkRegistry`.
   - Functional: `ArkRedirectController` (redirect, `?info`, `??`, 404, wrong NAAN).
   - Integration: `ArkDoctrineListener`.
   - Command tests for `ark:*` commands.

6. Final packaging cleanup
   - Add minimal release checklist (tagging, changelog, Packagist publish).
   - Verify README examples against working code paths.

## Notes

- NAAN apply link in README: https://n2t.net/e/naan_request
- For memory organizations, include application guidance and ensure scan metadata consistently carries their assigned numbering context.

# REFACTOR-PROGRESS.md

## FASE 1: Backend Resources → JSON:API (COMPLETADA)
- `AffectationSeverityResource` → `type: 'affectation-severity'`
- `DepartmentResource` → `type: 'department'` (uses `code` as `id`)
- `FacilityTypeResource` → `type: 'facility-type'`
- `IncidentTypeResource` → `type: 'incident-type'` (severities/needs in `relationships`)
- `MunicipalityResource` → `type: 'municipality'` (centroid in `attributes`)
- `NeedResource` → `type: 'need'` (severity in `relationships`)
- `OrganizationTypeResource` → `type: 'organization-type'`
- `PropertyTypeResource` → `type: 'property-type'`

## FASE 2: Service Classes (COMPLETADA)
### Nuevos services (10):
1. `AffectationService` — indexQuery, loadRelations, verify, update, destroy, destroyEvidence
2. `AffectationSeverityService` — create, update, delete
3. `CoverageZoneService` — index, create, update (with polygon normalization)
4. `FacilityTypeService` — index, create, update, delete
5. `IncidentTypeService` — index, create, loadRelations, update, delete (with needs sync)
6. `MunicipalityService` — index (with term/department filters)
7. `NeedService` — index, create (with name normalization)
8. `OrganizationTypeService` — index, create, update, delete
9. `PropertyTypeService` — index, create, update, delete
10. `AuthService` — login (credential validation + token generation)
11. `DepartmentService` — index (with term filter)

### Services refactorizados (3):
1. `FacilityService` — added index, show, uploadPhotos
2. `UserService` — added index (with role-based scoping)
3. `SearchReportService` — added index, show, update (with markFound + transactions)

## FASE 3: Controller Refactoring (COMPLETADA)
11 controllers refactored to inject services:
- `AffectationController` — delegates to AffectationService for verify/update/destroy/index/show
- `AffectationSeverityController` — delegates to AffectationSeverityService
- `CoverageZoneController` — delegates to CoverageZoneService (removed normalizePolygon)
- `FacilityTypeController` — delegates to FacilityTypeService
- `IncidentTypeController` — delegates to IncidentTypeService
- `MunicipalityController` — delegates to MunicipalityService
- `NeedController` — delegates to NeedService
- `OrganizationTypeController` — delegates to OrganizationTypeService
- `PropertyTypeController` — delegates to PropertyTypeService
- `DepartmentController` — delegates to DepartmentService
- `LoginController` — delegates to AuthService

3 controllers refactored with new service methods:
- `FacilityController` — delegates index/show/uploadPhotos to FacilityService
- `UserController` — delegates index to UserService
- `SearchReportController` — delegates index/show/update to SearchReportService

## FASE 4: PHPDoc + Transactions (COMPLETADA)
- All new services have complete PHPDoc blocks
- `AffectationService::verify()`, `AffectationService::update()`, `AffectationService::destroy()` use DB::transaction
- `IncidentTypeService::create()`, `IncidentTypeService::update()` use DB::transaction
- `SearchReportService::update()`, `SearchReportService::markFound()` use DB::transaction

## FASE 5: Frontend Types + API Client (COMPLETADA)
- Added 8 new Item types to `types.ts`: `AffectationSeverityItem`, `DepartmentItem`, `FacilityTypeItem`, `IncidentTypeItem`, `MunicipalityItem`, `NeedItem`, `OrganizationTypeItem`, `PropertyTypeItem`
- Added mapper functions in `api.ts` to transform JSON:API → flat types
- Updated 8 list endpoints in `api.ts` to use async mappers
- Zero tsc errors, zero eslint errors, build passes

## Verification
- Backend: `vendor/bin/pint --dirty --format agent` ✅
- Frontend: `tsc --noEmit` ✅
- Frontend: `eslint .` ✅ (only pre-existing warnings)
- Frontend: `next build` ✅

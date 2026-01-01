# Changelog

All notable changes to the Symbiosis Farm Management system will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2026-01-01] - SSO Authentication, Satellite Layers Fix, Crop Planning Integration

### Added
- **farmOS Crop Planning Module**: Enabled `farm_crop_plan` (v3.0.0-alpha3) for timeline visualization and planting records
  - Route: `/plan/add/crop` for creating crop plans
  - Route: `/plan/{id}/timeline/crop/plant_type` for plant type timeline view
  - Route: `/plan/{id}/timeline/crop/location` for location-based timeline view
  - Integration with succession planner via `?plan={id}` parameter
- **Succession Planner Crop Plan Integration**: New "Crop Plan Integration" field to link quick forms to farmOS crop plans
  - Quick forms automatically create planting records when plan ID provided
  - Timeline visualization automatically populated in farmOS
  - Season inheritance from crop plan to quick forms

### Fixed
- **SSO Authentication (Laravel → farmOS)**: OAuth tokens now include required OpenID Connect scopes
  - Changed `Passport::setDefaultScope([])` to `Passport::setDefaultScope(['openid', 'email', 'profile'])`
  - Fixed `/api/user` endpoint accessibility for farmOS OpenID Connect
  - SSO login now working end-to-end
- **farmOS Map Satellite Layers Disappearing**: Permanently fixed satellite layer persistence
  - Root cause: `config_update` module reverting to module defaults during cache rebuilds
  - Solution: Edited module install configs in `web/profiles/farm/modules/*/config/install/`
  - Modified files: `farm_map.map_type.dashboard.yml`, `asset_list.yml`, `locations.yml`, `geofield.yml`, `default.yml`
  - Added `- satellite_layers` to behaviors array in all map type defaults
  - Satellite layers now survive `drush cr` without requiring PHP script
  - Deleted obsolete `add_satellite_layers.php` script

### Changed
- **farmOS Configuration Management**: Config exports now properly preserve satellite layer settings
- **Succession Planner Quick Form URLs**: Now include `plan` parameter for automatic crop plan linking
- **Documentation**: Updated `.github/copilot-instructions.md`, `docs/SUCCESSION_PLANNER_README.md`, and main `README.md` with new features

### Technical Details
- **SSO Flow**: Laravel Passport OAuth2 server → farmOS OpenID Connect client
- **Scopes Required**: `openid`, `email`, `profile` (for user info), `farm_manager` (for permissions)
- **farmOS Database**: Direct queries for reads (50ms), API for writes only (performance optimization)
- **Crop Plan Workflow**: Create plan → Note ID → Enter in succession planner → Quick forms auto-link → Timeline auto-populates

### Developer Notes
- Config sync directory: `web/sites/default/files/config_sync/`
- Module defaults: `web/profiles/farm/modules/*/config/install/*.yml`
- Always edit module install configs, not just active config, to survive cache rebuilds
- farmOS crop planning uses `plan_record` entity with `crop_planting` bundle

---

## [2025-12-31] - Previous Work
(Historical changelog entries to be added as needed)

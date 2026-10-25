# Changelog

## v1.4.0 - 2026-10-25

### Added

- `SettingCreated`, `SettingUpdated`, and `SettingDeleted` events for global and model settings.
- Event delivery after successful settings database transaction commits, with cache invalidation before listeners run.
- Optional `DefersSettingEvents` contract for custom repository transaction coordination.
- PHP 8.5 compatibility jobs for Laravel 12 and 13, and a lowest-dependency Laravel 10 job.

## v1.3.0 - 2026-02-27

### Added

- Support for model settings.

## v1.2.0 - 2026-03-17

### Added
- Tests
- Laravel 13 Support

## v1.1.0 - 2026-02-26

### Added
- Blade directives:
    - @hasSetting
    - @hasFlag
    - @setting

## v1.0.0 - 2026-02-25

### Initial Release
- Database-backed settings
- Feature flags
- Facades
- Helper functions
- Artisan commands

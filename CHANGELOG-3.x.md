# Change Log for OXID developer tools component

## v8.0.0-alpha.2 - 2026-02-12
*Compilation release*

### Changed
- `oe:database:reset` now listens for `DatabaseNotEmptyException`

## v3.0.0-alpha.1 - 2025-02-03

### Removed
- Deprecated theme activation command class `ThemeActivateCommand`
- Dependency on the `Facts` component

### Changed
- `oe:database:reset` command now  fetches DB connection parameters from the Symfony container.
All corresponding command-line parameters were removed
- Update `DatabaseConfiguration` namespace

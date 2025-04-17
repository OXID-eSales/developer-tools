# Change Log for OXID developer tools component

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v.3.0.0] - unreleased

### Removed
- Deprecated theme activation command class `ThemeActivateCommand`
- Dependency on the `Facts` component

### Changed
- `oe:database:reset` command now  fetches DB connection parameters from the Symfony container.
All corresponding command-line parameters were removed
- Update `DatabaseConfiguration` namespace

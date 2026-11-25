# Maintenance upgrades

## Core 2.3.1

Core 2.3.1 is a patch release for the 2.x API. No new configuration, migrations, or application code changes are required when upgrading from 2.3.0.

```bash
composer update codebyray/laravel-review-rateable --with-all-dependencies
composer audit
```

If your root constraint pins an older release, update it to `^2.3` first. Review the dependency changes and run your application tests before deploying.

Deleting a review through Eloquent now queries all its image records rather than trusting a previously loaded relation. Files are still deleted only after the database transaction commits, and `images.delete_files_on_delete=false` still preserves them. This prevents new orphaned files; it does not discover or remove files orphaned by earlier releases. Bulk query deletes bypass Eloquent model events, so use the review/model APIs when automatic file cleanup is required.

Do not republish migrations or configuration to upgrade. Publishing reuses an existing matching migration filename. Avoid `--force` unless you intend to replace application changes. If older releases already created duplicate migrations, compare them with your migration history before removing any unused duplicate; do not rerun migrations that created existing tables.

## Compatibility and security

The 2.x line retains PHP 8.1+ and Laravel 10–13 compatibility subject to Laravel's own PHP requirements. For maintained deployments, use supported PHP and the latest Laravel 12/13 security patches. Laravel 10/11 are upstream end-of-life, and PHP 8.1 is a legacy target.

CI keeps historical compatibility lanes separate from security validation: only Laravel 10/11 resolution bypasses Composer advisory blocking. Maintained lanes must pass `composer audit`. Never copy that bypass into an application deployment. Composer lock files are intentionally not committed for this library; CI resolves fresh dependencies per supported framework lane.

## Upgrading from Core 1.x

Version 2 is an architectural rewrite. Follow the [installation and migration guidance](https://reviewrateable.com/docs/core/installation) and plan a data migration before using the 2.x trait and service APIs.

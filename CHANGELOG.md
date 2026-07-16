# Changelog

All notable changes to `laravel-review-rateable` will be documented here.

## 2.1.3 - 2026-07-15

### What's Changed
* **Performance**: Massive performance optimization for all `averageRating` methods. Replaced memory-heavy PHP collection loops with native database-level aggregate queries (`AVG`, `COUNT`), effectively eliminating potential N+1 memory issues on models with many reviews.
* **Refactor**: Added strict PHP return type hints to the `ReviewRateable` trait for improved IDE support and stability.
* **Bug Fixes**: Corrected method naming inconsistencies in `ReviewRateableService` to ensure proper delegation to the model.
* **Testing**: Added comprehensive test suite via Orchestra Testbench and Pest to verify mathematical accuracy of rating averages and service layer operations.

## 2.1.2 - 2026-02-10

### What's Changed
* **Refactor**: Improved internal handling of morph relationships to ensure better compatibility with newer Laravel versions.
* **Configuration**: Added support for Laravel 13.

## 2.1.1 - 2025-11-20

### What's Changed
* **Features**: Added `user_id` support to the `addReview` method to allow tracking of reviewer identities.
* **Documentation**: Updated internal README instructions for easier package setup.

## 2.0 - 2025-04-07

### What's Changed from v1

* Complete rewrite: Complete rewrite of v1. New v2 allows more control over you reviews/ratings.
* Define custom rating keys, labels, and value boundaries (min/max) via a config file.
* Organize ratings by department (e.g. default, sales, support) with their own criteria.

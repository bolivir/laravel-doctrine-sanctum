# Upgrade Guide

## Upgrading To 3.0 From 2.x

### Minimum Versions

The following dependency versions have been updated:

- The minimum PHP version is now v8.0.2x
- The minimum Laravel version is now v9.21

### New `expires_at` Column
Make sure to run a migration to add the `expires_at` column to your `personal_access_tokens` table.

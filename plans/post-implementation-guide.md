# Post-Implementation Guide

> **Status**: Living Document  
> **Date**: 2026-09-07  
> **Audience**: Consuming app developers and library maintainers  
> **Purpose**: Common operational tasks, troubleshooting, and best practices after library changes are deployed

---

## 1. Vendor Override Awareness

### 1.1 How `resources/views/vendor/qf/` Works

The UI Library uses Laravel's package view publishing system. When a consuming app runs:

```bash
php artisan vendor:publish --tag=qf-core-views
```

Laravel copies the library's Blade views from `src/Resources/views/` into the consuming app's `resources/views/vendor/qf/` directory. Once published, Laravel's view resolution gives the **published copy absolute priority** over the library source.

**Priority chain** (highest to lowest):
1. `resources/views/vendor/qf/` — Published override (consuming app)
2. `src/Resources/views/` — Library source (this workspace)

This means: **if a published override exists, changes to the library source Blade file are silently ignored by the consuming app.**

### 1.2 How to Check for Overrides

```bash
# List all published QF views
ls -la resources/views/vendor/qf/

# Recursively list all overrides
find resources/views/vendor/qf/ -type f -name "*.blade.php"

# Check if a specific view has an override
ls resources/views/vendor/qf/livewire/access-control-manager.blade.php
```

### 1.3 When to Update Overrides

| Scenario | Action |
|----------|--------|
| Library Blade file changed (bug fix, new feature) | Delete the override and re-publish, or manually merge changes |
| Override was for a one-time customization | Consider using a different approach (component extension, slot) instead of a full view override |
| Override is intentional and permanent | Document it in the consuming app's README; add a CI check that alerts when the source view changes |
| Unsure if override is needed | Delete it and test — if the library default works, the override was unnecessary |

### 1.4 How to Re-Publish Views

```bash
# Delete the existing override
rm resources/views/vendor/qf/livewire/access-control-manager.blade.php

# Re-publish from the updated library source
php artisan vendor:publish --tag=qf-core-views --force

# Always clear view cache after
php artisan view:clear
```

### 1.5 Preventing Accidental Overrides

- **Avoid `vendor:publish --all`** — This publishes everything, including views you may not intend to override. Use specific tags instead.
- **Document intentional overrides** — Add a comment at the top of each published view explaining why it was overridden and when.
- **Consider alternatives to full view overrides**:
  - Use Blade `@section` / `@yield` if the library view provides extension points
  - Use component slots if the library component supports them
  - Extend the Livewire component class instead of overriding the view

---

## 2. Cache Management

### 2.1 The Golden Rule

**After any library update, always run:**

```bash
php artisan optimize:clear
```

This single command clears all Laravel caches. It is the safest default after any `composer update`, `git pull` of library changes, or manual file modification.

### 2.2 What Each Cache Clear Does

| Command | Clears | When to Use |
|---------|--------|-------------|
| `php artisan optimize:clear` | All caches (views, routes, config, app cache, compiled classes, events) | **Default after any library update** |
| `php artisan view:clear` | `storage/framework/views/*.php` (compiled Blade templates) | After Blade template changes only |
| `php artisan config:clear` | `bootstrap/cache/config.php` | After config file changes only |
| `php artisan route:clear` | `bootstrap/cache/routes-v7.php` | After route file changes only |
| `php artisan cache:clear` | General cache store (Redis/Memcached/file) | After data cache changes |
| `php artisan event:clear` | Cached event listeners | After event registration changes |

### 2.3 Development vs. Production

| Environment | After Library Update | Normal Operation |
|-------------|---------------------|------------------|
| **Development** | `php artisan optimize:clear` | No caching (default) |
| **Production** | `php artisan optimize:clear` then `php artisan optimize` | `php artisan optimize` (pre-compiled) |

In production, you typically want caches **enabled** for performance (`php artisan optimize`). However, immediately after a library update, you must clear first (`optimize:clear`) and then re-optimize (`optimize`).

### 2.4 Stale View Cache Symptoms

If you modify a Blade file and nothing changes, the compiled view is stale. Symptoms include:

- Old HTML structure still rendering after Blade changes
- New Blade directives not being processed
- Removed sections still appearing in the output
- Error messages referencing old line numbers

**Fix**: `php artisan view:clear`

> **⚠️ Important**: If `view:clear` doesn't fix it, check for a published vendor override at `resources/views/vendor/qf/`. The override takes priority over the library source, so clearing the view cache won't help if the override itself is stale.

---

## 3. Permission Panel Troubleshooting

### 3.1 Common Issues and Fixes

#### Issue: Permissions not appearing in the AccessControlManager UI

**Checklist**:
1. **Is the permission defined in a `permissions.php` config file?**  
   Check `src/Core/{Module}/Config/permissions.php` for the permission definition.
2. **Is it a model-based permission?**  
   Model-based permissions are auto-discovered by [`ModelDiscovery`](src/Services/AccessControl/ModelDiscovery.php). Verify the model file exists and has the expected permission annotations.
3. **Is it an `extra` permission?**  
   Extra permissions must be defined under the `extra` key in `permissions.php`. Each entry needs `id`, `label`, `description`, and `icon`.
4. **Has `AccessControlPermissionSeeder` been run?**  
   Permissions must exist in the database. Run the seeder: `php artisan db:seed --class=AccessControlPermissionSeeder`
5. **Is there a published vendor override masking the fix?**  
   Check `resources/views/vendor/qf/livewire/access-control-manager.blade.php`. If it exists, delete it and re-publish.

#### Issue: Toggle buttons don't persist changes

**Checklist**:
1. **Is `stateSyncMethod="method"` present on the `ToggleButtonGroup`?**  
   The [`ToggleButtonListener`](src/Listeners/ToggleButtonListener.php) requires this attribute.
2. **Does `:data` include `selectedScope`?**  
   The JSON payload must have a `selectedScope` key identifying the permission being toggled.
3. **Check browser console for JavaScript errors** — The `ToggleButtonGroup` uses Alpine.js; a JS error can prevent the event from dispatching.
4. **Check Laravel logs** — `storage/logs/laravel.log` may contain permission-related errors.

#### Issue: Navigation items or row actions not visible

**Checklist**:
1. **Is the feature flag enabled?**  
   Check `config/ui-library.php` → `features.multi_company` is `true`.
2. **Does the user's role have the required permission?**  
   Check the role-permission mapping in the database.
3. **Has config cache been cleared?**  
   Run `php artisan config:clear`.
4. **Is the navigation item gated behind a feature flag?**  
   [`NavigationManager::loadModuleNavItems()`](src/Services/Navigation/NavigationManager.php:384) filters items based on feature flags.

### 3.2 Debugging the Permission Pipeline

The full pipeline for rendering a permission in the UI:

```
permissions.php config
    ↓
AccessControlManager::manageAccessControl()
    ↓ (reads model files via ModelDiscovery + extra from config)
$models array
    ↓
access-control-manager.blade.php
    ↓ (renders ToggleButtonGroup for each permission)
ToggleButtonGroup Blade component
    ↓ (Alpine.js handles client-side toggle)
ToggleButtonListener
    ↓ (persists change via Livewire method)
Database (role_has_permissions table)
```

When debugging, verify each step in the pipeline:
1. **Config**: Is the permission in `permissions.php`?
2. **Backend**: Is `manageAccessControl()` collecting it? (dd the `$models` array)
3. **Template**: Is the Blade section rendering? (check for conditionals hiding it)
4. **Component**: Are the `ToggleButtonGroup` attributes correct?
5. **Listener**: Is `ToggleButtonListener` receiving the event? (check Livewire network tab)
6. **Database**: Is the permission record being created/updated?

---

## 4. Library Update Checklist

Follow these steps in order when pulling library changes into a consuming app:

### 4.1 Pre-Update

- [ ] **Review the library changelog** — Understand what changed and why
- [ ] **Check for breaking changes** — Look for config key renames, method signature changes, removed classes
- [ ] **Back up the database** — Especially before migration changes
- [ ] **Identify published overrides** — Run `find resources/views/vendor/qf/ -type f` to list all overrides that may need updating

### 4.2 Update

- [ ] **Pull library changes**: `composer update quickerfaster/ui-library` (or `git pull` if using a path repository)
- [ ] **Run migrations** (if any): `php artisan migrate`
- [ ] **Run seeders** (if any): `php artisan db:seed --class=AccessControlPermissionSeeder`
- [ ] **Clear all caches**: `php artisan optimize:clear`
- [ ] **Re-publish views if needed**: If library Blade files changed and you have overrides, delete and re-publish affected views

### 4.3 Post-Update Verification

- [ ] **Check for updated published views**: Compare `resources/views/vendor/qf/` files against library source
- [ ] **Verify navigation**: Check that sidebar items and row actions appear correctly
- [ ] **Verify permissions**: Open the AccessControlManager UI and confirm all permissions render
- [ ] **Test toggle functionality**: Toggle a permission on/off and verify it persists after page refresh
- [ ] **Check for errors**: Review `storage/logs/laravel.log` for any new errors
- [ ] **Run automated tests** (if available): `php artisan test`

### 4.4 Production Deployment

- [ ] **Deploy during low-traffic window** — Cache clears cause a brief performance dip
- [ ] **Run `php artisan optimize:clear` then `php artisan optimize`** — Clear then re-build production caches
- [ ] **Restart queue workers**: `php artisan queue:restart` (if using queues)
- [ ] **Monitor error rates** for 15-30 minutes after deployment
- [ ] **Verify critical paths**: Login, dashboard, company switcher, permission management

---

## 5. Debugging Published Views

### 5.1 How to Determine if a Published Override is Masking Library Changes

**Step 1: Check if an override exists**

```bash
# List all published QF views
find resources/views/vendor/qf/ -type f -name "*.blade.php"
```

If the file you're debugging appears in this list, an override exists.

**Step 2: Compare the override against the library source**

```bash
# Diff the published override against the library source
diff resources/views/vendor/qf/livewire/access-control-manager.blade.php \
     vendor/quickerfaster/ui-library/src/Services/AccessControl/resources/views/livewire/access-control-manager.blade.php
```

If the files differ, the override is masking library changes.

**Step 3: Determine if the override is intentional**

Check the override's git history:
```bash
git log --oneline resources/views/vendor/qf/livewire/access-control-manager.blade.php
```

If the override was published as a one-time customization that is no longer needed, delete it. If it was an intentional customization, manually merge the library changes into the override.

**Step 4: Test without the override**

```bash
# Temporarily move the override
mv resources/views/vendor/qf/livewire/access-control-manager.blade.php \
   resources/views/vendor/qf/livewire/access-control-manager.blade.php.bak

# Clear view cache
php artisan view:clear

# Test the page — does it work with the library default?
```

If the library default works correctly, the override is unnecessary and can be deleted permanently.

### 5.2 Common Override Scenarios

| Scenario | Diagnosis | Resolution |
|----------|-----------|------------|
| Override exists but is identical to library source | Published at some point, never updated | Delete the override — it serves no purpose |
| Override has minor customizations | Intentional tweaks to labels, layout, etc. | Manually merge library changes into the override |
| Override is completely different from library source | Heavy customization | Consider extending the component class instead of overriding the view |
| Override is masking a bug fix | Published before the fix was applied | Delete and re-publish, or manually apply the fix to the override |

### 5.3 Preventing Future Override Issues

1. **Add a CI check** that compares published views against library source and alerts on divergence:
   ```bash
   # Example CI script
   for view in $(find resources/views/vendor/qf/ -type f -name "*.blade.php"); do
       lib_view="vendor/quickerfaster/ui-library/src/Resources/views/${view#resources/views/vendor/qf/}"
       if [ -f "$lib_view" ] && ! diff -q "$view" "$lib_view" > /dev/null; then
           echo "WARNING: Published view differs from library source: $view"
       fi
   done
   ```

2. **Document all intentional overrides** in a `resources/views/vendor/qf/README.md`:
   ```markdown
   # Published View Overrides
   
   | View | Reason | Last Synced |
   |------|--------|-------------|
   | `livewire/access-control-manager.blade.php` | Custom permission layout | 2026-09-07 |
   ```

3. **Prefer extension over override** — Before publishing a view, ask: "Can I achieve this by extending the Livewire component or using a Blade slot instead?"

---

## 6. Quick Reference

### 6.1 Common Commands

```bash
# Cache management
php artisan optimize:clear          # Clear all caches (post-update default)
php artisan view:clear              # Clear compiled Blade views only
php artisan config:clear            # Clear config cache only
php artisan optimize                # Build all caches (production)

# View publishing
php artisan vendor:publish --tag=qf-core-views              # Publish all QF views
php artisan vendor:publish --tag=qf-core-views --force      # Force re-publish (overwrite)
find resources/views/vendor/qf/ -type f -name "*.blade.php" # List all overrides

# Permission seeding
php artisan db:seed --class=AccessControlPermissionSeeder   # Seed all permissions

# Debugging
php artisan route:list              # List all registered routes
php artisan config:show ui-library  # Show resolved library config
composer show quickerfaster/ui-library --latest  # Check installed version
```

### 6.2 Key Files Reference

| File | Purpose |
|------|---------|
| [`src/Config/ui-library.php`](src/Config/ui-library.php) | Library configuration (feature flags, multitenancy, modules) |
| [`src/Services/AccessControl/AccessControlManager.php`](src/Services/AccessControl/AccessControlManager.php) | Permission management Livewire component |
| [`src/Services/AccessControl/AccessControlPermissionSeeder.php`](src/Services/AccessControl/AccessControlPermissionSeeder.php) | Permission seeder |
| [`src/Services/AccessControl/ModelDiscovery.php`](src/Services/AccessControl/ModelDiscovery.php) | Model-based permission discovery |
| [`src/Listeners/ToggleButtonListener.php`](src/Listeners/ToggleButtonListener.php) | Toggle button event handler |
| [`src/Services/Navigation/NavigationManager.php`](src/Services/Navigation/NavigationManager.php) | Navigation item loading and feature flag gating |
| [`src/Http/Livewire/DataTables/DataTable.php`](src/Http/Livewire/DataTables/DataTable.php) | DataTable with moreActions filtering |
| [`plans/library-relationship-bugs.md`](plans/library-relationship-bugs.md) | Known bugs and fixes |
| [`plans/consuming-app-multi-company-implementation.md`](plans/consuming-app-multi-company-implementation.md) | Multi-company implementation guide |
| [`plans/multi-company-user-assignment-recommendation.md`](plans/multi-company-user-assignment-recommendation.md) | Multi-company architecture recommendation |
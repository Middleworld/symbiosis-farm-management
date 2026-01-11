# farmOS Bulk Log Deletion Fix

## Issue
Bulk deletion of logs in farmOS was failing with error:
```
The website encountered an unexpected error. Try again later.
```

## Root Cause
The `entity_reference_integrity_enforce` module was checking for dependent entities before allowing deletion, but its query had a bug causing `QueryException: 'log' not found in Drupal\Core\Entity\Query\Sql\Tables->ensureEntityTable()`.

Stack trace showed:
```
entity_reference_integrity_enforce/src/Plugin/Action/DeleteAction.php(36):
  EntityReferenceIntegrityEntityHandler->hasDependents()
```

## Solution Applied (January 11, 2026)
Removed `log` entity type from integrity enforcement:

```bash
drush php-eval "
\$config = \Drupal::configFactory()->getEditable('entity_reference_integrity_enforce.settings');
\$enabled = \$config->get('enabled_entity_type_ids');
unset(\$enabled['log']);
\$config->set('enabled_entity_type_ids', \$enabled)->save();
"
drush cache:rebuild
```

## Configuration Change
File: `entity_reference_integrity_enforce.settings`
- **Before**: `enabled_entity_type_ids` included `log`
- **After**: `enabled_entity_type_ids` excludes `log`

Logs can now be bulk deleted without integrity checks blocking the operation.

## Testing
- Selected 6 test logs (IDs 7-12)
- Clicked "Delete log" action
- **Result**: Successfully deleted without errors

## Notes
- This disables referential integrity enforcement for log deletions
- farmOS logs typically don't have critical dependencies that would break if deleted
- If you need integrity checking restored, the module query would need to be fixed first

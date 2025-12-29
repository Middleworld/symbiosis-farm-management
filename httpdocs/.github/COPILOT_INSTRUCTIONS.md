# GitHub Copilot Instructions for Middleworld Farms Admin

## CRITICAL: Always Check Git History FIRST

⚠️ **BEFORE implementing ANY feature or fix, ALWAYS:**

1. **Check if the code already exists** using:
   ```bash
   git log --oneline --all --grep="<feature_name>" -10
   git show <commit_hash>:<file_path>
   ```

2. **Search for existing implementations**:
   ```bash
   grep -r "function <functionName>" .
   git log --all --oneline --follow <file_path>
   ```

3. **Check current git diff**:
   ```bash
   git diff HEAD <file_path>
   ```

4. **NEVER assume code is missing** - if a user reports something "stopped working", it likely means:
   - The code EXISTS but broke
   - Something changed that affected it
   - NOT that the code was never there

## Performance Requirements

### Database Access Rules

1. **ALWAYS use local mirrored database tables for lookups**:
   - ✅ Use `PlantVariety::where()` (local DB - fast ~50ms)
   - ❌ NEVER use `$this->farmOSApi->getVariety()` for lookups (API - slow 2-30 seconds)

2. **FarmOS API is ONLY for**:
   - Creating new records
   - Updating existing records
   - Initial sync/import operations
   - NOT for read operations in user-facing features

3. **Local database tables that mirror FarmOS**:
   - `plant_varieties` (PlantVariety model)
   - `plant_assets` (PlantAsset model)
   - `seeding_logs` (SeedingLog model)
   - `harvests` (Harvest model)
   - `field_beds` (FieldBed model)

### Response Time Targets

- Page loads: < 2 seconds
- API endpoints: < 200ms
- Database queries: < 50ms
- AI operations: < 3 seconds (acceptable for background operations)

## Laravel Query Best Practices

### ❌ AVOID: Chained orWhere() for Different Fields

```php
// BAD - Returns ANY matching record (first in database)
$variety = PlantVariety::where('farmos_id', $id)
    ->orWhere('farmos_tid', $id)
    ->orWhere('id', $id)
    ->first();
```

**Problem**: Laravel's `orWhere()` creates `WHERE field1 = X OR field2 = X OR field3 = X`, which returns the FIRST record that matches ANY condition, not necessarily the one you want.

### ✅ USE: Sequential Queries with Priority

```php
// GOOD - Check each field separately with priority order
$variety = PlantVariety::where('farmos_id', $id)->first();

if (!$variety) {
    $variety = PlantVariety::where('farmos_tid', $id)->first();
}

if (!$variety) {
    $variety = PlantVariety::where('id', $id)->first();
}

if (!$variety) {
    $variety = PlantVariety::whereRaw('LOWER(name) = ?', [strtolower($id)])->first();
}
```

**Why**: Ensures exact matches with proper fallback logic.

## Git Workflow

### Before Making Changes

1. **Check current state**:
   ```bash
   git status
   git diff
   ```

2. **Review recent history**:
   ```bash
   git log --oneline -20
   git log --grep="<relevant_keyword>" -10
   ```

3. **Check if file has working version**:
   ```bash
   git log --follow <file_path>
   git show <commit>:<file_path>
   ```

### When Things Break

1. **First response should be**: "Let me check the git history to see when this was working"
2. **Compare with working version**: `git diff <last_working_commit> HEAD -- <file>`
3. **Consider restoring**: `git checkout <commit> -- <file>` if breaking changes were made
4. **Then fix incrementally** rather than rewriting

## Code Review Checklist

Before suggesting code changes:

- [ ] Checked git history for existing implementation
- [ ] Verified the code doesn't already exist
- [ ] Confirmed using local DB not FarmOS API for reads
- [ ] Used sequential queries not chained orWhere()
- [ ] Tested that changes don't break existing functionality
- [ ] Response times meet performance targets
- [ ] Cleared caches after changes (`php artisan cache:clear && curl opcache-clear.php`)

## Common Mistakes to Avoid

1. ❌ Replacing working code without checking git history
2. ❌ Using FarmOS API for read operations in user-facing features
3. ❌ Chaining orWhere() for different field lookups
4. ❌ Assuming code is missing when user says "stopped working"
5. ❌ Not clearing OPcache after PHP changes
6. ❌ Breaking existing functionality while adding new features

## Success Patterns

1. ✅ Check git history FIRST, implement SECOND
2. ✅ Use local mirrored database for all read operations
3. ✅ Sequential queries with proper priority order
4. ✅ Incremental fixes to existing working code
5. ✅ Cache clearing after every backend change
6. ✅ Performance testing (compare before/after response times)

---

**Remember**: If a user says something "stopped working", it means it WAS working. Find the working version first, then fix what broke it.

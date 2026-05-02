# Kojaya ERP - Architecture Decision Records (ADR)

## 📋 What is ADR?

Architecture Decision Records (ADR) document important architectural decisions made during the project. Each ADR captures:
- **Context** - Why was this decision needed?
- **Decision** - What did we decide?
- **Consequences** - What does this mean for the project?

---

## 🎯 ADR-001: Laravel 12 vs Laravel 11

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Project needed a modern PHP framework with long-term support and latest features. Options were:
- Laravel 11 (stable, proven)
- Laravel 12 (latest, cutting-edge)

### Decision
**Chose Laravel 12** (latest version as of February 2026)

**Rationale:**
- Latest PHP 8.2+ features (constructor promotion, readonly properties, enums)
- Modernized structure (no `app/Http/Kernel.php`, middleware in `bootstrap/app.php`)
- Better performance with Laravel Boost
- Future-proof with latest LTS support
- Access to new features (improved queue system, better error handling)

**Consequences:**

**Positive:**
- Modern PHP features improve code quality
- Better performance with Boost
- Smoother upgrade path for next 2-3 years
- Access to latest Laravel ecosystem

**Negative:**
- Less community knowledge/experience available
- Some packages may not be compatible yet
- Debugging resources more limited

---

## 🎯 ADR-002: Vue 3 + Inertia.js vs Filament PHP

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Admin panel needed for ERP system. Options were:
- Filament PHP v3 (admin panel framework)
- Vue 3 + Inertia.js (custom SPA)

### Decision
**Chose Vue 3 + Inertia.js** (custom SPA)

**Rationale:**
- More flexibility for custom UX requirements
- Better mobile responsiveness (important for future mobile apps)
- Reusable Vue components across web and mobile
- TypeScript support for type safety
- Better developer experience (hot reload, fast builds)
- Indonesian compliance requires highly custom UIs

**Trade-offs:**

**Positive:**
- Custom UX tailored to Indonesian cooperative needs
- Shared components between web and future mobile apps
- Better mobile/responsive design
- TypeScript catches bugs at compile time
- Modern development workflow (HMR, fast builds)

**Negative:**
- Initial development slower (no CRUD auto-generation)
- More code to maintain
- Need to build common UI components from scratch
- 2-3 months additional development time

**Mitigation:**
- Use Reka UI (headless components) to speed up development
- Use shadcn-vue for pre-built components
- Create internal component library
- Document patterns and reuse heavily

---

## 🎯 ADR-003: PostgreSQL vs MySQL

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Primary database choice for ERP system. Options:
- MySQL 8.0 (most common for Laravel)
- PostgreSQL 13+ (enterprise features)

### Decision
**Chose PostgreSQL** as primary database

**Rationale:**
- Better for complex queries (JOINs, subqueries)
- Superior JSON support (for flexible schema)
- Better performance at scale
- More reliable transaction handling
- Better indexing options
- Full-text search built-in
- Easier to migrate to cloud providers (AWS RDS, Google Cloud SQL)

**Consequences:**

**Positive:**
- Better query performance for complex reports
- JSON fields for flexible data (audit logs, metadata)
- More reliable for high-traffic scenarios
- Easier scaling path

**Negative:**
- Slightly higher learning curve
- Some Laravel packages optimized for MySQL
- Hosting costs slightly higher

---

## 🎯 ADR-004: UUID vs Auto-Increment IDs

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Primary key strategy for all database tables. Options:
- Auto-increment integers (Laravel default)
- UUID (Universally Unique Identifiers)

### Decision
**Chose UUID primary keys** for all 72 models

**Rationale:**
- **Security:** Non-guessable IDs prevent enumeration attacks
- **Distributed Systems:** Easier to merge data from multiple sources
- **API-First:** Safe to expose UUIDs in API endpoints
- **Multi-tenancy:** Safer for shared databases
- **Privacy:** Harder to guess record counts (e.g., employee count)

**Implementation:**
```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasFactory, HasUuids;
    // UUID primary key is automatic
}
```

**Consequences:**

**Positive:**
- Improved security posture
- Safe API endpoints (no ID enumeration)
- Better support for distributed systems
- Easier data migration/merging

**Negative:**
- Larger indexes (UUIDs are 36 chars vs integers)
- Slightly slower JOINs
- Can't sort by ID to get insertion order
- More complex debugging (human-unfriendly IDs)

**Mitigation:**
- Use `created_at` timestamp for insertion order
- Optimize indexes for UUID columns
- Add `ulid` or `ordered_uuid` for sortable IDs (future)

---

## 🎯 ADR-005: Sanctum vs Passport for API Auth

**Status:** ✅ Accepted
**Date:** March 1, 2026
**Deciders:** Development Team

### Context
API authentication for mobile apps. Options:
- Laravel Passport (OAuth2)
- Laravel Sanctum (API tokens)

### Decision
**Chose Laravel Sanctum** for API authentication

**Rationale:**
- **Simpler:** No need for full OAuth2 complexity
- **Lighter:** Smaller footprint, faster performance
- **Mobile-friendly:** Token-based auth perfect for mobile apps
- **SPA-support:** Can handle both token and session auth
- **Easier integration:** Simpler for mobile developers

**Implementation:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
```

**Consequences:**

**Positive:**
- Simple token generation and management
- Good performance
- Easy for mobile developers to use
- Supports both token and session auth

**Negative:**
- Tokens don't expire by default (security concern)
- No OAuth2 benefits (refresh tokens, scopes)
- Manual token management required

**Mitigation:**
- Add token expiration in v1.1
- Implement token rotation
- Add token abilities (permissions)
- Monitor token usage

---

## 🎯 ADR-006: Service Layer Pattern

**Status:** ✅ Accepted
**Date:** March 5, 2026
**Deciders:** Development Team

### Context
Business logic organization approach. Options:
- Fat Controllers (logic in controllers)
- Service Layer (separate business logic classes)
- Domain-Driven Design (complex)

### Decision
**Chose Service Layer Pattern**

**Rationale:**
- **Separation of Concerns:** Controllers handle HTTP, Services handle logic
- **Reusability:** Services can be reused across controllers
- **Testability:** Easier to unit test services
- **Maintainability:** Business logic in one place
- **Clarity:** Clear distinction between HTTP and business logic

**Implementation:**
```php
// Controller
class PayrollController extends Controller
{
    public function generate(GeneratePayrollRequest $request)
    {
        $payroll = $this->service->calculate(
            $request->validated(),
            $request->user()
        );
        return new PayrollResource($payroll);
    }
}

// Service
class PayrollCalculatorService
{
    public function calculate(array $data, User $user): Payroll
    {
        // Complex calculation logic
    }
}
```

**Examples:**
- `PayrollCalculatorService` - Payroll calculations
- `Pph21TerService` - Tax calculations
- `BpjsCalculationService` - BPJS calculations
- `NotificationService` - Notifications
- `AuditLogService` - Audit logging

**Consequences:**

**Positive:**
- Clean, focused controllers
- Reusable business logic
- Easier to test services
- Single source of truth for calculations

**Negative:**
- More files to maintain
- Indirection can be confusing initially
- Need to understand service layer pattern

---

## 🎯 ADR-007: API Versioning Strategy

**Status:** ✅ Accepted
**Date:** March 15, 2026
**Deciders:** Development Team

### Context
API versioning approach for mobile apps. Options:
- URL-based versioning (`/api/v1/resource`)
- Header-based versioning (`Accept: application/vnd.api.v1+json`)
- No versioning (breaking changes require new API)

### Decision
**Chose URL-based versioning** with path prefix

**Rationale:**
- **Explicit:** Version visible in URL
- **Simple:** Easy to implement and understand
- **Cachable:** Different versions can have different cache keys
- **Clear:** Mobile developers know which version they're using

**Implementation:**
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::get('/members', [CooperativeMemberApiController::class, 'index']);
    Route::post('/members', [CooperativeMemberApiController::class, 'store']);
});
```

**Consequences:**

**Positive:**
- Clear version separation
- Easy to deprecate old versions
- Better cache control
- Simpler for mobile developers

**Negative:**
- Need to maintain multiple versions (eventually)
- Route definitions more verbose

**Future:**
- Add header-based versioning for internal APIs
- Consider GraphQL for flexible versioning

---

## 🎯 ADR-008: Soft Deletes vs Hard Deletes

**Status:** ✅ Accepted
**Date:** March 20, 2026
**Deciders:** Development Team

### Context
Data deletion strategy. Options:
- Hard deletes (permanently remove data)
- Soft deletes (mark as deleted)

### Decision
**Chose Soft Deletes** for most models

**Rationale:**
- **Audit Trail:** Preserve data for compliance
- **Recoverability:** Can restore accidentally deleted records
- **Reporting:** Historical reports need old data
- **Compliance:** Indonesian regulations require data retention

**Implementation:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    // deleted_at column added automatically
}
```

**Consequences:**
- Need to query with `whereNull('deleted_at')` or use `withTrashed()`
- Database grows larger over time
- Need cleanup jobs for old deleted records

---

## 🎯 ADR-009: TypeScript vs JavaScript

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
Frontend language choice. Options:
- JavaScript (simpler, faster)
- TypeScript (type-safe)

### Decision
**Chose TypeScript with strict mode**

**Rationale:**
- **Type Safety:** Catch bugs at compile time, not runtime
- **Better IDE Support:** Autocompletion, refactoring tools
- **Self-Documenting:** Types act as inline documentation
- **Team Scale:** More developers = more value from types
- **Confidence:** Fewer runtime errors in production

**Consequences:**

**Positive:**
- Fewer runtime errors
- Better developer experience
- Safer refactoring
- Self-documenting code

**Negative:**
- Initial setup more complex
- Learning curve for JavaScript developers
- Compilation step required
- Type definitions need maintenance

---

## 🎯 ADR-010: Tailwind CSS v4 vs v3

**Status:** ✅ Accepted
**Date:** February 26, 2026
**Deciders:** Development Team

### Context
CSS framework version choice. Options:
- Tailwind CSS v3 (stable, proven)
- Tailwind CSS v4 (latest, beta)

### Decision
**Chose Tailwind CSS v4** (latest version as of Feb 2026)

**Rationale:**
- **New Features:** Native CSS nesting, @layer support
- **Better Performance:** Oxide compiler (Rust-based, 10x faster)
- **Smaller Bundle Size:** Tree-shaking of unused utilities
- **Future-Proof:** Latest features and improvements

**Consequences:**

**Positive:**
- Faster build times
- Smaller CSS bundles
- Modern CSS features
- Better developer experience

**Negative:**
- Beta software (potential bugs)
- Less documentation/examples available
- Some plugins may not be compatible yet

---

## 🔮 Future Decisions (Pending)

### ADR-011: Payment Gateway Provider
**Status:** ⏳ Pending (Phase 4)
**Options:** Midtrans vs Xendit vs Stripe
**Timeline:** July 2026

### ADR-012: Mobile App Framework
**Status:** ⏳ Pending (Phase 3)
**Options:** Flutter vs React Native vs Kotlin Native
**Timeline:** May 2026

### ADR-013: Real-time Updates
**Status:** ⏳ Pending (Phase 5)
**Options:** WebSocket vs Server-Sent Events vs Polling
**Timeline:** Q4 2026

### ADR-014: Caching Strategy
**Status:** ⏳ Pending
**Options:** Redis vs Memcached vs Laravel Cache
**Timeline:** When traffic increases

---

## 📊 Decision Template

For future architectural decisions, use this template:

```markdown
## ADR-XXX: [Decision Title]

**Status:** ⏳ Proposed | ✅ Accepted | ❌ Rejected
**Date:** [Date]
**Deciders:** [Who made the decision]

### Context
[What is the issue or problem?]
[What are the constraints?]
[What are the options?]

### Decision
[What did we decide?]
[Why did we choose this option?]

### Consequences
**Positive:**
- [Benefit 1]
- [Benefit 2]

**Negative:**
- [Drawback 1]
- [Drawback 2]

**Mitigation:**
- [How we address the drawbacks]
```

---

## 🔄 Decision Review Process

### **When to Review Decisions:**
- Every 6 months
- Major technology changes
- Performance issues
- Security concerns
- Team feedback

### **How to Reverse Decisions:**
1. Document reasons for reversal
2. Get team consensus
3. Create migration plan
4. Update ADR status
5. Implement changes

---

## 📚 References

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue 3 Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

*Last Updated: May 2, 2026*

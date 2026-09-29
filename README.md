# ddd-iam-bundle

Identity & Access Management as a drop-in Bounded Context, for the
FrankenPHP / Symfony / Sylius / DDD stack.

`composer require alexandrebulete/ddd-iam-bundle` gives a project:

- a `User` aggregate — email, password, lifecycle, **roles**;
- authentication against the Sylius back office (`IamUserProvider`);
- a user CRUD in the admin, with grid, filters and a role editor;
- an append-only audit log of every account change;
- `iam:create-super-admin`, the CLI that solves the chicken-and-egg problem.

No file to copy, no namespace to rewrite. What a project legitimately differs
on is configuration.

## Install

```bash
composer require alexandrebulete/ddd-iam-bundle
```

Then, in the application:

```yaml
# config/routes/iam.yaml
iam:
    resource: '@DddIamBundle/config/routes.php'
```

```yaml
# config/packages/security.yaml
security:
    password_hashers:
        AlexandreBulete\DddSymfonyBundle\Security\SecurityUser: 'auto'
    providers:
        app_admin_user_provider:
            id: AlexandreBulete\DddIamBundle\Infrastructure\Security\IamUserProvider
    firewalls:
        # Agents, by API token (see *Agents and API tokens*). Only if you have an API.
        api:
            pattern: ^/api
            stateless: true
            access_token:
                token_handler: AlexandreBulete\DddIamBundle\Infrastructure\Security\ApiTokenHandler
        admin:
            context: admin
            pattern: '/admin(?:/.*)?$'
            provider: app_admin_user_provider
            form_login:
                login_path: sylius_admin_ui_login
                check_path: sylius_admin_ui_login_check
                default_target_path: sylius_admin_ui_dashboard
            logout:
                path: sylius_admin_ui_logout
                target: sylius_admin_ui_login
    access_control:
        - { path: ^/admin/login, roles: PUBLIC_ACCESS }
        - { path: ^/admin/logout, roles: PUBLIC_ACCESS }
        - { path: ^/admin, roles: backoffice.access }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

`backoffice.access` is a **permission**, not a role: it is ticked in a role
like any other (see *Roles and permissions* below).

Then:

```bash
bin/console doctrine:migrations:migrate
bin/console iam:create-super-admin --email=you@example.com
```

**The bundle ships its own migrations** — never run `migrations:diff` for IAM
tables, that would copy the bundle's schema into your history. They are
registered as services (the bundle turns on DoctrineMigrationsBundle's
`enable_service_migrations`) so they receive `iam.table_prefix`, and they are
built with the Schema API, so the DDL follows your platform. Their versions
live under `AlexandreBulete\DddIamBundle\…\Migrations` and sort before
`DoctrineMigrations\…`: IAM tables exist before any project migration that
references them.

Do not set `enable_service_migrations: false` in your application: the IAM
migrations would silently disappear.

**Custom `user_class`**: the bundle migration creates its own columns; your
subclass's extra columns go through your own `migrations:diff`.

## Where the line is

The bundle owns **identity, credentials, lifecycle and authorisation**. It does
not own profile or business data: a phone number, an avatar, a store
assignment, a signature block are not IAM.

That is not a limitation, it is the reason the bundle can be shared between
projects that have nothing else in common. Anything else goes in a **companion
aggregate** in your own Bounded Context, keyed by `UserId`, with no
cross-context foreign key:

```php
final class StaffMember
{
    private function __construct(
        private(set) StaffMemberId $id,
        private(set) UserId $userId,        // published language, not an association
        private(set) ?StoreCode $storeCode,
    ) {}
}
```

Provision it by listening to the bundle's events:

```php
#[AsEventListener]
final readonly class ProvisionStaffMemberOnUserCreated
{
    public function __invoke(UserCreated $event): void { /* … */ }
}
```

`firstName` / `lastName` are the deliberate exception: every user list needs a
human label.

## Extension seams

### 1. Roles and permissions — data, not configuration

Authorization is by **permission** (ddd-symfony-bundle, ADR 0008 of the
project that drove it): every command and query is a permission, discovered
from its handler — `iam.create_user`, `client.find_clients`… — plus a few
entry points like `backoffice.access`.

A **role** is a named set of permissions, defined in the back office
(*Identity › Roles*): name it, tick its permissions, assign it to users. No
YAML, no deploy. A new use case appears in the role screen by itself.

- **`super_admin`** is the one system role, created by the bundle's migration:
  every permission, present and future; it cannot be restricted nor removed.
- **No default role**: an account created without a role can do nothing.
- **No escalation**: whoever acts hands out only what they hold — permissions
  on a role, roles on a user (`super_admin` only by a `super_admin`).
- A role still carried by users or agents cannot be removed.

Providing a `PermissionCheckerInterface` is what turns authorization on: every
command and query on the buses is then checked (a refusal is a 403 over HTTP),
and the menu shows only what its screens allow. In templates and
`access_control`, a permission is used like a role:
`is_granted('iam.find_users')`, `roles: backoffice.access`.

A role that must exist in every environment is created by a **migration of
the project** (insert into `iam_role`); the back office covers the rest.

### 2. Agents and API tokens — `iam.api_tokens`

An **agent** is a non-human account: an AI agent, a CI job, an integration.
It has roles and is authorized exactly like a person, but it has no email, no
password, and never signs in to the back office: it presents an **API token**.

- *Identity › Agents*: create the agent, give it a role.
- *Identity › API tokens*: issue a token for it — label, lifetime (90 days by
  default, a year at most). The token is shown **once**; only its SHA-256 is
  stored. Several tokens per agent: rotate by issuing the next one, deploying
  it, revoking the previous one.
- Revoking an agent revokes its tokens; suspending it stops them until it is
  reactivated. The list flags tokens about to expire.

A token reads `<prefix>_<id>_<secret>`. The prefix (`iam.api_tokens.prefix`,
`iam` by default) makes a leaked token recognisable by secret scanners; the id
is the token's, so everything done with it is one filter away in an activity
journal (ddd-activity-bundle keeps it as the actor's credential).

```bash
curl -H "Authorization: Bearer $TOKEN" https://app.example/api/iam/me
# {"kind":"agent","id":"…","name":"Security watch","token":"…","permissions":[…]}
```

`GET /api/iam/me` says who the token authenticates and what it may do: what a
CI job calls to check its token before relying on it.

An agent running *inside* the application (a worker) needs no token: run its
work as its account, `TraceContext::runAs(Actor::agent($id, $name), 'worker', …)`,
and the same permissions apply.

### 3. Password policy — `iam.password_policy`

```yaml
iam:
    password_policy:
        min_length: 12
        require_letters: true
        require_digits: true
        require_mixed_case: false
        require_special_character: false
```

Rules config cannot express (history, HIBP lookup, per-tenant): implement
`PasswordPolicyInterface` and alias it.

### 4. Back office — `iam.admin`

```yaml
iam:
    admin:
        enabled: true              # false → headless, no Sylius service at all
        grid_limits: [10, 25, 50]
```

**Extending a grid.** Re-declare the service id with your own subclass — do not
use `#[AsDecorator]`:

```php
$services->set(UserGrid::class)
    ->class(UserGridWithStatusFilter::class)
    ->args([param('iam.admin.grid_limits')]);
```

A decorator would leave Sylius's `sylius.grid` tag on the renamed inner service
and the grid would silently disappear from the registry. Re-declaring the id
keeps every tag and autoconfiguration.

Adding a column backed by **your own data** is the one case config does not
cover: grid fields are read off `UserResource`, which is `final`. You need
either your own resource + grid pair, or `iam.user_class`.

### 5. Audit — `iam.audit`

```yaml
iam:
    audit:
        enabled: true
```

Disabled means *absent* — no listener, no write. The `iam_audit_log` table stays
mapped either way: dropping history because a flag flipped is not a thing a
bundle should do.

Events are published through `DomainEventPublisherInterface`. The default
adapter dispatches in-process, inside the aggregate's transaction. A project
running a transactional outbox binds its own adapter and the bundle neither
knows nor cares:

```php
$services->alias(DomainEventPublisherInterface::class, OutboxEventPublisher::class);
```

### 6. Domain events

`UserCreated`, `UserRenamed`, `UserEmailChanged`, `UserPasswordChanged`,
`UserRolesChanged`, `UserSuspended`, `UserReactivated`, `UserRevoked`.

Public and stable. `UserPasswordChanged` deliberately carries no password;
`UserRolesChanged` carries both the new and the previous set, because the delta
cannot be recomputed after the fact.

### 7. Table names — `iam.table_prefix`

```yaml
iam:
    table_prefix: iam_
```

Applied at mapping-load time by `TablePrefixListener` — a bundle cannot ship a
configurable table name in static XML.

### 8. The escape hatch — `iam.user_class`

Subclassing the aggregate. Properties are `protected(set)` and the constructor
is `protected` to make it possible, but overriding this means shipping your own
Doctrine mapping for the subclass, and the bundle then maps only the audit log.

Prefer the companion aggregate. This exists for the case that genuinely cannot
be modelled that way.

## Design notes worth knowing

**Roles are stored as JSON**, not a join table. They are read on every request,
always loaded and written whole, and never queried from the role side. A join
table would buy referential integrity against a role table that does not exist
— the catalogue lives in configuration. On PostgreSQL the column is `jsonb` (binary,
comparable, indexable); other platforms get their JSON type.

**Deleting a user revokes it.** The back office's delete button dispatches
`RevokeUserCommand`. Erasing the row would leave the audit trail pointing at
nothing. GDPR erasure is a different use case: scrub the personal data, keep the
identifier.

**Account status is checked in the user provider**, not in a `UserChecker`.
A `UserChecker` is more idiomatic and discloses less, but it has to be wired per
firewall and a firewall that forgets it authenticates suspended accounts
silently. For a bundle, a check that cannot be forgotten wins. Revisit the day
post-authentication checks appear (password expiry, MFA enrolment).

**Route names are pinned** via `AsResource(alias: 'iam.user')` →
`iam_admin_user_index`. Derived names would change under anyone who moved a
class.

## Supported databases

PostgreSQL, MySQL / MariaDB and SQLite. Every Doctrine type of the bundle maps
onto a portable DBAL declaration, and the migration is written with the Schema
API. The integration tests run the migration on all three and check that the
result is exactly what the ORM mapping expects.

## Development

```bash
composer install
composer qa          # phpstan (max + strict rules), deptrac, phpunit
```

Integration tests use SQLite in memory unless `DDD_TEST_DATABASE_URL` points
at a real database — CI runs them on PostgreSQL and MySQL too.

## Upgrading from 1.x

Roles moved from configuration to the database (2.0):

- remove `iam.roles`, `iam.default_roles` and `iam.super_admin_role` from your
  configuration, and any `security.role_hierarchy` that mirrored them;
- in `access_control`, replace `ROLE_ADMIN` by `backoffice.access`;
- run the migrations (the `iam_role` table and `super_admin` are created),
  then recreate your roles in the back office — `ROLE_ADMIN` / `ROLE_USER` on
  existing accounts are no longer defined roles.

## Configuration reference

```yaml
iam:
    user_class: AlexandreBulete\DddIamBundle\Domain\Model\User
    table_prefix: iam_
    api_tokens:
        prefix: iam            # 2 to 16 lowercase letters and digits
    password_policy:
        min_length: 12
        require_letters: true
        require_digits: true
        require_mixed_case: false
        require_special_character: false
    audit:
        enabled: true
    admin:
        enabled: true
        grid_limits: [10, 25, 50]
```

## Not configurable (yet), and why

**The `/admin` route prefix.** It is fixed in `#[AsResource]`, and it matches
sylius/admin-ui's own prefix, which the application sets in
`config/routes/sylius_admin_ui.yaml`. Making one side configurable without the
other would produce a half-moved back office. Change both, or neither.

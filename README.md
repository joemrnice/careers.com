# CareerPath

CareerPath is a framework-free PHP/MySQL career development platform. It is designed for PHP 8.2+, MySQL 8.0+/MariaDB, Apache, and XAMPP.

## Local setup

1. Copy the project into `C:\xampp\htdocs\careers.com`.
2. Start Apache and MySQL in XAMPP.
3. Import `database/schema.sql`, then `database/seed.sql` using phpMyAdmin.
4. Copy `app/config/config.example.php` to `app/config/config.local.php`.
5. Set `base_url` to `http://localhost/careers.com/public` and update database credentials.
6. Open `http://localhost/careers.com/public/`.

The public entry point provides database-backed career discovery with search and filters, individual career profiles, authenticated saved careers, career comparison, learning paths with enrollment and lesson progress, published articles and tutorials with search, authenticated article bookmarks, a searchable resource library, a rules-based career assessment with optional result persistence and dashboard history, moderated article comments, community discussions with replies and reports, learning challenges, project showcase submissions, a daily learning planner, a private personal development journal, a career transition planner, learning streaks and milestone badges, contact messages, newsletter signup, profile image uploads, persistent SMART-style user goals, responsive navigation, secure account registration/login, logout, account settings, and protected user/admin dashboards. The user dashboard summarizes saved careers, enrolled learning paths, bookmarked guides, career goals, learning consistency, badges, and prior assessment recommendations.

Public pages include unique descriptions, canonical URLs, Open Graph/Twitter metadata, and JSON-LD for the site and articles. Apache serves the dynamic XML sitemap at `/sitemap.xml` and environment-aware crawler directives at `/robots.txt`. Import `database/schema.sql` after pulling changes so the password-reset token table is available.

## Authentication

Registration requires a valid email address and an eight-character minimum password. New accounts must verify their email before signing in. Verification links are single-use and expire after 24 hours; development mode displays the link locally, while other environments attempt delivery with PHP's configured `mail()` transport. Passwords are stored with PHP's `password_hash()` function. Forms use session-backed CSRF tokens, and dashboard/account access is enforced server-side. Users can update their name, email, or password from `?page=profile`; changes require the current password. Account closure soft-deactivates the user record and signs the user out. Password recovery is available through `?page=forgot-password` using single-use, one-hour token records. Import the updated schema so the `email_verified_at` column and `email_verification_tokens`, `password_reset_tokens`, `user_goals`, `challenges`, `user_challenges`, `learning_planner_entries`, `development_journal_entries`, `career_transition_plans`, `project_showcases`, `discussions`, `discussion_replies`, and `discussion_reports` tables exist, and import the seed so the `registered_user` role exists before registration. Existing databases without `email_verified_at` remain able to authenticate existing accounts until the schema is migrated.

The career transition planner stores the user's starting point in `career_transition_plans.current_position`. Existing installations created with an earlier version should rename that column before using the planner:

```sql
ALTER TABLE career_transition_plans
    CHANGE current_role current_position VARCHAR(220) NOT NULL;
```

## Administrator setup

After registering an account, promote it to administrator through phpMyAdmin:

```sql
INSERT INTO user_roles (user_id, role_id)
SELECT users.id, roles.id
FROM users
JOIN roles ON roles.name = 'administrator'
WHERE users.email = 'your-email@example.com';
```

Replace the example email with the account to promote. The `/public/?page=admin` route checks this role on the server for every request.

The administrator dashboard provides protected create/edit forms for careers, articles, learning paths, and resources, plus user management at `?page=users`. Administrators can search accounts and activate, suspend, or soft-delete other users; an administrator cannot change their own status. Learning paths support title, slug, description, level, publication status, modules, and lessons. Use `?page=admin-path&id=<path-id>` to manage a path's modules and lessons. Site branding and default metadata can be managed at `?page=settings`. Forms validate slugs, URLs, statuses, and required fields, use CSRF tokens, use PDO prepared statements, and record successful changes in `audit_logs`. Administrators can review comments, discussions, community reports, and contact messages through `?page=moderation&type=comments`, `?page=moderation&type=discussions`, `?page=moderation&type=reports`, and `?page=moderation&type=messages`, with status changes also audited.

Never commit `app/config/config.local.php`, production credentials, uploaded files, or logs.

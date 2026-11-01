# Zindagi Trust IT Ticketing System

Laravel IT support ticketing for Head Office, School 1, and School 2.

## Local setup

Use Laragon for MySQL and Apache, then run from the project directory:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000/submit-ticket`.

Seeded staff accounts use the password `password` and must be changed before shared or production use:

- `super@zindagitrust.org` — super admin
- `ho.admin@zindagitrust.org` — Head Office branch admin
- `s1.admin@zindagitrust.org` — School 1 branch admin
- `s2.admin@zindagitrust.org` — School 2 branch admin
- `ho.tech@zindagitrust.org` — Head Office technician
- `s1.tech@zindagitrust.org` — School 1 technician
- `s2.tech@zindagitrust.org` — School 2 technician

## Main workflows

- Public requester submission with secure tracking token.
- Private attachment downloads for requesters and authorized staff.
- Branch-isolated dashboard access.
- Multiple technician assignment through `ticket_user`.
- Public requester chat and private staff notes.
- Resolution approval/rejection, cancellation, SLA escalation, and automatic closure.
- Transactional recurring-ticket merge.
- Super-admin management at `/dashboard/admin`.
- Reports at `/dashboard/reports` and CSV export at `/dashboard/tickets-export`.

## Mail

For Mailpit, configure local SMTP values in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=it@zindagitrust.org
MAIL_FROM_NAME="Zindagi Trust IT Support"
```

Production should use approved Google Workspace SMTP credentials or relay. Never commit credentials or a populated `.env` file.

## Scheduled operations

The scheduler runs `tickets:escalate` every fifteen minutes and `tickets:auto-close` daily. Configure the host scheduler to run `php artisan schedule:run` every minute.

## Testing

Run:

```powershell
php artisan test
```

Feature tests use SQLite in-memory storage. Ensure the PHP CLI has `pdo_sqlite` and `sqlite3` enabled, or configure a dedicated test MySQL database.

The first-response metric is measured from ticket creation until the first public staff message. Internal notes do not count.

## Security

Requester access is token-based and does not use application login. Staff authorization is enforced server-side by role, branch, and technician assignment. Historical records are retained; operational entities are deactivated rather than destructively deleted.

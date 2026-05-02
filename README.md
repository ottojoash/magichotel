# Magic Hotel

Magic Hotel is a PHP hotel management project with:

- Guest booking for rooms, restaurant items, bar items, spa, and gym services
- Guest login and dashboard
- Feedback saved to the database
- Admin management for bookings, staff, services, and contact details

## Pages

- `index.html` - landing page
- `booking.php` - booking and guest account creation
- `login.php` - client login
- `client_dashboard.php` - guest dashboard
- `feedback.php` - database-backed guest feedback
- `services.php` - live service and menu catalog
- `contact.php` - live contact information
- `admin_login.php` - staff and admin login
- `admin_dashboard.php` - management dashboard

## Database Setup

1. Start MariaDB from XAMPP.
2. From the project folder, run:

```powershell
& 'C:\xampp\php\php.exe' setup_database.php
```

3. Open the project through your PHP server.

The setup script creates or updates:

- guest users
- staff accounts
- service catalog items
- hotel contact settings

## Default Accounts

Admin:

- `admin@magichotel.com` / `admin123`
- `manager@magichotel.com` / `manager123`

Guest:

- `guest@magichotel.com` / `guest123`

## Troubleshooting

If your local MariaDB root account is not using the standard XAMPP credentials, update `config.php` and `setup_database.php` with the correct host, username, and password first.

On this machine, MariaDB CLI authentication reported `auth_gssapi_client` for the `root` account during terminal setup. If you see the same thing, fix the local MariaDB root auth method or reset the XAMPP development credentials before running `setup_database.php`.


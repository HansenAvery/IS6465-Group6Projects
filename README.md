# SmartPark Solutions (PHP)

Requires PHP 8.0+ with the pdo_sqlite extension (no Composer). Users and parking locations are stored in
data/smartpark.sqlite, created and seeded automatically on first run. Bookings/notifications live in the session.

    cd smartpark-php
    php -S localhost:8000 router.php
    # open http://localhost:8000

Pages: login.php → index.php (Find Parking) → book.php → payment.php → confirmation.php,
plus bookings.php and notifications.php. Shared code is in includes/functions.php.

Login: every page except login.php requires a signed-in session.
Demo account: demo@smartpark.com / Park1234!

CRUD sets (login required):
- Users: users.php (view + delete button) · user_form.php (add / update)
- Parking Locations: locations.php (view + delete button) · location_form.php (add / update)
Parking locations are what the Find Parking and booking pages read, so changes there show up immediately.
Delete data/smartpark.sqlite to reset to the seed data.

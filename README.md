# SmartPark Solutions (PHP)

Requires PHP 8.0+ (no database, no Composer). State is kept in the PHP session.

    cd IS6465-Group6Projects
    php -S localhost:8000
    # open http://localhost:8000

Pages: login.php → index.php (Find Parking) → book.php → payment.php → confirmation.php,
plus bookings.php and notifications.php. Shared code is in includes/functions.php.

Login: every page except login.php requires a signed-in session.
Demo account: demo@smartpark.com / Park1234!  (add users in includes/users.php using password_hash()).

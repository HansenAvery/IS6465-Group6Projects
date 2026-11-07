<?php
// Demo user store. Passwords are stored as password_hash() values, never plain text.
// Generate a new hash with: php -r 'echo password_hash("yourpassword", PASSWORD_DEFAULT);'
return [
    'demo@smartpark.com' => ['name' => 'Demo Driver', 'hash' => '$2y$10$RNQwDO9CKMEQ.s/g20klXuxvUsdSZZikxRPBP3pw596H.KIjX0fTS'],
];

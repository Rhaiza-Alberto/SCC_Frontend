<?php
/**
 * Local Configuration Override Template
 * Copy this file to 'config.local.php' and adjust the values to match your local setup.
 * config.local.php is ignored by Git, so your local changes will not affect others.
 */
return [
    // Database configuration
    'db_host'   => 'localhost',
    'db_user'   => 'root',
    'db_pass'   => '',
    'db_name'   => 'scc_database',

    // SMTP Configuration for Email/OTP notifications
    'smtp_host' => 'smtp.gmail.com',
    'smtp_user' => 'eggvelasco@gmail.com',
    'smtp_pass' => 'xgbx sljs uuqn fwxm', // Gmail App Password
    'smtp_port' => 587,
];

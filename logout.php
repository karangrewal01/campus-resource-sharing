<?php
/**
 * Campus Resource Sharing Platform - User Logout Handler
 */

require_once __DIR__ . '/config/app.php';

logout_user();

set_flash('info', 'You have been safely signed out. See you soon!');
redirect('login.php');

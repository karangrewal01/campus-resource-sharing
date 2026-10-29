<?php
/**
 * Campus Resource Sharing Platform - Faculty Student Requests
 */

require_once dirname(__DIR__) . '/config/app.php';
require_role(['faculty', 'admin']);

// Direct to the unified lending requests handler
redirect('student/lending_requests.php');

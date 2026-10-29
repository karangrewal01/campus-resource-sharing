<?php
/**
 * Campus Resource Sharing Platform - Analytics API Endpoint
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once BASE_PATH . '/services/AnalyticsService.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in() || $_SESSION['user_role'] !== 'admin') {
    json_response(['success' => false, 'error' => 'Unauthorized: Administrator access required'], 403);
}

$analyticsService = new AnalyticsService();
$metrics = $analyticsService->getAdminMetrics();

json_response([
    'success' => true,
    'metrics' => $metrics
]);

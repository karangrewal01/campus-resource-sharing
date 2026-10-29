<?php
/**
 * Campus Resource Sharing Platform - Notifications API Endpoint
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once BASE_PATH . '/services/NotificationService.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'error' => 'Unauthenticated'], 401);
}

$user = current_user();
$notifService = new NotificationService();

$unreadCount = $notifService->getUnreadCount((int)$user['id']);
$notifications = $notifService->getUserNotifications((int)$user['id'], 10);

json_response([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);

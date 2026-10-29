<?php
/**
 * Campus Resource Sharing Platform - Resources API Endpoint
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once BASE_PATH . '/services/ResourceService.php';

header('Content-Type: application/json; charset=utf-8');

$resourceService = new ResourceService();

$filters = [
    'search'              => trim($_GET['search'] ?? ''),
    'category_id'         => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
    'sharing_method'      => !empty($_GET['sharing_method']) ? trim($_GET['sharing_method']) : null,
    'department_id'       => !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null,
    'availability_status' => !empty($_GET['availability_status']) ? trim($_GET['availability_status']) : null
];

$limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$resources = $resourceService->getResources($filters, $limit, $offset);
$total = $resourceService->countResources($filters);

json_response([
    'success' => true,
    'total' => $total,
    'count' => count($resources),
    'resources' => $resources
]);

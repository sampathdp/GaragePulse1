<?php
/**
 * Setup script for combined Customer & Vehicle page
 * Run this file once to add the new page and configure sidebar visibility
 * Access: http://localhost/GaragePulse1/setup_customer_vehicle.php
 */

require_once __DIR__ . '/classes/Includes.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['id']) || ($_SESSION['role_id'] ?? 0) != 1) {
    die('Access denied. Please login as admin.');
}

$db = new Database();
$messages = [];

try {
    // 1. Check if the new page already exists
    $checkQuery = "SELECT id FROM pages WHERE page_route = 'views/CustomerVehicle/'";
    $result = $db->prepareSelect($checkQuery);
    $existingPage = $result ? $result->fetch() : null;

    if (!$existingPage) {
        // Add the new combined Customer & Vehicle page
        $insertQuery = "INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db->prepareExecute($insertQuery, [
            'Customer & Vehicle',
            'views/CustomerVehicle/',
            'Customer & Vehicle',
            'Manage customers and their vehicles in one place',
            'fas fa-users-cog',
            1,
            1
        ]);
        $messages[] = "✅ New 'Customer & Vehicle' page added to pages table.";
    } else {
        $messages[] = "ℹ️ 'Customer & Vehicle' page already exists.";
    }

    // 2. Get page IDs
    $newPageResult = $db->prepareSelect("SELECT id FROM pages WHERE page_route = 'views/CustomerVehicle/'");
    $newPageId = $newPageResult ? $newPageResult->fetch()['id'] : null;

    $customersResult = $db->prepareSelect("SELECT id FROM pages WHERE page_route = 'views/Customer/'");
    $customersPageId = $customersResult ? $customersResult->fetch()['id'] : null;

    $vehiclesResult = $db->prepareSelect("SELECT id FROM pages WHERE page_route = 'views/Vehicle/'");
    $vehiclesPageId = $vehiclesResult ? $vehiclesResult->fetch()['id'] : null;

    // 3. Get all companies
    $companiesResult = $db->prepareSelect("SELECT id FROM companies");
    $companies = $companiesResult ? $companiesResult->fetchAll() : [];

    if (empty($companies)) {
        $companies = [['id' => 1]]; // Default company
    }

    foreach ($companies as $company) {
        $companyId = $company['id'];

        // Make new page visible
        if ($newPageId) {
            $checkExisting = $db->prepareSelect(
                "SELECT id FROM sidebar_modules WHERE company_id = ? AND page_id = ?",
                [$companyId, $newPageId]
            );
            if ($checkExisting && $checkExisting->fetch()) {
                $db->prepareExecute(
                    "UPDATE sidebar_modules SET is_visible = 1 WHERE company_id = ? AND page_id = ?",
                    [$companyId, $newPageId]
                );
            } else {
                $db->prepareExecute(
                    "INSERT INTO sidebar_modules (company_id, page_id, is_visible) VALUES (?, ?, 1)",
                    [$companyId, $newPageId]
                );
            }
        }

        // Hide old Customers page
        if ($customersPageId) {
            $db->prepareExecute(
                "UPDATE sidebar_modules SET is_visible = 0 WHERE company_id = ? AND page_id = ?",
                [$companyId, $customersPageId]
            );
        }

        // Hide old Vehicles page
        if ($vehiclesPageId) {
            $db->prepareExecute(
                "UPDATE sidebar_modules SET is_visible = 0 WHERE company_id = ? AND page_id = ?",
                [$companyId, $vehiclesPageId]
            );
        }
    }

    $messages[] = "✅ Sidebar visibility updated for " . count($companies) . " company(ies).";
    $messages[] = "✅ Old 'Customers' and 'Vehicles' pages hidden from sidebar.";
    $messages[] = "✅ New 'Customer & Vehicle' page is now visible in sidebar.";

} catch (Exception $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Customer & Vehicle Page</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; background: #f0f0f0; }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
        .error { background: #f8d7da; color: #721c24; }
        a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Setup Complete</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : (strpos($msg, '❌') !== false ? 'error' : 'info'); ?>">
            <?php echo $msg; ?>
        </div>
    <?php endforeach; ?>
    <p><strong>Next steps:</strong></p>
    <ol>
        <li>Refresh your browser to see the updated sidebar</li>
        <li>The new "Customer & Vehicle" page should appear under "Customer & Vehicle" category</li>
        <li>The old "Customers" and "Vehicles" pages are now hidden</li>
    </ol>
    <a href="<?php echo BASE_URL; ?>views/CustomerVehicle/">Go to Customer & Vehicle Page</a>
    <a href="<?php echo BASE_URL; ?>views/Dashboard/" style="background: #6c757d; margin-left: 10px;">Go to Dashboard</a>
</body>
</html>

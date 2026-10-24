<?php
/**
 * SDPMS - Cancel Purchase Order Handler
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    set_flash_message('danger', 'Purchase order identifier missing.');
    header('Location: ' . BASE_URL . '/purchase-orders/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM `purchase_orders` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash_message('danger', "Purchase order '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/purchase-orders/index.php');
    exit;
}

if ($order['status'] === 'Completed') {
    set_flash_message('danger', "Completed purchase orders cannot be cancelled.");
    header('Location: ' . BASE_URL . '/purchase-orders/view.php?id=' . urlencode($id));
    exit;
}

try {
    $db->beginTransaction();

    // 1. Update PO Status
    $updPO = $db->prepare("UPDATE `purchase_orders` SET `status` = 'Cancelled' WHERE id = :id");
    $updPO->execute([':id' => $id]);

    // 2. Update Delivery Status
    $delStmt = $db->prepare("SELECT id, status FROM `deliveries` WHERE order_id = :id LIMIT 1");
    $delStmt->execute([':id' => $id]);
    $delivery = $delStmt->fetch();

    if ($delivery) {
        $updDel = $db->prepare("UPDATE `deliveries` SET `status` = 'Cancelled', `remarks` = 'Order cancelled by purchasing manager' WHERE id = :did");
        $updDel->execute([':did' => $delivery['id']]);

        // 3. Log History
        $hist = $db->prepare(
            "INSERT INTO `delivery_status_history` (`delivery_id`, `previous_status`, `new_status`, `notes`, `changed_by`)
             VALUES (:did, :prev, 'Cancelled', 'Purchase order cancelled.', :uid)"
        );
        $hist->execute([
            ':did'  => $delivery['id'],
            ':prev' => $delivery['status'],
            ':uid'  => $_SESSION['user_id'] ?? null
        ]);
    }

    // 4. Audit Log
    log_audit_event($db, 'CANCEL', 'purchase_orders', $id, "Cancelled purchase order {$id}.");

    $db->commit();
    set_flash_message('warning', "Purchase Order {$id} has been formally cancelled.");
} catch (Exception $e) {
    $db->rollBack();
    set_flash_message('danger', "Failed to cancel order: " . $e->getMessage());
}

header('Location: ' . BASE_URL . '/purchase-orders/index.php');
exit;

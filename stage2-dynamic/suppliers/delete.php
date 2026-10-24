<?php
/**
 * SDPMS - Safe Supplier Deletion / Deactivation Handler
 * Safety Constraint: Prevents deletion if linked purchase orders exist
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    set_flash_message('danger', 'Supplier identifier missing.');
    header('Location: ' . BASE_URL . '/suppliers/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM `suppliers` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash_message('danger', "Supplier '{$id}' not found.");
    header('Location: ' . BASE_URL . '/suppliers/index.php');
    exit;
}

// Check linked purchase orders count
$chkStmt = $db->prepare("SELECT COUNT(*) FROM `purchase_orders` WHERE supplier_id = :id");
$chkStmt->execute([':id' => $id]);
$linkedCount = (int)$chkStmt->fetchColumn();

if ($linkedCount > 0) {
    // Safety constraint: Deactivate rather than delete to preserve audit history & prevent orphaned records
    $upd = $db->prepare("UPDATE `suppliers` SET `status` = 'Inactive' WHERE id = :id");
    $upd->execute([':id' => $id]);

    log_audit_event(
        $db, 
        'DEACTIVATE', 
        'suppliers', 
        $id, 
        "Supplier '{$supplier['company_name']}' has {$linkedCount} linked PO(s). Deactivated instead of deleted to protect historical integrity."
    );

    set_flash_message(
        'warning', 
        "Safety Constraint Applied: Supplier '{$supplier['company_name']}' has {$linkedCount} linked purchase order(s). To protect historical procurement records, the supplier has been deactivated instead of deleted."
    );
} else {
    // No linked records, safe to delete completely
    try {
        $del = $db->prepare("DELETE FROM `suppliers` WHERE id = :id");
        $del->execute([':id' => $id]);

        log_audit_event($db, 'DELETE', 'suppliers', $id, "Deleted unlinked supplier '{$supplier['company_name']}'.");
        set_flash_message('success', "Supplier '{$supplier['company_name']}' ({$id}) was completely deleted.");
    } catch (Exception $e) {
        set_flash_message('danger', "Failed to delete supplier: " . $e->getMessage());
    }
}

header('Location: ' . BASE_URL . '/suppliers/index.php');
exit;

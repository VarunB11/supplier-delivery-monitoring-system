<?php
/**
 * SDPMS - Secure CSV Data Exporter
 * Streams formatted CSV with UTF-8 BOM and RFC 4180 field escaping
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$reportType = trim($_GET['type'] ?? 'orders');
$supplierId = trim($_GET['supplier'] ?? '');
$status     = trim($_GET['status'] ?? '');
$startDate  = trim($_GET['start_date'] ?? '');
$endDate    = trim($_GET['end_date'] ?? '');

$filename = strtoupper($reportType) . '_REPORT_' . date('Ymd_His') . '.csv';

// Output HTTP Headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Write UTF-8 Byte Order Mark (BOM) for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

if ($reportType === 'orders') {
    fputcsv($output, ['PO Number', 'Supplier ID', 'Supplier Name', 'Order Date', 'Expected Date', 'Description', 'Quantity', 'Unit Price (INR)', 'Total Amount (INR)', 'Status', 'Notes']);

    $where = [];
    $params = [];

    if ($supplierId !== '') { $where[] = "po.supplier_id = :sup"; $params[':sup'] = $supplierId; }
    if ($status !== '') { $where[] = "po.status = :st"; $params[':st'] = $status; }
    if ($startDate !== '') { $where[] = "po.order_date >= :sdate"; $params[':sdate'] = $startDate; }
    if ($endDate !== '') { $where[] = "po.order_date <= :edate"; $params[':edate'] = $endDate; }

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT po.*, s.company_name FROM `purchase_orders` po JOIN `suppliers` s ON po.supplier_id = s.id {$whereSQL} ORDER BY po.order_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        fputcsv($output, [
            $r['id'],
            $r['supplier_id'],
            $r['company_name'],
            $r['order_date'],
            $r['expected_date'],
            $r['description'],
            $r['quantity'],
            $r['unit_price'],
            $r['total_amount'],
            $r['status'],
            $r['notes'] ?? ''
        ]);
    }

} elseif ($reportType === 'deliveries') {
    fputcsv($output, ['Delivery ID', 'PO Number', 'Supplier ID', 'Supplier Name', 'Expected Date', 'Actual Date', 'Ordered Qty', 'Received Qty', 'Delay Days', 'Schedule Status', 'Delivery Status', 'Remarks']);

    $where = [];
    $params = [];

    if ($supplierId !== '') { $where[] = "d.supplier_id = :sup"; $params[':sup'] = $supplierId; }
    if ($status !== '') { $where[] = "d.status = :st"; $params[':st'] = $status; }
    if ($startDate !== '') { $where[] = "d.expected_date >= :sdate"; $params[':sdate'] = $startDate; }
    if ($endDate !== '') { $where[] = "d.expected_date <= :edate"; $params[':edate'] = $endDate; }

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT d.*, s.company_name 
            FROM `deliveries` d 
            JOIN `suppliers` s ON d.supplier_id = s.id 
            {$whereSQL} 
            ORDER BY d.expected_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        $info = calculate_delay_days($r['expected_date'], $r['actual_date'], $r['status']);
        fputcsv($output, [
            $r['id'],
            $r['order_id'],
            $r['supplier_id'],
            $r['company_name'],
            $r['expected_date'],
            $r['actual_date'] ?? 'Pending',
            $r['ordered_qty'],
            $r['received_qty'],
            $info['delay'],
            $info['text'],
            $r['status'],
            $r['remarks'] ?? ''
        ]);
    }

} elseif ($reportType === 'delayed') {
    fputcsv($output, ['PO Number', 'Delivery ID', 'Supplier ID', 'Supplier Name', 'Expected Date', 'Delay Days', 'Delay Type', 'Ordered Qty', 'Status', 'Remarks']);

    $where = ["(d.status = 'Delayed' OR (d.status = 'Pending' AND d.expected_date < CURDATE()))"];
    $params = [];

    if ($supplierId !== '') { $where[] = "d.supplier_id = :sup"; $params[':sup'] = $supplierId; }

    $whereSQL = "WHERE " . implode(" AND ", $where);
    $sql = "SELECT d.*, s.company_name 
            FROM `deliveries` d 
            JOIN `suppliers` s ON d.supplier_id = s.id 
            {$whereSQL} 
            ORDER BY d.expected_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        $info = calculate_delay_days($r['expected_date'], $r['actual_date'], $r['status']);
        fputcsv($output, [
            $r['order_id'],
            $r['id'],
            $r['supplier_id'],
            $r['company_name'],
            $r['expected_date'],
            $info['delay'],
            $info['isOverdue'] ? 'Overdue' : 'Late Delivery',
            $r['ordered_qty'],
            $r['status'],
            $r['remarks'] ?? ''
        ]);
    }

} elseif ($reportType === 'performance') {
    fputcsv($output, ['Supplier ID', 'Company Name', 'Material Category', 'Status', 'Total Orders', 'Eligible Completed', 'On-Time Count', 'Late Count', 'On-Time %', 'Average Delay (Days)', 'Ordered Qty', 'Received Qty', 'Qty Fulfilment %', 'Composite Score (/100)', 'Rating Tier']);

    $perfList = get_supplier_performance_metrics($db);
    foreach ($perfList as $m) {
        if ($supplierId !== '' && $m['supplier_id'] !== $supplierId) continue;
        if ($status !== '' && $m['supplier_status'] !== $status) continue;

        fputcsv($output, [
            $m['supplier_id'],
            $m['company_name'],
            $m['category'],
            $m['supplier_status'],
            $m['total_orders'],
            $m['eligible_completed'],
            $m['on_time_count'],
            $m['late_count'],
            $m['on_time_percentage'] !== null ? $m['on_time_percentage'] . '%' : 'N/A',
            $m['avg_delay_days'],
            $m['total_ordered_qty'],
            $m['total_received_qty'],
            $m['qty_fulfilment_pct'] . '%',
            $m['overall_score'] !== null ? $m['overall_score'] : 'N/A',
            $m['rating']
        ]);
    }
}

fclose($output);
exit;

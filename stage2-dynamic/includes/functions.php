<?php
/**
 * SDPMS - Core Business Logic, Formatting & Audit Helper Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Output sanitization against XSS
 */
function sanitize_output(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Currency formatter (Indian Rupee format)
 */
function format_currency_inr($num): string {
    if ($num === null || $num === '') return '₹0';
    $amount = (float)$num;
    return '₹' . number_format($amount, 2);
}

/**
 * Date formatter (e.g. 20 Jan 2026)
 */
function format_date_in(?string $dateStr): string {
    if (!$dateStr || $dateStr === '0000-00-00') return '—';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M Y', $timestamp) : $dateStr;
}

/**
 * Flash messaging helpers
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Status HTML badge generator
 */
function render_status_badge(string $status): string {
    $map = [
        'Active'              => 'badge-active',
        'Inactive'            => 'badge-inactive',
        'Pending'             => 'badge-pending',
        'On Time'             => 'badge-ontime',
        'Delivered'           => 'badge-delivered',
        'Completed'           => 'badge-completed',
        'Delayed'             => 'badge-delayed',
        'Partially Delivered' => 'badge-partial',
        'Cancelled'           => 'badge-cancelled'
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return sprintf(
        '<span class="badge %s"><span class="badge-dot"></span>%s</span>',
        $cls,
        sanitize_output($status)
    );
}

/**
 * Rating pill generator
 */
function render_rating_badge(string $rating): string {
    $map = [
        'Excellent'         => 'rating-excellent',
        'Good'              => 'rating-good',
        'Average'           => 'rating-average',
        'Needs Improvement' => 'rating-needs-improvement',
        'Unrated'           => 'rating-unrated'
    ];
    $cls = $map[$rating] ?? 'rating-unrated';
    return sprintf('<span class="rating-pill %s">%s</span>', $cls, sanitize_output($rating));
}

/**
 * Delay Days Calculation Business Logic:
 * - Completed deliveries: actual - expected in calendar days
 * - Overdue undelivered: today - expected in calendar days
 */
function calculate_delay_days(?string $expectedDate, ?string $actualDate, string $status): array {
    if ($status === 'Cancelled') {
        return ['delay' => 0, 'isLate' => false, 'isOverdue' => false, 'isEarly' => false, 'text' => 'Cancelled'];
    }

    if (!empty($actualDate)) {
        $exp = new DateTime($expectedDate);
        $act = new DateTime($actualDate);
        $diff = $exp->diff($act);
        $days = (int)$diff->format("%r%a");

        if ($days > 0) {
            return ['delay' => $days, 'isLate' => true, 'isOverdue' => false, 'isEarly' => false, 'text' => "{$days} days late"];
        } elseif ($days === 0) {
            return ['delay' => 0, 'isLate' => false, 'isOverdue' => false, 'isEarly' => false, 'text' => 'On Time'];
        } else {
            $early = abs($days);
            return ['delay' => 0, 'earlyDays' => $early, 'isLate' => false, 'isOverdue' => false, 'isEarly' => true, 'text' => "{$early} days early"];
        }
    } else {
        // Still pending receipt
        $exp = new DateTime($expectedDate);
        $today = new DateTime('today');
        $diff = $exp->diff($today);
        $days = (int)$diff->format("%r%a");

        if ($days > 0) {
            return ['delay' => $days, 'isLate' => true, 'isOverdue' => true, 'isEarly' => false, 'text' => "{$days} days overdue"];
        } else {
            return ['delay' => 0, 'isLate' => false, 'isOverdue' => false, 'isEarly' => false, 'text' => 'Within schedule'];
        }
    }
}

/**
 * Audit Logger Helper
 */
function log_audit_event(PDO $db, string $action, string $table, string $recordId, string $details): void {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare(
            "INSERT INTO `audit_logs` (`user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`)
             VALUES (:user_id, :action, :table_name, :record_id, :details, :ip_address)"
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':action'     => $action,
            ':table_name' => $table,
            ':record_id'  => $recordId,
            ':details'    => $details,
            ':ip_address' => $ip
        ]);
    } catch (Exception $e) {
        // Silently preserve main execution
    }
}

/**
 * System-wide Dashboard KPIs derived directly from database tables
 */
function get_system_dashboard_kpis(PDO $db): array {
    // 1. Total & Active Suppliers
    $stmt = $db->query("SELECT COUNT(*) AS total, SUM(CASE WHEN `status` = 'Active' THEN 1 ELSE 0 END) AS active FROM `suppliers`");
    $supData = $stmt->fetch();
    $totalSuppliers = (int)($supData['total'] ?? 0);
    $activeSuppliers = (int)($supData['active'] ?? 0);

    // 2. Total Purchase Orders (excluding Cancelled)
    $stmt = $db->query("SELECT COUNT(*) FROM `purchase_orders` WHERE `status` != 'Cancelled'");
    $totalOrders = (int)$stmt->fetchColumn();

    // 3. Awaiting Delivery (Pending or Partially Delivered)
    $stmt = $db->query("SELECT COUNT(*) FROM `deliveries` WHERE `status` IN ('Pending', 'Partially Delivered')");
    $awaitingDelivery = (int)$stmt->fetchColumn();

    // 4. Delayed Deliveries (Flagged Delayed or Overdue Expected Date)
    $stmt = $db->query(
        "SELECT COUNT(*) FROM `deliveries` 
         WHERE `status` = 'Delayed' 
            OR (`status` = 'Pending' AND `expected_date` < CURDATE())"
    );
    $delayedOrders = (int)$stmt->fetchColumn();

    // 5. Eligible Completed Deliveries & On-Time Rate
    $stmt = $db->query("SELECT `expected_date`, `actual_date`, `status` FROM `deliveries` WHERE `status` = 'Delivered' AND `actual_date` IS NOT NULL");
    $completedDeliveries = $stmt->fetchAll();
    $totalEligible = count($completedDeliveries);
    $onTimeCount = 0;

    foreach ($completedDeliveries as $del) {
        $delayInfo = calculate_delay_days($del['expected_date'], $del['actual_date'], $del['status']);
        if (!$delayInfo['isLate']) {
            $onTimeCount++;
        }
    }

    $onTimePercentage = $totalEligible > 0 ? round(($onTimeCount / $totalEligible) * 100, 1) : 0.0;

    return [
        'totalSuppliers'   => $totalSuppliers,
        'activeSuppliers'  => $activeSuppliers,
        'totalOrders'      => $totalOrders,
        'awaitingDelivery' => $awaitingDelivery,
        'delayedOrders'    => $delayedOrders,
        'onTimeDeliveries' => $onTimeCount,
        'totalEligible'    => $totalEligible,
        'onTimePercentage' => $onTimePercentage
    ];
}

/**
 * Supplier Performance Evaluation Logic (Exact Formulas)
 */
function get_supplier_performance_metrics(PDO $db, ?string $supplierId = null): array {
    $where = $supplierId ? "WHERE s.id = :supplier_id" : "";
    $sql = "SELECT s.id, s.company_name, s.category, s.status,
                   (SELECT COUNT(*) FROM `purchase_orders` po WHERE po.supplier_id = s.id AND po.status != 'Cancelled') AS total_orders
            FROM `suppliers` s
            {$where}
            ORDER BY s.company_name ASC";
    
    $stmt = $db->prepare($sql);
    if ($supplierId) {
        $stmt->execute([':supplier_id' => $supplierId]);
    } else {
        $stmt->execute();
    }
    $suppliers = $stmt->fetchAll();

    $results = [];

    foreach ($suppliers as $sup) {
        $sId = $sup['id'];

        // Fetch all active deliveries for this supplier
        $delStmt = $db->prepare(
            "SELECT `id`, `expected_date`, `actual_date`, `ordered_qty`, `received_qty`, `status` 
             FROM `deliveries` 
             WHERE `supplier_id` = :sid AND `status` != 'Cancelled'"
        );
        $delStmt->execute([':sid' => $sId]);
        $deliveries = $delStmt->fetchAll();

        $eligibleCompleted = 0;
        $onTimeCount = 0;
        $lateCount = 0;
        $sumPositiveDelayDays = 0;
        $totalOrderedQty = 0.0;
        $totalReceivedQty = 0.0;

        foreach ($deliveries as $d) {
            $totalOrderedQty += (float)$d['ordered_qty'];
            $totalReceivedQty += (float)$d['received_qty'];

            if ($d['status'] === 'Delivered' && !empty($d['actual_date'])) {
                $eligibleCompleted++;
                $info = calculate_delay_days($d['expected_date'], $d['actual_date'], $d['status']);
                if ($info['isLate']) {
                    $lateCount++;
                    $sumPositiveDelayDays += $info['delay'];
                } else {
                    $onTimeCount++;
                }
            }
        }

        // Formula 1: On-Time Delivery %
        $onTimePercentage = $eligibleCompleted > 0 ? round(($onTimeCount / $eligibleCompleted) * 100, 1) : null;

        // Formula 2: Average Delay Days
        $avgDelay = $eligibleCompleted > 0 ? round($sumPositiveDelayDays / $eligibleCompleted, 1) : 0.0;

        // Formula 3: Quantity Fulfilment %
        $qtyFulfilment = $totalOrderedQty > 0 ? round(($totalReceivedQty / $totalOrderedQty) * 100, 1) : 100.0;

        // Composite Weighted Score (70% On-Time + 30% Quantity)
        $overallScore = null;
        $rating = 'Unrated';

        if ($eligibleCompleted > 0) {
            $clampedQty = min(100.0, $qtyFulfilment);
            $overallScore = round((0.70 * $onTimePercentage) + (0.30 * $clampedQty));

            if ($overallScore >= 90) {
                $rating = 'Excellent';
            } elseif ($overallScore >= 75) {
                $rating = 'Good';
            } elseif ($overallScore >= 60) {
                $rating = 'Average';
            } else {
                $rating = 'Needs Improvement';
            }
        }

        $results[] = [
            'supplier_id'         => $sId,
            'company_name'        => $sup['company_name'],
            'category'            => $sup['category'],
            'supplier_status'     => $sup['status'],
            'total_orders'        => (int)$sup['total_orders'],
            'eligible_completed'  => $eligibleCompleted,
            'on_time_count'       => $onTimeCount,
            'late_count'          => $lateCount,
            'on_time_percentage'  => $onTimePercentage,
            'avg_delay_days'      => $avgDelay,
            'total_ordered_qty'   => $totalOrderedQty,
            'total_received_qty'  => $totalReceivedQty,
            'qty_fulfilment_pct'  => $qtyFulfilment,
            'overall_score'       => $overallScore,
            'rating'              => $rating
        ];
    }

    return $supplierId ? ($results[0] ?? []) : $results;
}

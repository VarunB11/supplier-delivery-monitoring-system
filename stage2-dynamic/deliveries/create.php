<?php
/**
 * SDPMS - Create Delivery Consignment
 * In accordance with procurement ERP standards, deliveries are scheduled via Purchase Orders
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

set_flash_message('info', 'Consignments are automatically initiated upon Purchase Order requisition. Please create a new purchase order to schedule delivery.');
header('Location: ' . BASE_URL . '/purchase-orders/create.php');
exit;

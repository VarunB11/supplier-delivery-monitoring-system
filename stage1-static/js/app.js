/**
 * SUPPLIER DELIVERY & PERFORMANCE MONITORING SYSTEM
 * Core Application Logic, Data Store & Shared Utilities
 */

const STORAGE_KEYS = {
  SUPPLIERS: 'sdpms_suppliers_v1',
  ORDERS: 'sdpms_orders_v1',
  DELIVERIES: 'sdpms_deliveries_v1',
  HISTORY: 'sdpms_delivery_history_v1',
  ACTIVITY_LOG: 'sdpms_user_activity_v1',
  CURRENT_USER: 'sdpms_current_user_v1'
};

const DEMO_USERS = {
  admin: {
    id: 1,
    name: 'Rajesh Sengupta',
    email: 'admin@procuretrack.example.com',
    role: 'Admin',
    initials: 'RS'
  },
  buyer: {
    id: 2,
    name: 'Priya Menon',
    email: 'buyer@procuretrack.example.com',
    role: 'Purchasing_Manager',
    initials: 'PM'
  },
  auditor: {
    id: 3,
    name: 'Amit Deshmukh',
    email: 'auditor@procuretrack.example.com',
    role: 'Auditor',
    initials: 'AD'
  }
};

// Initial realistic demonstration datasets
const INITIAL_SUPPLIERS = [
  {
    id: 'SUP-1001',
    company_name: 'Apex Precision Steel Ltd.',
    contact_person: 'Rajesh Sharma',
    email: 'r.sharma@apexsteel.example.com',
    phone: '+91 98230 11223',
    address: 'Plot 45, Phase II, Industrial Estate',
    city: 'Pune',
    category: 'Metals & Alloys',
    registration_date: '2025-01-15',
    status: 'Active'
  },
  {
    id: 'SUP-1002',
    company_name: 'Vortex Microelectronics Pvt.',
    contact_person: 'Anita Deshmukh',
    email: 'anita@vortexmicro.example.com',
    phone: '+91 98450 33445',
    address: '7B, Tech Park Road, Whitefield',
    city: 'Bengaluru',
    category: 'Electronic Components',
    registration_date: '2025-02-10',
    status: 'Active'
  },
  {
    id: 'SUP-1003',
    company_name: 'Nova Polymer Solutions',
    contact_person: 'Vikram Mehta',
    email: 'vmehta@novapolymers.example.com',
    phone: '+91 98112 55667',
    address: '12 GIDC Estate, Vatva',
    city: 'Ahmedabad',
    category: 'Plastics & Polymers',
    registration_date: '2025-03-01',
    status: 'Active'
  },
  {
    id: 'SUP-1004',
    company_name: 'Bharat Fasteners & Hardware',
    contact_person: 'Suresh Patil',
    email: 'suresh@bharatfasteners.example.com',
    phone: '+91 97654 77889',
    address: 'D-14, MIDC Ambad',
    city: 'Nashik',
    category: 'Fasteners & Hardware',
    registration_date: '2025-03-20',
    status: 'Active'
  },
  {
    id: 'SUP-1005',
    company_name: 'Zenith Industrial Packaging',
    contact_person: 'Meera Nair',
    email: 'm.nair@zenithpack.example.com',
    phone: '+91 99401 22334',
    address: '88 Ambattur Industrial Estate',
    city: 'Chennai',
    category: 'Packaging Materials',
    registration_date: '2025-04-05',
    status: 'Active'
  },
  {
    id: 'SUP-1006',
    company_name: 'Kavita Chemical Reagents',
    contact_person: 'Dr. Kavita Joshi',
    email: 'kavita@kavitachem.example.com',
    phone: '+91 98200 44556',
    address: 'Plot 102, TTC Industrial Area',
    city: 'Navi Mumbai',
    category: 'Chemical Reagents',
    registration_date: '2025-05-12',
    status: 'Active'
  },
  {
    id: 'SUP-1007',
    company_name: 'Dynamic Fluid Systems',
    contact_person: 'Arun Verma',
    email: 'arun@dynamicfluid.example.com',
    phone: '+91 98765 88990',
    address: '33 Sector 25, HSIIDC',
    city: 'Faridabad',
    category: 'Hydraulic & Pneumatic',
    registration_date: '2025-06-01',
    status: 'Active'
  },
  {
    id: 'SUP-1008',
    company_name: 'Delta Rubber & Seals Corp',
    contact_person: 'Pooja Hegde',
    email: 'pooja@deltarubber.example.com',
    phone: '+91 97411 99001',
    address: 'Plot 19, Peenya 3rd Phase',
    city: 'Bengaluru',
    category: 'Rubber & Seals',
    registration_date: '2025-07-15',
    status: 'Inactive'
  }
];

const INITIAL_ORDERS = [
  {
    id: 'PO-2026-001',
    supplier_id: 'SUP-1001',
    order_date: '2026-01-05',
    expected_date: '2026-01-20',
    description: 'High-Tensile Cold Rolled Steel Coils (Grade EN8)',
    quantity: 50,
    unit_price: 65000,
    total_amount: 3250000,
    status: 'Completed',
    notes: 'Urgent batch for Q1 chassis fabrication'
  },
  {
    id: 'PO-2026-002',
    supplier_id: 'SUP-1002',
    order_date: '2026-01-10',
    expected_date: '2026-01-25',
    description: 'STM32 Microcontroller ICs (100-pin LQFP)',
    quantity: 1200,
    unit_price: 380,
    total_amount: 456000,
    status: 'Completed',
    notes: 'Anti-static moisture barrier packaging required'
  },
  {
    id: 'PO-2026-003',
    supplier_id: 'SUP-1003',
    order_date: '2026-01-15',
    expected_date: '2026-02-01',
    description: 'High Density Polyethylene (HDPE) Granules - Natural',
    quantity: 2000,
    unit_price: 115,
    total_amount: 230000,
    status: 'Completed',
    notes: 'Batch test certificate must accompany shipment'
  },
  {
    id: 'PO-2026-004',
    supplier_id: 'SUP-1004',
    order_date: '2026-01-22',
    expected_date: '2026-02-05',
    description: 'M8 Stainless Steel Hex Socket Cap Screws (A2-70)',
    quantity: 10000,
    unit_price: 8.5,
    total_amount: 85000,
    status: 'Completed',
    notes: 'DIN 912 standard compliance verified'
  },
  {
    id: 'PO-2026-005',
    supplier_id: 'SUP-1005',
    order_date: '2026-02-01',
    expected_date: '2026-02-14',
    description: 'Heavy Duty 5-Ply Corrugated Shipping Cartons',
    quantity: 3500,
    unit_price: 42,
    total_amount: 147000,
    status: 'Completed',
    notes: 'Moisture resistant coating'
  },
  {
    id: 'PO-2026-006',
    supplier_id: 'SUP-1006',
    order_date: '2026-02-10',
    expected_date: '2026-02-24',
    description: 'Industrial Degreasing Solvent (Trichloroethylene alt)',
    quantity: 400,
    unit_price: 520,
    total_amount: 208000,
    status: 'Completed',
    notes: 'MSDS documentation strictly mandatory'
  },
  {
    id: 'PO-2026-007',
    supplier_id: 'SUP-1001',
    order_date: '2026-02-15',
    expected_date: '2026-03-02',
    description: 'Seamless Hydraulic Tubing (OD 25mm, WT 2.5mm)',
    quantity: 150,
    unit_price: 1850,
    total_amount: 277500,
    status: 'Completed',
    notes: 'Eddy-current tested tubes'
  },
  {
    id: 'PO-2026-008',
    supplier_id: 'SUP-1002',
    order_date: '2026-02-20',
    expected_date: '2026-03-08',
    description: 'Optocoupler High-Speed Isolators (SMD)',
    quantity: 2500,
    unit_price: 65,
    total_amount: 162500,
    status: 'Completed',
    notes: 'Tape and reel packaging'
  },
  {
    id: 'PO-2026-009',
    supplier_id: 'SUP-1007',
    order_date: '2026-03-01',
    expected_date: '2026-03-18',
    description: 'Pneumatic Actuator Cylinders Double Acting (50mm Bore)',
    quantity: 30,
    unit_price: 8400,
    total_amount: 252000,
    status: 'Partially Delivered',
    notes: 'Balance quantity delayed due to valve block shortage'
  },
  {
    id: 'PO-2026-010',
    supplier_id: 'SUP-1003',
    order_date: '2026-03-05',
    expected_date: '2026-03-22',
    description: 'Polypropylene Copolymer Resin (MFI 12)',
    quantity: 1500,
    unit_price: 130,
    total_amount: 195000,
    status: 'Delayed',
    notes: 'Overdue shipment; logistics breakdown reported'
  },
  {
    id: 'PO-2026-011',
    supplier_id: 'SUP-1004',
    order_date: '2026-03-10',
    expected_date: '2026-03-28',
    description: 'M12 Grade 8.8 High Strength Anchor Bolts',
    quantity: 4000,
    unit_price: 24,
    total_amount: 96000,
    status: 'Delayed',
    notes: 'Raw material shortage at supplier forge'
  },
  {
    id: 'PO-2026-012',
    supplier_id: 'SUP-1005',
    order_date: '2026-03-15',
    expected_date: '2026-04-05',
    description: 'Custom Die-Cut Foam Cushioning Inserts',
    quantity: 1800,
    unit_price: 68,
    total_amount: 122400,
    status: 'Pending',
    notes: 'Awaiting manufacturing completion'
  },
  {
    id: 'PO-2026-013',
    supplier_id: 'SUP-1006',
    order_date: '2026-03-20',
    expected_date: '2026-04-10',
    description: 'Surface Phosphating Chemical Dip (200L Drum)',
    quantity: 10,
    unit_price: 14500,
    total_amount: 145000,
    status: 'Pending',
    notes: 'Scheduled for dispatch next week'
  },
  {
    id: 'PO-2026-014',
    supplier_id: 'SUP-1008',
    order_date: '2026-02-05',
    expected_date: '2026-02-25',
    description: 'Viton Fluoropolymer O-Rings Assorted Kit',
    quantity: 200,
    unit_price: 450,
    total_amount: 90000,
    status: 'Cancelled',
    notes: 'Order cancelled due to supplier factory shutdown'
  }
];

const INITIAL_DELIVERIES = [
  {
    id: 'DEL-3001',
    order_id: 'PO-2026-001',
    supplier_id: 'SUP-1001',
    expected_date: '2026-01-20',
    actual_date: '2026-01-19',
    ordered_qty: 50,
    received_qty: 50,
    status: 'Delivered',
    remarks: 'Delivered 1 day early. Inspection passed with zero defects.'
  },
  {
    id: 'DEL-3002',
    order_id: 'PO-2026-002',
    supplier_id: 'SUP-1002',
    expected_date: '2026-01-25',
    actual_date: '2026-01-30',
    ordered_qty: 1200,
    received_qty: 1200,
    status: 'Delivered',
    remarks: 'Delivered 5 days late due to air cargo customs clearance delay.'
  },
  {
    id: 'DEL-3003',
    order_id: 'PO-2026-003',
    supplier_id: 'SUP-1003',
    expected_date: '2026-02-01',
    actual_date: '2026-02-01',
    ordered_qty: 2000,
    received_qty: 2000,
    status: 'Delivered',
    remarks: 'Delivered exactly on schedule. Quality certificate accepted.'
  },
  {
    id: 'DEL-3004',
    order_id: 'PO-2026-004',
    supplier_id: 'SUP-1004',
    expected_date: '2026-02-05',
    actual_date: '2026-02-04',
    ordered_qty: 10000,
    received_qty: 10000,
    status: 'Delivered',
    remarks: 'Delivered 1 day early. Full count verified via automated weigh counter.'
  },
  {
    id: 'DEL-3005',
    order_id: 'PO-2026-005',
    supplier_id: 'SUP-1005',
    expected_date: '2026-02-14',
    actual_date: '2026-02-18',
    ordered_qty: 3500,
    received_qty: 3500,
    status: 'Delivered',
    remarks: 'Delivered 4 days late due to transportation strike in transit state.'
  },
  {
    id: 'DEL-3006',
    order_id: 'PO-2026-006',
    supplier_id: 'SUP-1006',
    expected_date: '2026-02-24',
    actual_date: '2026-02-24',
    ordered_qty: 400,
    received_qty: 400,
    status: 'Delivered',
    remarks: 'On-time delivery. Sealed drums inspected without leaks.'
  },
  {
    id: 'DEL-3007',
    order_id: 'PO-2026-007',
    supplier_id: 'SUP-1001',
    expected_date: '2026-03-02',
    actual_date: '2026-03-02',
    ordered_qty: 150,
    received_qty: 150,
    status: 'Delivered',
    remarks: 'On-time delivery. Wall thickness confirmed via ultrasonic test.'
  },
  {
    id: 'DEL-3008',
    order_id: 'PO-2026-008',
    supplier_id: 'SUP-1002',
    expected_date: '2026-03-08',
    actual_date: '2026-03-12',
    ordered_qty: 2500,
    received_qty: 2500,
    status: 'Delivered',
    remarks: 'Delivered 4 days late. Supplier warned about buffer planning.'
  },
  {
    id: 'DEL-3009',
    order_id: 'PO-2026-009',
    supplier_id: 'SUP-1007',
    expected_date: '2026-03-18',
    actual_date: '2026-03-18',
    ordered_qty: 30,
    received_qty: 18,
    status: 'Partially Delivered',
    remarks: 'Partial batch of 18 units accepted. Remaining 12 units promised by next month.'
  },
  {
    id: 'DEL-3010',
    order_id: 'PO-2026-010',
    supplier_id: 'SUP-1003',
    expected_date: '2026-03-22',
    actual_date: null,
    ordered_qty: 1500,
    received_qty: 0,
    status: 'Delayed',
    remarks: 'Overdue delivery! Supplier alerted; carrier truck had mechanical breakdown.'
  },
  {
    id: 'DEL-3011',
    order_id: 'PO-2026-011',
    supplier_id: 'SUP-1004',
    expected_date: '2026-03-28',
    actual_date: null,
    ordered_qty: 4000,
    received_qty: 0,
    status: 'Delayed',
    remarks: 'Overdue shipment pending tooling completion at manufacturer.'
  },
  {
    id: 'DEL-3012',
    order_id: 'PO-2026-012',
    supplier_id: 'SUP-1005',
    expected_date: '2026-04-05',
    actual_date: null,
    ordered_qty: 1800,
    received_qty: 0,
    status: 'Pending',
    remarks: 'Production scheduled; tracking number pending.'
  },
  {
    id: 'DEL-3013',
    order_id: 'PO-2026-013',
    supplier_id: 'SUP-1006',
    expected_date: '2026-04-10',
    actual_date: null,
    ordered_qty: 10,
    received_qty: 0,
    status: 'Pending',
    remarks: 'Purchase order acknowledged and scheduled.'
  },
  {
    id: 'DEL-3014',
    order_id: 'PO-2026-014',
    supplier_id: 'SUP-1008',
    expected_date: '2026-02-25',
    actual_date: null,
    ordered_qty: 200,
    received_qty: 0,
    status: 'Cancelled',
    remarks: 'Purchase order formally cancelled.'
  }
];

const INITIAL_HISTORY = [
  {
    id: 'HIST-1',
    delivery_id: 'DEL-3001',
    previous_status: 'Pending',
    new_status: 'Delivered',
    change_date: '2026-01-19 14:30:00',
    notes: 'Received full consignment 1 day early.'
  },
  {
    id: 'HIST-2',
    delivery_id: 'DEL-3009',
    previous_status: 'Pending',
    new_status: 'Partially Delivered',
    change_date: '2026-03-18 11:15:00',
    notes: 'Received partial quantity: 18 out of 30 units.'
  },
  {
    id: 'HIST-3',
    delivery_id: 'DEL-3010',
    previous_status: 'Pending',
    new_status: 'Delayed',
    change_date: '2026-03-23 09:00:00',
    notes: 'System auto-flagged order as overdue by 1 day.'
  }
];

const INITIAL_ACTIVITY = [
  {
    id: 'ACT-101',
    timestamp: '2026-03-23 10:15:30',
    userName: 'Rajesh Sengupta',
    role: 'Admin',
    action: 'SYSTEM_AUDIT',
    target: 'SUP-1008',
    status: 'PERMITTED',
    description: 'System-wide compliance audit executed and safety constraints verified.'
  },
  {
    id: 'ACT-102',
    timestamp: '2026-03-23 11:22:10',
    userName: 'Priya Menon',
    role: 'Purchasing_Manager',
    action: 'CREATE_PO',
    target: 'PO-2026-013',
    status: 'PERMITTED',
    description: 'Created Purchase Order PO-2026-013 for Surface Phosphating Chemical Dip.'
  },
  {
    id: 'ACT-103',
    timestamp: '2026-03-23 12:45:00',
    userName: 'Priya Menon',
    role: 'Purchasing_Manager',
    action: 'DELETE_SUPPLIER_ATTEMPT',
    target: 'SUP-1004',
    status: 'BLOCKED',
    description: 'Deactivation blocked: Purchasing Officer role lacks administrative deletion rights.'
  },
  {
    id: 'ACT-104',
    timestamp: '2026-03-23 14:05:40',
    userName: 'Priya Menon',
    role: 'Purchasing_Manager',
    action: 'UPDATE_DELIVERY',
    target: 'DEL-3009',
    status: 'PERMITTED',
    description: 'Recorded partial goods receipt of 18 units for Zen Packaging.'
  },
  {
    id: 'ACT-105',
    timestamp: '2026-03-23 15:40:00',
    userName: 'Amit Deshmukh',
    role: 'Auditor',
    action: 'AUDIT_SCORECARD',
    target: 'Supplier Benchmark',
    status: 'PERMITTED',
    description: 'Audited Q1 supplier reliability scorecards and delay penalties.'
  }
];

// Data Store Controller
class DataStore {
  static init() {
    if (!localStorage.getItem(STORAGE_KEYS.SUPPLIERS)) {
      localStorage.setItem(STORAGE_KEYS.SUPPLIERS, JSON.stringify(INITIAL_SUPPLIERS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.ORDERS)) {
      localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(INITIAL_ORDERS));
    }
    if (!localStorage.getItem(STORAGE_KEYS.DELIVERIES)) {
      localStorage.setItem(STORAGE_KEYS.DELIVERIES, JSON.stringify(INITIAL_DELIVERIES));
    }
    if (!localStorage.getItem(STORAGE_KEYS.HISTORY)) {
      localStorage.setItem(STORAGE_KEYS.HISTORY, JSON.stringify(INITIAL_HISTORY));
    }
    if (!localStorage.getItem(STORAGE_KEYS.ACTIVITY_LOG)) {
      localStorage.setItem(STORAGE_KEYS.ACTIVITY_LOG, JSON.stringify(INITIAL_ACTIVITY));
    }
  }

  static reset() {
    localStorage.setItem(STORAGE_KEYS.SUPPLIERS, JSON.stringify(INITIAL_SUPPLIERS));
    localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(INITIAL_ORDERS));
    localStorage.setItem(STORAGE_KEYS.DELIVERIES, JSON.stringify(INITIAL_DELIVERIES));
    localStorage.setItem(STORAGE_KEYS.HISTORY, JSON.stringify(INITIAL_HISTORY));
    localStorage.setItem(STORAGE_KEYS.ACTIVITY_LOG, JSON.stringify(INITIAL_ACTIVITY));
  }

  static getSuppliers() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.SUPPLIERS) || '[]');
  }

  static saveSuppliers(suppliers) {
    localStorage.setItem(STORAGE_KEYS.SUPPLIERS, JSON.stringify(suppliers));
  }

  static getOrders() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.ORDERS) || '[]');
  }

  static saveOrders(orders) {
    localStorage.setItem(STORAGE_KEYS.ORDERS, JSON.stringify(orders));
  }

  static getDeliveries() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.DELIVERIES) || '[]');
  }

  static saveDeliveries(deliveries) {
    localStorage.setItem(STORAGE_KEYS.DELIVERIES, JSON.stringify(deliveries));
  }

  static getHistory() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.HISTORY) || '[]');
  }

  static addHistory(entry) {
    const history = this.getHistory();
    entry.id = 'HIST-' + (history.length + 1);
    entry.change_date = new Date().toISOString().replace('T', ' ').substring(0, 19);
    history.push(entry);
    localStorage.setItem(STORAGE_KEYS.HISTORY, JSON.stringify(history));
    return entry;
  }

  static getActivityLog() {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.ACTIVITY_LOG) || '[]');
  }

  static logActivity(action, target, description, status = 'PERMITTED') {
    const user = this.getCurrentUser();
    const logs = this.getActivityLog();
    const now = new Date();
    const timestamp = now.toISOString().replace('T', ' ').substring(0, 19);
    const newLog = {
      id: 'ACT-' + (logs.length + 101),
      timestamp,
      userName: user.name,
      role: user.role,
      action,
      target: target || '—',
      status, // 'PERMITTED' or 'BLOCKED'
      description
    };
    logs.unshift(newLog);
    if (logs.length > 80) logs.pop();
    localStorage.setItem(STORAGE_KEYS.ACTIVITY_LOG, JSON.stringify(logs));
    return newLog;
  }

  static getCurrentUser() {
    const defaultUser = DEMO_USERS.admin;
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.CURRENT_USER) || JSON.stringify(defaultUser));
  }

  static setCurrentUser(user) {
    localStorage.setItem(STORAGE_KEYS.CURRENT_USER, JSON.stringify(user));
  }

  static switchUserRole(roleKey) {
    const targetUser = DEMO_USERS[roleKey];
    if (targetUser) {
      this.setCurrentUser(targetUser);
      this.logActivity('SWITCH_ROLE', `Role: ${targetUser.role}`, `Switched session to ${targetUser.name} (${targetUser.role})`, 'PERMITTED');
      window.location.reload();
    }
  }

  static getRoleInfo(role) {
    if (!role) role = this.getCurrentUser().role;
    switch (role) {
      case 'Admin':
        return {
          role: 'Admin',
          roleTitle: 'System Administrator',
          name: 'Rajesh Sengupta',
          badgeClass: 'role-badge-admin',
          badgeText: '👑 Administrator',
          canRegisterSupplier: true,
          canEditSupplier: true,
          canDeleteSupplier: true,
          canCreateOrder: true,
          canEditOrder: true,
          canCancelOrder: true,
          canUpdateDelivery: true,
          canExportReports: true,
          isReadOnly: false,
          summary: 'Full Administrative Privileges. Authorized to register, edit, and delete suppliers, create and cancel purchase orders, update receipts, and inspect audit logs.'
        };
      case 'Purchasing_Manager':
        return {
          role: 'Purchasing_Manager',
          roleTitle: 'Purchasing Officer (Normal User)',
          name: 'Priya Menon',
          badgeClass: 'role-badge-buyer',
          badgeText: '👤 Normal User (Buyer)',
          canRegisterSupplier: true,
          canEditSupplier: true,
          canDeleteSupplier: false,
          canCreateOrder: true,
          canEditOrder: true,
          canCancelOrder: false,
          canUpdateDelivery: true,
          canExportReports: true,
          isReadOnly: false,
          summary: 'Operational Buying Privileges. Authorized to register suppliers, create POs, and record goods receipts. Supplier deletion and PO cancellation are RESTRICTED to Admins.'
        };
      case 'Auditor':
        return {
          role: 'Auditor',
          roleTitle: 'Quality & Compliance Auditor',
          name: 'Amit Deshmukh',
          badgeClass: 'role-badge-auditor',
          badgeText: '🔍 Auditor (Read-Only)',
          canRegisterSupplier: false,
          canEditSupplier: false,
          canDeleteSupplier: false,
          canCreateOrder: false,
          canEditOrder: false,
          canCancelOrder: false,
          canUpdateDelivery: false,
          canExportReports: true,
          isReadOnly: true,
          summary: 'Strict Read-Only Audit Privileges. Authorized to review supplier performance scores, delivery delay metrics, and export compliance reports. Data modifications are DISABLED.'
        };
      default:
        return {
          role: 'User',
          roleTitle: 'Standard User',
          name: 'User',
          badgeClass: 'role-badge-buyer',
          badgeText: '👤 User',
          isReadOnly: false,
          summary: 'Standard user access.'
        };
    }
  }

  static hasRole(role) {
    const user = this.getCurrentUser();
    return user.role === role;
  }

  static canDelete() {
    return this.hasRole('Admin');
  }

  static canCancelOrder() {
    return this.hasRole('Admin');
  }

  static isReadOnly() {
    return this.hasRole('Auditor');
  }

  static canCreateOrEdit() {
    const user = this.getCurrentUser();
    return user.role === 'Admin' || user.role === 'Purchasing_Manager';
  }
}

// Ensure storage initialized on script load
DataStore.init();

// Business Logic Calculations (Exact Formulas)
const CalculationEngine = {
  // Calendar days difference (Date2 - Date1)
  daysBetween(dateStr1, dateStr2) {
    if (!dateStr1 || !dateStr2) return 0;
    const d1 = new Date(dateStr1);
    const d2 = new Date(dateStr2);
    const diffTime = d2.getTime() - d1.getTime();
    return Math.round(diffTime / (1000 * 3600 * 24));
  },

  /**
   * Calculate delay days according to business logic:
   * - Completed deliveries: actual - expected (positive if late, 0 if early/on-time)
   * - Overdue undelivered: today - expected (positive if overdue, 0 if still in future)
   */
  calculateDelayDays(expectedDate, actualDate, status) {
    if (status === 'Cancelled') return { delay: 0, isLate: false, isOverdue: false, text: 'Cancelled' };

    if (actualDate) {
      const diff = this.daysBetween(expectedDate, actualDate);
      if (diff > 0) {
        return { delay: diff, isLate: true, isOverdue: false, text: `${diff} days late` };
      } else if (diff === 0) {
        return { delay: 0, isLate: false, isEarly: false, text: 'On Time' };
      } else {
        return { delay: 0, earlyDays: Math.abs(diff), isLate: false, isEarly: true, text: `${Math.abs(diff)} days early` };
      }
    } else {
      // Not yet delivered
      const today = new Date().toISOString().split('T')[0];
      const diff = this.daysBetween(expectedDate, today);
      if (diff > 0) {
        return { delay: diff, isLate: true, isOverdue: true, text: `${diff} days overdue` };
      } else {
        return { delay: 0, isLate: false, isOverdue: false, text: 'Within schedule' };
      }
    }
  },

  /**
   * Calculate supplier performance metrics based on real records
   */
  getSupplierMetrics(supplierId) {
    const allDeliveries = DataStore.getDeliveries().filter(d => d.supplier_id === supplierId);
    const allOrders = DataStore.getOrders().filter(o => o.supplier_id === supplierId);

    // Filter out cancelled deliveries/orders
    const activeDeliveries = allDeliveries.filter(d => d.status !== 'Cancelled');
    
    // Eligible completed deliveries = only fully delivered records
    const completedDeliveries = activeDeliveries.filter(d => d.status === 'Delivered' && d.actual_date);
    const totalEligible = completedDeliveries.length;

    let onTimeCount = 0;
    let lateCount = 0;
    let sumPositiveDelayDays = 0;

    completedDeliveries.forEach(d => {
      const delayInfo = this.calculateDelayDays(d.expected_date, d.actual_date, d.status);
      if (delayInfo.isLate) {
        lateCount++;
        sumPositiveDelayDays += delayInfo.delay;
      } else {
        onTimeCount++;
      }
    });

    // Formula 1: On-time delivery % = (On-time completed / Total eligible completed) * 100
    const onTimePercentage = totalEligible > 0 ? ((onTimeCount / totalEligible) * 100) : null;

    // Formula 2: Average delay in days = Sum of positive delay days / eligible completed deliveries
    const avgDelayDays = totalEligible > 0 ? (sumPositiveDelayDays / totalEligible) : 0;

    // Formula 3: Quantity fulfilment % = (Total accepted qty / Total ordered qty) * 100 (excluding cancelled)
    let totalOrderedQty = 0;
    let totalReceivedQty = 0;
    activeDeliveries.forEach(d => {
      totalOrderedQty += Number(d.ordered_qty || 0);
      totalReceivedQty += Number(d.received_qty || 0);
    });

    const qtyFulfilmentPercentage = totalOrderedQty > 0 ? ((totalReceivedQty / totalOrderedQty) * 100) : 100;

    // Overall Score out of 100: 70% On-time + 30% Quantity fulfilment
    let overallScore = null;
    let rating = 'Unrated';
    let ratingClass = 'rating-unrated';

    if (totalEligible > 0) {
      overallScore = Math.round((0.7 * onTimePercentage) + (0.3 * Math.min(qtyFulfilmentPercentage, 100)));
      if (overallScore >= 90) {
        rating = 'Excellent';
        ratingClass = 'rating-excellent';
      } else if (overallScore >= 75) {
        rating = 'Good';
        ratingClass = 'rating-good';
      } else if (overallScore >= 60) {
        rating = 'Average';
        ratingClass = 'rating-average';
      } else {
        rating = 'Needs Improvement';
        ratingClass = 'rating-needs-improvement';
      }
    }

    const pendingOrdersCount = activeDeliveries.filter(d => d.status === 'Pending').length;
    const partialOrdersCount = activeDeliveries.filter(d => d.status === 'Partially Delivered').length;
    const delayedOrdersCount = activeDeliveries.filter(d => d.status === 'Delayed').length;

    return {
      supplierId,
      totalOrders: allOrders.length,
      totalEligible,
      onTimeCount,
      lateCount,
      onTimePercentage: onTimePercentage !== null ? onTimePercentage.toFixed(1) : 'N/A',
      onTimePercentageRaw: onTimePercentage,
      avgDelayDays: avgDelayDays.toFixed(1),
      qtyFulfilmentPercentage: qtyFulfilmentPercentage.toFixed(1),
      totalOrderedQty,
      totalReceivedQty,
      overallScore,
      rating,
      ratingClass,
      pendingCount: pendingOrdersCount,
      partialCount: partialOrdersCount,
      delayedCount: delayedOrdersCount
    };
  },

  /**
   * System-wide KPI summary
   */
  getSystemSummary() {
    const suppliers = DataStore.getSuppliers();
    const orders = DataStore.getOrders();
    const deliveries = DataStore.getDeliveries();

    const activeSuppliers = suppliers.filter(s => s.status === 'Active').length;
    const totalOrders = orders.filter(o => o.status !== 'Cancelled').length;
    
    // Awaiting delivery = Pending or Partially Delivered
    const awaitingDelivery = deliveries.filter(d => d.status === 'Pending' || d.status === 'Partially Delivered').length;

    // Delayed orders = deliveries with Delayed status or overdue
    const delayedOrders = deliveries.filter(d => {
      if (d.status === 'Delayed') return true;
      if (d.status === 'Pending') {
        const today = new Date().toISOString().split('T')[0];
        return CalculationEngine.daysBetween(d.expected_date, today) > 0;
      }
      return false;
    }).length;

    // Completed deliveries
    const completedDeliveries = deliveries.filter(d => d.status === 'Delivered' && d.actual_date);
    let onTimeCount = 0;
    completedDeliveries.forEach(d => {
      const info = CalculationEngine.calculateDelayDays(d.expected_date, d.actual_date, d.status);
      if (!info.isLate) onTimeCount++;
    });

    const onTimePercentage = completedDeliveries.length > 0 
      ? ((onTimeCount / completedDeliveries.length) * 100).toFixed(1) 
      : '0.0';

    return {
      activeSuppliers,
      totalSuppliers: suppliers.length,
      totalOrders,
      awaitingDelivery,
      delayedOrders,
      onTimeDeliveries: onTimeCount,
      completedDeliveriesCount: completedDeliveries.length,
      onTimePercentage
    };
  }
};

// UI Formatting & Helper Functions
const UI = {
  formatCurrency(num) {
    if (num === null || num === undefined) return '₹0';
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0
    }).format(num);
  },

  formatDate(dateStr) {
    if (!dateStr) return '—';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
      const d = new Date(parts[0], parts[1] - 1, parts[2]);
      return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    return dateStr;
  },

  getStatusBadge(status) {
    const map = {
      'Active': 'badge-active',
      'Inactive': 'badge-inactive',
      'Pending': 'badge-pending',
      'On Time': 'badge-ontime',
      'Delivered': 'badge-delivered',
      'Completed': 'badge-completed',
      'Delayed': 'badge-delayed',
      'Partially Delivered': 'badge-partial',
      'Cancelled': 'badge-cancelled'
    };
    const cls = map[status] || 'badge-pending';
    return `<span class="badge ${cls}"><span class="badge-dot"></span>${status}</span>`;
  },

  getRatingBadge(rating, ratingClass) {
    return `<span class="rating-pill ${ratingClass}">${rating}</span>`;
  },

  showToast(message, type = 'primary') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div style="flex:1; font-size:0.85rem; font-weight:500;">${message}</div>
      <button style="color:#94a3b8; font-size:1rem; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => {
      if (toast.parentElement) toast.remove();
    }, 4000);
  },

  setupMobileMenu() {
    const toggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
      });
    }
  },

  setupResetDemo() {
    const resetBtn = document.getElementById('btn-reset-demo');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        if (confirm('Reset prototype to default demonstration data? This will restore sample suppliers, orders, and delivery records.')) {
          DataStore.reset();
          UI.showToast('Sample demonstration data successfully restored.', 'success');
          setTimeout(() => window.location.reload(), 600);
        }
      });
    }
  },

  updateUserProfile() {
    const user = DataStore.getCurrentUser();
    const roleInfo = DataStore.getRoleInfo(user.role);
    const avatar = document.querySelector('.user-avatar');
    const nameEl = document.querySelector('.user-name');
    const roleEl = document.querySelector('.user-role');

    if (avatar && user.initials) avatar.textContent = user.initials;
    if (nameEl && user.name) nameEl.textContent = user.name;
    if (roleEl) roleEl.textContent = roleInfo.roleTitle;

    // Render prominent quick role switcher in header
    const headerRight = document.querySelector('.header-right');
    if (headerRight && !document.getElementById('user-role-quick-switcher')) {
      const switcherEl = document.createElement('div');
      switcherEl.id = 'user-role-quick-switcher';
      switcherEl.className = 'quick-role-switcher';
      
      const isAdmin = user.role === 'Admin';
      const isBuyer = user.role === 'Purchasing_Manager';
      const isAuditor = user.role === 'Auditor';

      switcherEl.innerHTML = `
        <button type="button" class="btn-switch-role ${isAdmin ? 'active admin-active' : ''}" onclick="DataStore.switchUserRole('admin')" title="Switch to System Administrator">
          👑 Admin
        </button>
        <button type="button" class="btn-switch-role ${isBuyer ? 'active buyer-active' : ''}" onclick="DataStore.switchUserRole('buyer')" title="Switch to Purchasing Officer (Normal User)">
          👤 Buyer
        </button>
        <button type="button" class="btn-switch-role ${isAuditor ? 'active auditor-active' : ''}" onclick="DataStore.switchUserRole('auditor')" title="Switch to Quality & Compliance Auditor (Read-Only)">
          🔍 Auditor
        </button>
      `;

      headerRight.insertBefore(switcherEl, headerRight.firstChild);
    }

    if (document.getElementById('role-banner-container')) {
      this.renderRoleBanner('role-banner-container');
    }
  },

  renderRoleBanner(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const user = DataStore.getCurrentUser();
    const roleInfo = DataStore.getRoleInfo(user.role);

    let modeClass = 'admin-mode';
    if (user.role === 'Purchasing_Manager') modeClass = 'buyer-mode';
    if (user.role === 'Auditor') modeClass = 'auditor-mode';

    container.innerHTML = `
      <div class="role-capability-banner ${modeClass}">
        <div style="flex:1; min-width:280px;">
          <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <span class="role-badge ${roleInfo.badgeClass}">${roleInfo.badgeText}</span>
            <span style="font-weight:700; color:var(--navy-900); font-size:0.95rem;">Active User: ${user.name}</span>
          </div>
          <div style="font-size:0.82rem; color:var(--slate-600); line-height:1.4;">
            ${roleInfo.summary}
          </div>
        </div>
        <div class="role-tags-list">
          <span class="perm-tag ${roleInfo.canRegisterSupplier ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canRegisterSupplier ? '✓' : '✗'} Register Suppliers
          </span>
          <span class="perm-tag ${roleInfo.canDeleteSupplier ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canDeleteSupplier ? '✓' : '✗'} Deactivate / Delete Suppliers
          </span>
          <span class="perm-tag ${roleInfo.canCreateOrder ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canCreateOrder ? '✓' : '✗'} Create Purchase Orders
          </span>
          <span class="perm-tag ${roleInfo.canCancelOrder ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canCancelOrder ? '✓' : '✗'} Cancel Purchase Orders
          </span>
          <span class="perm-tag ${roleInfo.canUpdateDelivery ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canUpdateDelivery ? '✓' : '✗'} Record Receipts
          </span>
          <span class="perm-tag ${roleInfo.canExportReports ? 'perm-tag-allow' : 'perm-tag-deny'}">
            ${roleInfo.canExportReports ? '✓' : '✗'} Export Reports
          </span>
        </div>
      </div>
    `;
  },

  renderActivityLog(tableBodyId, filterRole = '') {
    const tableBody = document.getElementById(tableBodyId);
    if (!tableBody) return;
    const logs = DataStore.getActivityLog();
    const filtered = filterRole ? logs.filter(l => l.role === filterRole) : logs;

    if (filtered.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--slate-500);">No user activity records found.</td></tr>`;
      return;
    }

    tableBody.innerHTML = filtered.map(log => {
      let roleBadge = 'role-badge-buyer';
      let roleLabel = '👤 Buyer';
      if (log.role === 'Admin') {
        roleBadge = 'role-badge-admin';
        roleLabel = '👑 Admin';
      } else if (log.role === 'Auditor') {
        roleBadge = 'role-badge-auditor';
        roleLabel = '🔍 Auditor';
      }

      const statusBadge = log.status === 'PERMITTED' 
        ? `<span class="status-pill-permitted">✓ Permitted</span>`
        : `<span class="status-pill-blocked">✗ Blocked (No Role Privilege)</span>`;

      return `
        <tr>
          <td style="font-size:0.78rem; color:var(--slate-500); white-space:nowrap;">${log.timestamp}</td>
          <td>
            <div style="font-weight:600; font-size:0.85rem; color:var(--navy-900);">${log.userName}</div>
            <span class="role-badge ${roleBadge}" style="font-size:0.68rem; padding:0.1rem 0.45rem;">${roleLabel}</span>
          </td>
          <td><code style="font-weight:700; font-size:0.75rem; background:var(--slate-100); padding:0.15rem 0.4rem; border-radius:3px;">${log.action}</code></td>
          <td class="cell-code">${log.target}</td>
          <td>${statusBadge}</td>
          <td style="font-size:0.8rem; color:var(--slate-700);">${log.description}</td>
        </tr>
      `;
    }).join('');
  }
};

// Global App Initialization
document.addEventListener('DOMContentLoaded', () => {
  UI.setupMobileMenu();
  UI.setupResetDemo();
  UI.updateUserProfile();
});

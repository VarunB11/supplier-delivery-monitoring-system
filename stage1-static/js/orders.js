/**
 * PURCHASE ORDER MANAGEMENT MODULE (Stage 1 Static)
 * PO Creation, Automatic Total Calculation, Validation & Cancellation
 */

document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.getElementById('orders-table-body');
  const searchInput = document.getElementById('order-search');
  const statusFilter = document.getElementById('order-status-filter');
  const supplierFilter = document.getElementById('order-supplier-filter');
  const totalCountEl = document.getElementById('order-total-count');

  // Modal elements
  const modal = document.getElementById('order-modal');
  const modalTitle = document.getElementById('order-modal-title');
  const form = document.getElementById('order-form');
  const btnCreate = document.getElementById('btn-create-order');
  const btnCloseModal = document.getElementById('btn-close-order-modal');
  const btnCancelModal = document.getElementById('btn-cancel-order-modal');

  // Input fields for calculation
  const qtyInput = document.getElementById('po-qty');
  const priceInput = document.getElementById('po-price');
  const totalInput = document.getElementById('po-total');
  const supplierSelect = document.getElementById('po-supplier');

  // View modal elements
  const viewModal = document.getElementById('view-order-modal');
  const btnCloseViewModal = document.getElementById('btn-close-view-order-modal');

  let currentEditId = null;

  function calculateTotal() {
    if (!qtyInput || !priceInput || !totalInput) return;
    const qty = parseFloat(qtyInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const total = qty * price;
    totalInput.value = UI.formatCurrency(total);
  }

  if (qtyInput) qtyInput.addEventListener('input', calculateTotal);
  if (priceInput) priceInput.addEventListener('input', calculateTotal);

  function populateSupplierDropdowns() {
    const suppliers = DataStore.getSuppliers();
    
    // Filter dropdown on main page
    if (supplierFilter) {
      supplierFilter.innerHTML = '<option value="">All Suppliers</option>' +
        suppliers.map(s => `<option value="${s.id}">${s.company_name} (${s.id})</option>`).join('');
    }

    // Modal supplier dropdown (only active suppliers for new orders)
    if (supplierSelect) {
      const activeSuppliers = suppliers.filter(s => s.status === 'Active');
      supplierSelect.innerHTML = '<option value="">Select an Active Supplier...</option>' +
        activeSuppliers.map(s => `<option value="${s.id}">${s.company_name} (${s.category})</option>`).join('');
    }
  }

  function renderOrders() {
    const orders = DataStore.getOrders();
    const suppliers = DataStore.getSuppliers();
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedStatus = statusFilter ? statusFilter.value : '';
    const selectedSup = supplierFilter ? supplierFilter.value : '';

    const filtered = orders.filter(o => {
      const sup = suppliers.find(s => s.id === o.supplier_id);
      const supName = sup ? sup.company_name.toLowerCase() : '';
      const matchSearch = o.id.toLowerCase().includes(query) ||
                          supName.includes(query) ||
                          o.description.toLowerCase().includes(query);
      const matchStatus = !selectedStatus || o.status === selectedStatus;
      const matchSup = !selectedSup || o.supplier_id === selectedSup;
      return matchSearch && matchStatus && matchSup;
    });

    if (totalCountEl) {
      totalCountEl.textContent = `${filtered.length} of ${orders.length} purchase orders`;
    }

    if (!tableBody) return;

    if (filtered.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align:center; padding:3rem 1rem; color:var(--slate-500);">
            <div style="font-size:1.5rem; margin-bottom:0.5rem;">📦</div>
            <p style="font-weight:600;">No purchase orders found</p>
            <p style="font-size:0.8rem;">Try adjusting filters or create a new purchase order.</p>
          </td>
        </tr>
      `;
      return;
    }

    tableBody.innerHTML = filtered.map(o => {
      const sup = suppliers.find(s => s.id === o.supplier_id) || { company_name: 'Unknown' };
      const isDelayed = o.status === 'Delayed';
      return `
        <tr class="${isDelayed ? 'row-delayed' : ''}">
          <td class="cell-code">${o.id}</td>
          <td>
            <div class="cell-strong">${sup.company_name}</div>
            <div class="cell-muted">${o.supplier_id}</div>
          </td>
          <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${o.description}">
            ${o.description}
          </td>
          <td>${Number(o.quantity).toLocaleString()}</td>
          <td class="cell-strong">${UI.formatCurrency(o.total_amount)}</td>
          <td>
            <div>${UI.formatDate(o.expected_date)}</div>
            <div class="cell-muted">Ordered: ${UI.formatDate(o.order_date)}</div>
          </td>
          <td>${UI.getStatusBadge(o.status)}</td>
          <td>
            <div style="display:flex; gap:0.4rem;">
              <button class="btn btn-icon btn-sm" title="View Order" onclick="viewOrder('${o.id}')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
              ${!DataStore.isReadOnly() && o.status !== 'Completed' && o.status !== 'Cancelled' ? `
                <button class="btn btn-icon btn-sm" title="Edit Order" onclick="editOrder('${o.id}')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </button>
                <button class="btn btn-icon btn-sm" title="${DataStore.canCancelOrder() ? 'Cancel Order' : 'Restricted: Admin Only'}" onclick="cancelOrderPrompt('${o.id}')">
                  <svg width="15" height="15" fill="none" stroke="${DataStore.canCancelOrder() ? 'currentColor' : '#94a3b8'}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              ` : DataStore.isReadOnly() ? `
                <span class="badge" style="background:var(--slate-100); color:var(--slate-500); font-size:0.7rem;">Read-Only</span>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Open modal for Create PO
  if (btnCreate) {
    if (DataStore.isReadOnly()) {
      btnCreate.style.opacity = '0.6';
      btnCreate.title = 'Auditor Mode: Creation disabled';
    }
    btnCreate.addEventListener('click', () => {
      if (DataStore.isReadOnly()) {
        DataStore.logActivity('CREATE_ORDER_ATTEMPT', 'New PO', 'Action blocked: Auditor role cannot create purchase orders.', 'BLOCKED');
        alert('Access Restricted (RBAC Violation):\n\nQuality & Compliance Auditors have read-only privileges. Purchase order generation is restricted to Purchasing Officers and Administrators.');
        return;
      }

      currentEditId = null;
      modalTitle.textContent = 'Create New Purchase Order';
      form.reset();

      // Generate unique PO ID
      const orders = DataStore.getOrders();
      const currentYear = new Date().getFullYear();
      const nextNum = String(orders.length + 1).padStart(3, '0');
      document.getElementById('po-id').value = `PO-${currentYear}-${nextNum}`;
      document.getElementById('po-id').readOnly = false;

      // Default dates
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('po-order-date').value = today;
      
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 14);
      document.getElementById('po-expected-date').value = futureDate.toISOString().split('T')[0];

      calculateTotal();
      modal.classList.add('active');
    });
  }

  // Close modals
  [btnCloseModal, btnCancelModal].forEach(b => {
    if (b) b.addEventListener('click', () => modal.classList.remove('active'));
  });

  if (btnCloseViewModal) {
    btnCloseViewModal.addEventListener('click', () => viewModal.classList.remove('active'));
  }

  // Handle Form Submission
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const id = document.getElementById('po-id').value.trim().toUpperCase();
      const supplier_id = document.getElementById('po-supplier').value;
      const order_date = document.getElementById('po-order-date').value;
      const expected_date = document.getElementById('po-expected-date').value;
      const description = document.getElementById('po-description').value.trim();
      const quantity = parseFloat(document.getElementById('po-qty').value);
      const unit_price = parseFloat(document.getElementById('po-price').value);
      const notes = document.getElementById('po-notes').value.trim();

      // Validation
      if (!id || !supplier_id || !order_date || !expected_date || !description) {
        UI.showToast('Please fill in all mandatory fields.', 'danger');
        return;
      }

      if (isNaN(quantity) || quantity <= 0) {
        UI.showToast('Quantity must be a positive number greater than zero.', 'danger');
        return;
      }

      if (isNaN(unit_price) || unit_price < 0) {
        UI.showToast('Unit price cannot be negative.', 'danger');
        return;
      }

      if (new Date(expected_date) < new Date(order_date)) {
        UI.showToast('Expected delivery date cannot be earlier than order date.', 'danger');
        return;
      }

      const total_amount = quantity * unit_price;
      const orders = DataStore.getOrders();
      const deliveries = DataStore.getDeliveries();

      if (currentEditId) {
        // Edit existing PO
        const index = orders.findIndex(o => o.id === currentEditId);
        if (index !== -1) {
          orders[index] = {
            ...orders[index],
            supplier_id,
            order_date,
            expected_date,
            description,
            quantity,
            unit_price,
            total_amount,
            notes
          };
          DataStore.saveOrders(orders);

          // Synchronize delivery record
          const del = deliveries.find(d => d.order_id === currentEditId);
          if (del) {
            del.supplier_id = supplier_id;
            del.expected_date = expected_date;
            del.ordered_qty = quantity;
            DataStore.saveDeliveries(deliveries);
          }

          DataStore.logActivity('EDIT_PO', id, `Updated purchase order ${id} terms: ${quantity} units @ ₹${unit_price} (Valuation: ₹${total_amount.toLocaleString()}).`, 'PERMITTED');
          UI.showToast(`Purchase Order ${id} updated successfully.`, 'success');
        }
      } else {
        // Prevent duplicate PO ID
        if (orders.some(o => o.id === id)) {
          UI.showToast(`Purchase Order ID ${id} already exists. Please choose a unique identifier.`, 'danger');
          return;
        }

        const newOrder = {
          id,
          supplier_id,
          order_date,
          expected_date,
          description,
          quantity,
          unit_price,
          total_amount,
          status: 'Pending',
          notes
        };

        orders.unshift(newOrder);
        DataStore.saveOrders(orders);

        // Auto-generate linked Delivery Record in Pending status
        const nextDelNum = deliveries.length + 1;
        const newDelivery = {
          id: `DEL-${3000 + nextDelNum}`,
          order_id: id,
          supplier_id,
          expected_date,
          actual_date: null,
          ordered_qty: quantity,
          received_qty: 0,
          status: 'Pending',
          remarks: 'New order awaiting supplier dispatch.'
        };

        deliveries.unshift(newDelivery);
        DataStore.saveDeliveries(deliveries);

        // Record history
        DataStore.addHistory({
          delivery_id: newDelivery.id,
          previous_status: 'None',
          new_status: 'Pending',
          notes: `Purchase order ${id} created.`
        });

        DataStore.logActivity('CREATE_PO', id, `Created purchase order ${id} for "${description}" (${quantity} units, ₹${total_amount.toLocaleString()}).`, 'PERMITTED');
        UI.showToast(`Purchase order ${id} created and delivery tracking initiated.`, 'success');
      }

      modal.classList.remove('active');
      renderOrders();
    });
  }

  // View Order Details
  window.viewOrder = function(id) {
    const order = DataStore.getOrders().find(o => o.id === id);
    if (!order) return;

    const supplier = DataStore.getSuppliers().find(s => s.id === order.supplier_id) || { company_name: 'Unknown' };
    const delivery = DataStore.getDeliveries().find(d => d.order_id === id);

    document.getElementById('view-po-id').textContent = order.id;
    document.getElementById('view-po-supplier').textContent = `${supplier.company_name} (${order.supplier_id})`;
    document.getElementById('view-po-order-date').textContent = UI.formatDate(order.order_date);
    document.getElementById('view-po-expected-date').textContent = UI.formatDate(order.expected_date);
    document.getElementById('view-po-desc').textContent = order.description;
    document.getElementById('view-po-qty').textContent = `${Number(order.quantity).toLocaleString()} units`;
    document.getElementById('view-po-price').textContent = UI.formatCurrency(order.unit_price);
    document.getElementById('view-po-total').textContent = UI.formatCurrency(order.total_amount);
    document.getElementById('view-po-status').innerHTML = UI.getStatusBadge(order.status);
    document.getElementById('view-po-notes').textContent = order.notes || 'No special notes recorded.';

    // Delivery tracking summary
    if (delivery) {
      document.getElementById('view-po-del-id').textContent = delivery.id;
      document.getElementById('view-po-del-status').innerHTML = UI.getStatusBadge(delivery.status);
      document.getElementById('view-po-del-received').textContent = `${delivery.received_qty} / ${delivery.ordered_qty} units`;
      document.getElementById('view-po-del-actual').textContent = delivery.actual_date ? UI.formatDate(delivery.actual_date) : 'Pending';
      document.getElementById('view-po-del-remarks').textContent = delivery.remarks || '—';
    }

    viewModal.classList.add('active');
  };

  // Edit Order
  window.editOrder = function(id) {
    if (DataStore.isReadOnly()) {
      DataStore.logActivity('EDIT_ORDER_ATTEMPT', id, 'Action blocked: Auditor role cannot modify purchase orders.', 'BLOCKED');
      alert('Access Restricted (RBAC Violation):\n\nQuality & Compliance Auditors have read-only privileges. Purchase order editing requires Purchasing Officer or Administrator privileges.');
      return;
    }

    const order = DataStore.getOrders().find(o => o.id === id);
    if (!order) return;

    currentEditId = id;
    modalTitle.textContent = `Edit Purchase Order: ${order.id}`;

    document.getElementById('po-id').value = order.id;
    document.getElementById('po-id').readOnly = true;
    document.getElementById('po-supplier').value = order.supplier_id;
    document.getElementById('po-order-date').value = order.order_date;
    document.getElementById('po-expected-date').value = order.expected_date;
    document.getElementById('po-description').value = order.description;
    document.getElementById('po-qty').value = order.quantity;
    document.getElementById('po-price').value = order.unit_price;
    document.getElementById('po-notes').value = order.notes || '';

    calculateTotal();
    modal.classList.add('active');
  };

  // Cancel Order Prompt (RBAC Restricted to Admin Only)
  window.cancelOrderPrompt = function(id) {
    const currentUser = DataStore.getCurrentUser();
    const roleInfo = DataStore.getRoleInfo(currentUser.role);

    // RBAC Check: Only Admin can cancel purchase orders!
    if (!DataStore.canCancelOrder()) {
      DataStore.logActivity(
        'CANCEL_ORDER_ATTEMPT',
        id,
        `Blocked order cancellation by ${currentUser.name} (${roleInfo.roleTitle}). Admin authorization required.`,
        'BLOCKED'
      );
      alert(
        `⛔ Access Denied (RBAC Policy Restriction):\n\n` +
        `Current Role: ${roleInfo.roleTitle}\n` +
        `User: ${currentUser.name}\n\n` +
        `Cancelling confirmed purchase orders requires System Administrator (Rajesh Sengupta) authorization.\n` +
        `Purchasing Officers (${currentUser.name}) can generate and edit orders, but contract cancellation must be approved by the Administrator.\n\n` +
        `This unauthorized attempt has been recorded in the User Activity Trail.`
      );
      return;
    }

    const order = DataStore.getOrders().find(o => o.id === id);
    if (!order) return;

    if (confirm(`Safety Warning (System Administrator):\n\nAre you sure you want to cancel Purchase Order ${id}?\n\nThis will update both the order and delivery tracking record to "Cancelled".`)) {
      const orders = DataStore.getOrders();
      const o = orders.find(item => item.id === id);
      if (o) {
        o.status = 'Cancelled';
        DataStore.saveOrders(orders);
      }

      const deliveries = DataStore.getDeliveries();
      const d = deliveries.find(item => item.order_id === id);
      if (d) {
        const oldStatus = d.status;
        d.status = 'Cancelled';
        d.remarks = `Order cancelled by Administrator (${currentUser.name}).`;
        DataStore.saveDeliveries(deliveries);

        DataStore.addHistory({
          delivery_id: d.id,
          previous_status: oldStatus,
          new_status: 'Cancelled',
          notes: `Purchase order ${id} cancelled by Administrator.`
        });
      }

      DataStore.logActivity('CANCEL_PO', id, `System Administrator cancelled Purchase Order ${id}.`, 'PERMITTED');
      UI.showToast(`Purchase order ${id} has been cancelled.`, 'warning');
      renderOrders();
    }
  };

  // Listeners
  if (searchInput) searchInput.addEventListener('input', renderOrders);
  if (statusFilter) statusFilter.addEventListener('change', renderOrders);
  if (supplierFilter) supplierFilter.addEventListener('change', renderOrders);

  populateSupplierDropdowns();
  renderOrders();
});

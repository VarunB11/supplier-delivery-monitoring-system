/**
 * SUPPLIER MANAGEMENT MODULE (Stage 1 Static)
 * Full CRUD, Filter, Search & Safe Deactivation
 */

document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.getElementById('suppliers-table-body');
  const searchInput = document.getElementById('supplier-search');
  const categoryFilter = document.getElementById('supplier-category-filter');
  const statusFilter = document.getElementById('supplier-status-filter');
  const totalCountEl = document.getElementById('supplier-total-count');

  // Modal elements
  const modal = document.getElementById('supplier-modal');
  const modalTitle = document.getElementById('supplier-modal-title');
  const form = document.getElementById('supplier-form');
  const btnAdd = document.getElementById('btn-add-supplier');
  const btnCloseModal = document.getElementById('btn-close-supplier-modal');
  const btnCancelModal = document.getElementById('btn-cancel-supplier-modal');

  // View modal elements
  const viewModal = document.getElementById('view-supplier-modal');
  const btnCloseViewModal = document.getElementById('btn-close-view-supplier-modal');

  let currentEditId = null;

  function renderSuppliers() {
    const suppliers = DataStore.getSuppliers();
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const selectedCat = categoryFilter ? categoryFilter.value : '';
    const selectedStatus = statusFilter ? statusFilter.value : '';

    const filtered = suppliers.filter(s => {
      const matchSearch = s.company_name.toLowerCase().includes(query) ||
                          s.id.toLowerCase().includes(query) ||
                          s.contact_person.toLowerCase().includes(query) ||
                          s.city.toLowerCase().includes(query);
      const matchCat = !selectedCat || s.category === selectedCat;
      const matchStatus = !selectedStatus || s.status === selectedStatus;
      return matchSearch && matchCat && matchStatus;
    });

    if (totalCountEl) {
      totalCountEl.textContent = `${filtered.length} of ${suppliers.length} suppliers`;
    }

    if (!tableBody) return;

    if (filtered.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align:center; padding:3rem 1rem; color:var(--slate-500);">
            <div style="font-size:1.5rem; margin-bottom:0.5rem;">🔍</div>
            <p style="font-weight:600;">No suppliers found</p>
            <p style="font-size:0.8rem;">Try adjusting your search criteria or filter settings.</p>
          </td>
        </tr>
      `;
      return;
    }

    tableBody.innerHTML = filtered.map(s => {
      const orders = DataStore.getOrders().filter(o => o.supplier_id === s.id);
      const isAuditor = DataStore.isReadOnly();
      const canDelete = DataStore.canDelete();

      return `
        <tr>
          <td class="cell-code">${s.id}</td>
          <td>
            <div class="cell-strong">${s.company_name}</div>
            <div class="cell-muted">${s.city}</div>
          </td>
          <td>
            <div>${s.contact_person}</div>
            <div class="cell-muted">${s.email}</div>
          </td>
          <td>${s.category}</td>
          <td>${orders.length} order(s)</td>
          <td>${UI.getStatusBadge(s.status)}</td>
          <td>
            <div style="display:flex; gap:0.4rem;">
              <button class="btn btn-icon btn-sm" title="View Details" onclick="viewSupplier('${s.id}')">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
              ${!isAuditor ? `
                <button class="btn btn-icon btn-sm" title="Edit Supplier" onclick="editSupplier('${s.id}')">
                  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </button>
                <button class="btn btn-icon btn-sm" title="${canDelete ? 'Deactivate / Delete' : 'Restricted: Admin Only'}" onclick="deleteSupplierPrompt('${s.id}')">
                  <svg width="15" height="15" fill="none" stroke="${canDelete ? 'currentColor' : '#94a3b8'}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              ` : `
                <span class="badge" style="background:var(--slate-100); color:var(--slate-500); font-size:0.7rem;">Read-Only</span>
              `}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // Populate categories filter
  function populateCategories() {
    if (!categoryFilter) return;
    const suppliers = DataStore.getSuppliers();
    const categories = [...new Set(suppliers.map(s => s.category))].sort();
    categoryFilter.innerHTML = '<option value="">All Categories</option>' +
      categories.map(c => `<option value="${c}">${c}</option>`).join('');
  }

  // Open modal for Create
  if (btnAdd) {
    if (DataStore.isReadOnly()) {
      btnAdd.style.opacity = '0.6';
      btnAdd.title = 'Auditor Mode: Registration disabled';
    }
    btnAdd.addEventListener('click', () => {
      if (DataStore.isReadOnly()) {
        DataStore.logActivity('REGISTER_SUPPLIER_ATTEMPT', 'New Supplier', 'Action blocked: Auditor role has read-only access and cannot register vendors.', 'BLOCKED');
        alert('Access Restricted (RBAC Violation):\n\nQuality & Compliance Auditors have read-only privileges. Supplier registration can only be executed by Purchasing Officers or System Administrators.');
        return;
      }

      currentEditId = null;
      modalTitle.textContent = 'Register New Supplier';
      form.reset();
      
      // Auto-generate next ID
      const suppliers = DataStore.getSuppliers();
      const nextNum = 1000 + suppliers.length + 1;
      document.getElementById('sup-id').value = `SUP-${nextNum}`;
      document.getElementById('sup-id').readOnly = false;
      document.getElementById('sup-status').value = 'Active';

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

      if (DataStore.isReadOnly()) {
        alert('Access Restricted: Auditor cannot save modifications.');
        return;
      }

      const id = document.getElementById('sup-id').value.trim().toUpperCase();
      const company_name = document.getElementById('sup-company').value.trim();
      const contact_person = document.getElementById('sup-contact').value.trim();
      const email = document.getElementById('sup-email').value.trim();
      const phone = document.getElementById('sup-phone').value.trim();
      const address = document.getElementById('sup-address').value.trim();
      const city = document.getElementById('sup-city').value.trim();
      const category = document.getElementById('sup-category').value.trim();
      const status = document.getElementById('sup-status').value;

      if (!id || !company_name || !contact_person || !email || !category || !city) {
        UI.showToast('Please fill in all mandatory fields.', 'danger');
        return;
      }

      const suppliers = DataStore.getSuppliers();

      if (currentEditId) {
        // Update existing
        const index = suppliers.findIndex(s => s.id === currentEditId);
        if (index !== -1) {
          suppliers[index] = {
            ...suppliers[index],
            company_name,
            contact_person,
            email,
            phone,
            address,
            city,
            category,
            status
          };
          DataStore.saveSuppliers(suppliers);
          DataStore.logActivity('EDIT_SUPPLIER', id, `Updated profile and contact information for supplier "${company_name}" (${category}).`, 'PERMITTED');
          UI.showToast(`Supplier ${id} updated successfully.`, 'success');
        }
      } else {
        // Prevent duplicate ID
        if (suppliers.some(s => s.id === id)) {
          UI.showToast(`Supplier ID ${id} already exists. Please choose a unique ID.`, 'danger');
          return;
        }

        const newSupplier = {
          id,
          company_name,
          contact_person,
          email,
          phone,
          address,
          city,
          category,
          registration_date: new Date().toISOString().split('T')[0],
          status
        };

        suppliers.unshift(newSupplier);
        DataStore.saveSuppliers(suppliers);
        DataStore.logActivity('REGISTER_SUPPLIER', id, `Registered new supplier "${company_name}" under category "${category}" in ${city}.`, 'PERMITTED');
        UI.showToast(`Supplier ${company_name} registered successfully.`, 'success');
      }

      modal.classList.remove('active');
      renderSuppliers();
    });
  }

  // View Supplier Details
  window.viewSupplier = function(id) {
    const supplier = DataStore.getSuppliers().find(s => s.id === id);
    if (!supplier) return;

    const metrics = CalculationEngine.getSupplierMetrics(id);
    const orders = DataStore.getOrders().filter(o => o.supplier_id === id);

    document.getElementById('view-sup-title').textContent = `${supplier.company_name} (${supplier.id})`;
    document.getElementById('view-sup-category').textContent = supplier.category;
    document.getElementById('view-sup-contact').textContent = supplier.contact_person;
    document.getElementById('view-sup-email').textContent = supplier.email;
    document.getElementById('view-sup-phone').textContent = supplier.phone || '—';
    document.getElementById('view-sup-city').textContent = `${supplier.address ? supplier.address + ', ' : ''}${supplier.city}`;
    document.getElementById('view-sup-status').innerHTML = UI.getStatusBadge(supplier.status);
    document.getElementById('view-sup-reg').textContent = UI.formatDate(supplier.registration_date);

    document.getElementById('view-sup-orders-count').textContent = orders.length;
    document.getElementById('view-sup-ontime-rate').textContent = metrics.onTimePercentage !== 'N/A' ? `${metrics.onTimePercentage}%` : 'N/A';
    document.getElementById('view-sup-rating').innerHTML = UI.getRatingBadge(metrics.rating, metrics.ratingClass);

    viewModal.classList.add('active');
  };

  // Edit Supplier
  window.editSupplier = function(id) {
    if (DataStore.isReadOnly()) {
      DataStore.logActivity('EDIT_SUPPLIER_ATTEMPT', id, 'Action blocked: Auditor role cannot modify supplier details.', 'BLOCKED');
      alert('Access Restricted (RBAC Violation):\n\nQuality & Compliance Auditors have read-only privileges. Supplier editing requires Purchasing Officer or Administrator privileges.');
      return;
    }

    const supplier = DataStore.getSuppliers().find(s => s.id === id);
    if (!supplier) return;

    currentEditId = id;
    modalTitle.textContent = `Edit Supplier Details: ${supplier.id}`;

    document.getElementById('sup-id').value = supplier.id;
    document.getElementById('sup-id').readOnly = true;
    document.getElementById('sup-company').value = supplier.company_name;
    document.getElementById('sup-contact').value = supplier.contact_person;
    document.getElementById('sup-email').value = supplier.email;
    document.getElementById('sup-phone').value = supplier.phone || '';
    document.getElementById('sup-address').value = supplier.address || '';
    document.getElementById('sup-city').value = supplier.city || '';
    document.getElementById('sup-category').value = supplier.category;
    document.getElementById('sup-status').value = supplier.status;

    modal.classList.add('active');
  };

  // Delete or Deactivate Supplier (Safe Constraint & RBAC Logic)
  window.deleteSupplierPrompt = function(id) {
    const currentUser = DataStore.getCurrentUser();
    const roleInfo = DataStore.getRoleInfo(currentUser.role);

    // RBAC Check: Only Admin can delete or deactivate suppliers!
    if (!DataStore.canDelete()) {
      DataStore.logActivity(
        'DELETE_SUPPLIER_ATTEMPT',
        id,
        `Blocked deactivation attempt by ${currentUser.name} (${roleInfo.roleTitle}). Admin privileges required.`,
        'BLOCKED'
      );
      alert(
        `⛔ Access Denied (RBAC Policy Restriction):\n\n` +
        `Current Role: ${roleInfo.roleTitle}\n` +
        `User: ${currentUser.name}\n\n` +
        `Only System Administrators (Rajesh Sengupta) are authorized to deactivate or delete suppliers.\n` +
        `Purchasing Officers (${currentUser.name}) can register and edit suppliers, but cannot remove them from the vendor registry.\n\n` +
        `This unauthorized attempt has been recorded in the User Activity Trail.`
      );
      return;
    }

    const supplier = DataStore.getSuppliers().find(s => s.id === id);
    if (!supplier) return;

    const linkedOrders = DataStore.getOrders().filter(o => o.supplier_id === id);

    if (linkedOrders.length > 0) {
      const confirmDeactivate = confirm(
        `Safety Constraint Notice (System Administrator):\n\nSupplier "${supplier.company_name}" has ${linkedOrders.length} linked purchase order(s).\n` +
        `Direct deletion is prevented to preserve historical audit data.\n\n` +
        `Would you like to deactivate this supplier instead?`
      );

      if (confirmDeactivate) {
        const suppliers = DataStore.getSuppliers();
        const sup = suppliers.find(s => s.id === id);
        if (sup) {
          sup.status = 'Inactive';
          DataStore.saveSuppliers(suppliers);
          DataStore.logActivity('DEACTIVATE_SUPPLIER', id, `Administrator deactivated supplier "${sup.company_name}" (linked to ${linkedOrders.length} orders).`, 'PERMITTED');
          UI.showToast(`Supplier ${sup.company_name} has been deactivated.`, 'warning');
          renderSuppliers();
        }
      }
    } else {
      if (confirm(`Are you sure you want to delete supplier "${supplier.company_name}"? This action cannot be undone.`)) {
        const suppliers = DataStore.getSuppliers().filter(s => s.id !== id);
        DataStore.saveSuppliers(suppliers);
        DataStore.logActivity('DELETE_SUPPLIER', id, `Administrator permanently deleted unlinked supplier "${supplier.company_name}".`, 'PERMITTED');
        UI.showToast(`Supplier deleted.`, 'success');
        renderSuppliers();
      }
    }
  };


  // Search & filter listeners
  if (searchInput) searchInput.addEventListener('input', renderSuppliers);
  if (categoryFilter) categoryFilter.addEventListener('change', renderSuppliers);
  if (statusFilter) statusFilter.addEventListener('change', renderSuppliers);

  // Initial populate & render
  populateCategories();
  renderSuppliers();
});

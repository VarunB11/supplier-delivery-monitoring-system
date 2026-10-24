/**
 * SDPMS - Stage 2 Dynamic Client-Side Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile navigation menu drawer toggle
    const toggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });
    }

    // 2. Real-time PO Total Calculation
    const qtyInput = document.getElementById('calc-qty');
    const priceInput = document.getElementById('calc-price');
    const totalInput = document.getElementById('calc-total');

    function calculateOrderTotal() {
        if (!qtyInput || !priceInput || !totalInput) return;
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = qty * price;
        totalInput.value = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    if (qtyInput && priceInput) {
        qtyInput.addEventListener('input', calculateOrderTotal);
        priceInput.addEventListener('input', calculateOrderTotal);
        calculateOrderTotal();
    }

    // 3. Confirmation Dialogs for destructive actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 4. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-box');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

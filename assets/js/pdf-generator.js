document.addEventListener('DOMContentLoaded', function() {
    const printOrderBtn = document.getElementById('print-order-btn');
    
    if (printOrderBtn) {
        printOrderBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    const exportButtons = document.querySelectorAll('.export-pdf-btn');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            window.location.href = `export.php?action=pdf&id=${orderId}`;
        });
    });
});
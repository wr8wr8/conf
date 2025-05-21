document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
                e.preventDefault();
            }
        });
    });

    const dataTables = document.querySelectorAll('.data-table');
    dataTables.forEach(table => {
        const searchInput = document.querySelector(`#${table.getAttribute('id')}-search`);
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchVal = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchVal) ? '' : 'none';
                });
            });
        }
    });

    const groupSelect = document.querySelector('#group-select');
    if (groupSelect) {
        groupSelect.addEventListener('change', function() {
            const groupId = this.value;
            window.location.href = window.location.pathname + '?group_id=' + groupId;
        });
    }

    const orderItemForm = document.querySelector('#add-order-item-form');
    if (orderItemForm) {
        const itemSelect = orderItemForm.querySelector('#item_id');
        const priceInput = orderItemForm.querySelector('#price');
        
        itemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price) {
                priceInput.value = price;
            }
        });
    }

    const fileInputs = document.querySelectorAll('.custom-file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0].name;
            const label = this.nextElementSibling;
            label.textContent = fileName;
        });
    });

    const chartCanvas = document.getElementById('orders-chart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        
        fetch('api/order_stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Количество заявок',
                            data: data.counts,
                            backgroundColor: 'rgba(0, 0, 0, 0.1)',
                            borderColor: 'rgba(0, 0, 0, 0.8)',
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            });
    }

    const exportPdfBtn = document.getElementById('export-pdf-btn');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            
            fetch(`api/generate_pdf.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.open(data.file_url, '_blank');
                    } else {
                        alert('Ошибка при создании PDF: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при создании PDF');
                });
        });
    }
});
/**
 * Online Complaint Management System - Core JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
        });
    }

    // 2. Auto Dismiss Flash Alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // 3. Client-Side Instant Table Search & Filter
    const liveSearchInput = document.getElementById('liveTableSearch');
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const complaintsTable = document.getElementById('complaintsTable');

    if (complaintsTable && (liveSearchInput || statusFilter || priorityFilter)) {
        function filterTable() {
            const query = (liveSearchInput?.value || '').toLowerCase().trim();
            const statusVal = (statusFilter?.value || 'ALL').toUpperCase();
            const priorityVal = (priorityFilter?.value || 'ALL').toUpperCase();

            const rows = complaintsTable.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.classList.contains('no-results-row')) return;

                const text = row.innerText.toLowerCase();
                const rowStatus = (row.dataset.status || '').toUpperCase();
                const rowPriority = (row.dataset.priority || '').toUpperCase();

                const matchesQuery = !query || text.includes(query);
                const matchesStatus = statusVal === 'ALL' || rowStatus === statusVal;
                const matchesPriority = priorityVal === 'ALL' || rowPriority === priorityVal;

                if (matchesQuery && matchesStatus && matchesPriority) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle empty state row
            let noResultRow = complaintsTable.querySelector('.no-results-row');
            if (visibleCount === 0) {
                if (!noResultRow) {
                    noResultRow = document.createElement('tr');
                    noResultRow.className = 'no-results-row';
                    noResultRow.innerHTML = `<td colspan="7" style="text-align: center; padding: 2.5rem; color: #64748b;">
                        <svg style="width: 40px; height: 40px; margin: 0 auto 0.5rem; display: block; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <strong>No complaints found</strong> matching your criteria.
                    </td>`;
                    complaintsTable.querySelector('tbody').appendChild(noResultRow);
                } else {
                    noResultRow.style.display = '';
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        }

        if (liveSearchInput) liveSearchInput.addEventListener('input', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
        if (priorityFilter) priorityFilter.addEventListener('change', filterTable);
    }

    // 4. File Upload Drag & Drop & Name Preview
    const fileInput = document.getElementById('attachment');
    const dropzone = document.getElementById('dropzone');
    const preview = document.getElementById('filePreview');
    const fileNameSpan = document.getElementById('fileName');

    if (fileInput && dropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFilePreview();
            }
        });

        fileInput.addEventListener('change', updateFilePreview);

        function updateFilePreview() {
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                if (fileNameSpan && preview) {
                    fileNameSpan.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    preview.style.display = 'inline-flex';
                }
            }
        }
    }
});

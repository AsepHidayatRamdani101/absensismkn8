(function () {
    const heavyTableIds = [
        'tableStudents',
        'tableTeachers',
        'tableSchedules',
        'tableAttendanceDetails',
        'tableGuruAttendanceDetails',
        'tableMajors',
        'tableAttendances',
        'tableTeacherAttendances',
        'tableTeacherSubjects',
        'tableTeacherReport',
        'tableStudentReport',
        'tableWaliClassRecap'
    ];

    function getHeaderLabels(table) {
        const labels = [];
        const headerCells = table.querySelectorAll('thead th');

        headerCells.forEach(function (th) {
            labels.push((th.textContent || '').trim());
        });

        return labels;
    }

    function applyLabels(table) {
        const labels = getHeaderLabels(table);
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(function (row) {
            const cells = row.querySelectorAll('td');

            cells.forEach(function (cell, index) {
                const fallbackLabel = 'Kolom ' + (index + 1);
                const label = labels[index] || fallbackLabel;
                cell.setAttribute('data-label', label);
            });

            const actionCell = row.querySelector('td:last-child');
            if (actionCell) {
                actionCell.classList.add('table-mobile-actions');
            }
        });
    }

    function ensureResponsiveWrapper(table) {
        const parent = table.parentElement;
        if (!parent || parent.classList.contains('table-responsive')) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        parent.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    }

    function enhanceTable(table) {
        table.classList.add('mobile-priority');
        table.classList.add('mobile-card-table');

        ensureResponsiveWrapper(table);
        applyLabels(table);
    }

    function init() {
        heavyTableIds.forEach(function (id) {
            const table = document.getElementById(id);
            if (!table) {
                return;
            }

            enhanceTable(table);

            if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.dataTable.isDataTable(table)) {
                jQuery(table).on('draw.dt', function () {
                    applyLabels(table);
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

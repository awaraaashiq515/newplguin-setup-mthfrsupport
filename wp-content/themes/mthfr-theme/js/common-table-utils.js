jQuery(function ($) {
    // Sorting functionality
    function sortTable(tableId, columnIndex, columnName) {
        const tbody = $(`#${tableId} tbody`);
        const rows = tbody.find('tr').toArray();
        const sortIcons = $(`#${tableId} .sort-icon`);
        const currentIcon = sortIcons.eq(columnIndex);
        const isAscending = !currentIcon.hasClass('active');

        sortIcons.removeClass('active');
        if (isAscending) {
            currentIcon.addClass('active');
        }

        rows.sort(function (a, b) {
            const aValue = $(a).find('td').eq(columnIndex).text();
            const bValue = $(b).find('td').eq(columnIndex).text();

            if (columnName === 'created_at') {
                return isAscending
                    ? new Date(aValue) - new Date(bValue)
                    : new Date(bValue) - new Date(aValue);
            } else {
                return isAscending
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            }
        });

        tbody.append(rows);
    }

    // Search functionality
    function addSearchFunctionality(tableId) {
        $(`#searchInput-${tableId}`).on('input', function () {
            const searchText = $(this).val().toLowerCase();
            $(`#${tableId} tbody tr`).each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchText));
            });
        });
    }

    // Export functions to global scope
    window.tableUtils = {
        sortTable,
        addSearchFunctionality,
    };
});

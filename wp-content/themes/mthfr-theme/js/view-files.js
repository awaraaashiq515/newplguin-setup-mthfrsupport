jQuery(function ($) {
    const tableId = 'view-files';

    // Initialize DataTable with basic configurations
    function initializeDataTable() {
        $(`#${tableId}`).DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            pageLength: 10,
            order: [[2, 'desc']], // Default sort by "Created At" descending
            columnDefs: [
                { orderable: false, targets: 3 } // Disable sorting on the "Delete" column
            ],
            language: {
                emptyTable: "No files uploaded yet.",
                loadingRecords: "Loading files...",
                search: "Search files:"
            }
        });
    }

    // Fetch and display uploaded files
    function fetchUserUploads() {
        // showLoadingSpinner(); // Show loading while fetching
        $(`#${tableId} tbody`).html(`
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            
                            <div class="custom-spinner"></div>
                        </td>
                    </tr>
                `);
        $.ajax({
            url: viewData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_uploads',
                security: viewData.get_user_uploads_nonce,
            },
            success: function (response) {
                if (response.success) {
                    updateUploadedFilesTable(response.data.uploads);
                } else {
                    $(`#${tableId}`).DataTable().clear().draw();
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $(`#${tableId}`).DataTable().clear().draw();
            }
        });
    }



    // Update table with fetched uploads
    function updateUploadedFilesTable(uploads) {

        const $tableBody = $(`#${tableId} tbody`);

        $tableBody.empty();

        if (uploads.length > 0) {
            uploads.forEach(function (upload) {
                const row = `
                    <tr data-file-id="${upload.upload_id}">
                        <td>${upload.file_name}</td>
                        <td>${upload.format}</td>
                        <td>${upload.created_at}</td>
                        <td>
                            <button class="delete-file" style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px;">
                                <i class="fa fa-trash" title="Delete File"></i>
                            </button>
                        </td>
                    </tr>`;
                    
                $(`#${tableId}`).DataTable().row.add($(row)).draw();
                
            });
        }


    }

    // Delete file functionality
    // $(`#${tableId}`).on('click', '.delete-file', function () {
    //     const $row = $(this).closest('tr');
    //     const uploadId = $row.data('file-id');

    //     if (confirm('Are you sure you want to delete this file?')) {
    //         $.ajax({
    //             url: viewData.ajaxurl,
    //             type: 'POST',
    //             data: {
    //                 action: 'delete_file',
    //                 security: viewData.delete_file_nonce,
    //                 upload_id: uploadId,
    //             },
    //             success: function (response) {
    //                 if (response.success) {
    //                     $(`#${tableId}`).DataTable().row($row).remove().draw();
    //                     alert('File deleted successfully.');
    //                 } else {
    //                     alert('Failed to delete the file: ' + response.data);
    //                 }
    //             },
    //             error: function (jqXHR, textStatus, errorThrown) {
    //                 alert('AJAX Error: ' + jqXHR.status + '-' + jqXHR.responseText + ' - ' + errorThrown);
    //             }
    //         });
    //     }
    // });

    $(`#${tableId}`).on('click', '.delete-file', function () {
        const $row = $(this).closest('tr');
        const uploadId = $row.data('file-id');

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with the file deletion
                $.ajax({
                    url: viewData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_file',
                        security: viewData.delete_file_nonce,
                        upload_id: uploadId,
                    },
                    success: function (response) {
                        if (response.success) {
                            // Remove the file from DataTable and show success message
                            $(`#${tableId}`).DataTable().row($row).remove().draw();
                            Swal.fire(
                                'Deleted!',
                                'Your file has been deleted.',
                                'success'
                            );
                        } else {
                            // Show failure message if deletion failed
                            Swal.fire(
                                'Error!',
                                'Failed to delete the file: ' + response.data,
                                'error'
                            );
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        Swal.fire(
                            'AJAX Error!',
                            'An error occurred: ' + jqXHR.status + '-' + jqXHR.responseText + ' - ' + errorThrown,
                            'error'
                        );
                    }
                });
            } else {
                // Optionally, you can show a cancellation message
                Swal.fire(
                    'Cancelled',
                    'Your file is safe :)',
                    'error'
                );
            }
        });
    });
    // Initialize DataTable and fetch uploads on page load
    initializeDataTable(); // Initialize DataTable once when the page loads
    fetchUserUploads(); // Fetch uploads on page load
});

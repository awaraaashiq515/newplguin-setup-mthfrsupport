jQuery(function ($) {
    const tableId = 'result-files';

    // Initialize DataTable with basic configurations
    function initializeDataTable() {
        $(`#${tableId}`).DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            pageLength: 10,
            rowGroup: false,
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
    function fetchUserResults() {

        $(`#${tableId} tbody`).html(`
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="custom-spinner"></div>
                        </td>
                    </tr>
                `);
        $.ajax({
            url: resultData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_result',
                security: resultData.get_user_result_nonce,
            },
            success: function (response) {
                if (response.success) {
                    updateUploadedFilesTable(response.data.results);
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
    function updateUploadedFilesTable(results) {
        const $tableBody = $(`#${tableId} tbody`);


        $tableBody.empty();

        if (results.length > 0) {
            results.forEach(function (result) {
                const row = `
                    <tr data-file-id="${result.result_id}" data-upload-id="${result.upload_id}">
                        <td>${result.report_name}</td>
                        <td>${result.file_name}</td>
                        <td>${result.created_at}</td>
                        <td>
                            <button class="view-file" 
                                    data-result-id="${result.result_id}" 
                                    data-file-name="${result.report_name}" 
                                    data-folder-name="${result.file_name}"
                                    style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px;">
                                <i class="fa fa-eye" title="View Report"></i>
                            </button>
                            <button class="delete-file"
                                    style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px;">
                                <i class="fa fa-trash" title="Delete Report"></i>
                            </button>

                        </td>

                    </tr>`;

                $(`#${tableId}`).DataTable().row.add($(row)).draw();

            });

        }

    }

    $(document).ready(function () {
        // Check if the URL contains 'ster'
        if (window.location.href.indexOf('ster') !== -1) {

            // Delay the button removal (e.g., 1 second = 1000 ms)
            setTimeout(function () {
                // Check if the button exists and remove it
                if ($('.view-file').length > 0) {
                    $('.view-file').remove();  // Remove the button
                } else {
                    console.log('Button not found.');  // Debugging
                }
            }, 2000);  // Delay for 1 second (1000 ms)
        }
    });

    $(`#${tableId}`).on('click', '.view-file', function () {
    // Example of an AJAX call using the localized data
        const resultId = $(this).data('result-id');  // Get the upload_id from the button's data attribute
        const folderName = $(this).data('folder-name');  // Get the upload_id from the button's data attribute
        const fileName = $(this).data('file-name');  // Get the upload_id from the button's data attribute

        $.ajax({
            url: resultData.ajaxurl,  // Use the localized ajaxurl
            type: 'POST',
            data: {
                action: 'load_report_visualization',  // Action you defined in PHP
                result_id: resultId,  // Example data you want to send
                folder_name: folderName,
                file_name: fileName,
                security: resultData.load_report_visualization  // Include the nonce for security
            },
            success: function(response) {
                
                $('#result-container').html(response);
                
                // Handle the response (e.g., display the report visualization)
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
    });


    $(`#${tableId}`).on('click', '.delete-file', function () {
        const $row = $(this).closest('tr');
        const uploadId = $row.data('upload-id');

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
                    url: resultData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_file',
                        security: resultData.delete_file_nonce,
                        upload_id: uploadId,
                    },
                    success: function (response) {
                        if (response.success) {
                            // Remove the file from DataTable and show success message
                            
                            const dataTable = $(`#${tableId}`).DataTable();
                            dataTable.rows().every(function (rowIdx, tableLoop, rowLoop) {
                                const rowData = this.data();
                                const rowId = $(this.node()).data('upload-id'); // Get the data-id from the row node
                                if (rowId === uploadId) { // Check if the data-id matches
                                    this.remove(); // Remove the row
                                }
                            });
                            dataTable.draw(); // Redraw the DataTable
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

    
    $('#back-button').on('click', function () {
        console.log("Back button clicked!");
        console.log("Ajax URL: ", resultData.ajaxurl); // Should print the correct URL
        console.log("Nonce: ", resultData.reload_results_table); // Should print a valid nonce

        // Send AJAX request to reload the table
        $.ajax({
            url: resultData.ajaxurl, // WordPress provides ajaxurl by default
            type: 'POST',
            data: { 
                action: 'reload_results_table', // The action that hooks into your function
                security: resultData.reload_results_table,
                
            },
            success: function(response) {
                // Replace the content of the #results-container with the new table
                $('#results-container').html(response);
            },
            error: function (error) {
                console.error('Error:', error);
            }
        });
    });




    // Initialize DataTable and fetch uploads on page load
    initializeDataTable(); // Initialize DataTable once when the page loads
    fetchUserResults(); // Fetch uploads on page load
});

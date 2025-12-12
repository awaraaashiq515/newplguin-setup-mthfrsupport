jQuery(function ($) {
    const tableId = 'uploaded-files';

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
                { targets: 0, width: '40%' },
                { orderable: false, targets: 3 } // Disable sorting on the "Delete" column
            ],
            select: { items: 'row', style: 'single' },
            language: {
                emptyTable: "No files uploaded yet.",
                loadingRecords: "Loading files...",
                search: "Search files:"
            }
        });

        $(`#${tableId}`).DataTable().on('click', 'tbody tr', (e) => {
            let classList = e.currentTarget.classList;
            let row = $(e.currentTarget); // Store the clicked row

            if (classList.contains('selected')) {
                
                classList.remove('selected');
                row.find('.generate-file').hide();
                $('#generate-report').prop('disabled', true);
                $('#generate-report').data('upload-id', '');  // Store the selected upload ID
            }
            else {

                $(`#${tableId}`).DataTable().rows('.selected').nodes().each((row) => {
                    row.classList.remove('selected');
                    $(row).find('.generate-file').hide();
                });
                let uploadId = $(e.currentTarget).data('file-id');

                
                $('#generate-report').prop('disabled', false);
                $('#generate-report').data('upload-id', uploadId);  // Store the selected upload ID

                // Show the generate-file button of the selected row
                row.find('.generate-file').show();


                classList.add('selected');
                
            }
        });

    }

    // Fetch and display uploaded files
    function fetchUserUploads() {
        $(`#${tableId} tbody`).html(`
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="custom-spinner"></div>
                        </td>
                    </tr>
                `);

        $.ajax({
            url: uploadData.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_uploads',
                security: uploadData.get_user_uploads_nonce,
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
                            <button class="generate-file" style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px; display: none;">
                                <i class="fa fa-file" title="Generate Report"></i>
                            </button>
                        </td>

                    </tr>`;
                $(`#${tableId}`).DataTable().row.add($(row)).draw();

            });

            
        } else {
            const noDataRow = `
            <tr>
                <td colspan="4" class="text-center py-4">
                    <span class="sr-only">No data available</span>
                </td>
            </tr>`;
            $(`#${tableId} tbody`).html(noDataRow);
        }


    }

    $('body').on('change', '#file', function() {
        var file_data = $(this).prop('files')[0];
        var form_data = new FormData();
        form_data.append('file', file_data);
        form_data.append('action', 'file_upload');
        form_data.append('security', uploadData.security);

        $.ajax({
            url: uploadData.ajaxurl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: form_data,
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function(event) {
                        if (event.lengthComputable) {
                            var percent = Math.ceil((event.loaded / event.total) * 100);
                            $('.progress-bar').css('width', percent + '%');
                            $('.progress-text').text(percent + '%');
                        }
                    });
                }
                return xhr;
            },
            beforeSend: function() {
                $('#progress-container').show();
            },
            success: function (response) {

                
                // Hide success message after 5 seconds

                if (response.success) {
                    var fileType = response.data.source_type;
                    var folderName = response.data.folder_name || 'Unknown';
                    var createdAt = response.data.created_at;
                    var uploadId = response.data.upload_id; // Get the upload ID from response
                    $('#file').val('');  // This clears the file input
                    
                    // Append details to the table
                    var row = `
                        <tr data-file-id="${uploadId}">
                            <td>${folderName}</td>
                            <td>${fileType}</td>
                            <td>${createdAt}</td>
                            <td>
                                <button class="delete-file" style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px;">
                                    <i class="fa fa-trash" title="Delete File"></i>
                                </button>
                                
                                <button class="generate-file" style="color: black; background: transparent; cursor: pointer; font-size: 24px; padding: 8px 12px; border-radius: 4px; display: none;">
                                    <i class="fa fa-file" title="Generate Report"></i>
                                </button>
                            </td>
                        </tr>`;

                    $(`#${tableId}`).DataTable().row.add($(row)).draw();

                    $('#upload-success').html('  <i class="fa fa-times-circle"></i> File uploaded successfully!').show();
                    $('#upload-fail').hide(); // Hide error message if exists

                                        // Toggle button state
                    $('#progress-container').hide();
                    setTimeout(function () {
                        $('#upload-success').fadeOut();
                    }, 3000); // 5 seconds timeout for success message

                } else {

                    $('#upload-fail').html('<i class="fa fa-times-circle"></i> ' + response.data.message).show();;
                    $('#upload-success').hide(); // Hide success message if exists
                    $('#file').val('');  // This clears the file input
                    $('#progress-container').hide();

                    // Hide error message after 5 seconds
                    setTimeout(function () {
                        $('#upload-fail').fadeOut();
                    }, 3000); // 5 seconds timeout for error message
                    
                    
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                
                $('#progress-container').hide();
            }
        });
    });



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
                    url: uploadData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_file',
                        security: uploadData.delete_file_nonce,
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

    
    

    // Generate report logic (redirect to select-report page)
    $(document).on('click', '.generate-file', function (event) {
        // Prevent the event from bubbling up to the row selection handler
        event.stopPropagation();

        let uploadId = $(this).closest('tr').data('file-id');

        if (uploadId) {
            // Redirect to the next page and pass the upload_id in the URL
            window.location.href = '/select-report?upload_id=' + uploadId;
        } else {
            alert('No file selected.');
        }
    });

    $('#generate-report').on('click', function() {
        // Get the upload_id from session storage
        var uploadId = $(this).data('upload-id');

        if (uploadId) {
            // Redirect to the next page and pass the upload_id in the URL
            window.location.href = '/select-report?upload_id=' + uploadId;
        } else {
            alert('No file selected.');
        }
    });


    
    // Fetch uploads on page load

    initializeDataTable(); // Reinitialize DataTable after updating rows
    fetchUserUploads();
});

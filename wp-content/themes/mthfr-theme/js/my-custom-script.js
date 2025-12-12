// jQuery(function ($) {

//     $('body').on('change', '#file', function() {
        
//         var $this = $(this);
//         var file_data = $(this).prop('files')[0];
//         var form_data = new FormData();
//         form_data.append('file', file_data);
//         form_data.append('action', 'file_upload');
//         form_data.append('security', blog.security);

//         $.ajax({
//             url: blog.ajaxurl,
//             type: 'POST',
//             contentType: false,
//             processData: false,
//             data: form_data,
//             xhr: function() {
//                 var xhr = $.ajaxSettings.xhr();
//                 if (xhr.upload) {
//                     xhr.upload.addEventListener('progress', function(event) {
//                         if (event.lengthComputable) {
//                             var percent = Math.ceil((event.loaded / event.total) * 100);
//                             $('.progress-bar').css('width', percent + '%');
//                             $('.progress-text').text(percent + '%');
//                         }
//                     });
//                 }
//                 return xhr;
//             },
//             beforeSend: function() {
//                 $('#progress-container').show();
//             },
//             success: function (response) {

//                 $('#success-message').html('File uploaded successfully!').show();
//                 $('#error-message').hide(); // Hide error message if exists

//                 // Hide success message after 5 seconds
//                 setTimeout(function() {
//                     $('#success-message').fadeOut();
//                 }, 5000); // 5 seconds timeout for success message

//                 if (response.success) {
//                     var fileType = file_data.type;
//                     var folderName = response.data.folder || 'Unknown';
//                     var fileUrl = response.data.url;
//                     var createdAt = response.data.created_at;
//                     var uploadId = response.data.upload_id; // Get the upload ID from response
//                     $('#file').val('');  // This clears the file input

//                     // Append details to the table
//                     var row = `
//                         <tr data-file-id="${uploadId}">
//                             <td>${folderName}</td>
//                             <td>${fileType}</td>
//                             <td>${createdAt}</td>
//                             <td>
//                                 <button class="delete-file" style="color: red; cursor: pointer; background:white;">🗑 Delete</button>
//                             </td>
//                         </tr>`;
//                     $('#uploaded-files tbody').append(row);
//                     $('#uploaded-files').show();

//                     // Reset progress
//                     // setTimeout(() => {
//                     //     $('.progress-bar').css('width', '0%');
//                     //     $('.progress-text').text('0%');
//                     //     $('#progress-container').hide();
//                     // }, 1000);
//                                         // Toggle button state
//                     $('#progress-container').hide();
//                 } else {
//                     $('#error-message').html(response.data.message).show();
//                     $('#success-message').hide(); // Hide success message if exists
//                     $('#file').val('');  // This clears the file input

//                     // Hide error message after 5 seconds
//                     setTimeout(function() {
//                         $('#error-message').fadeOut();
//                     }, 5000); // 5 seconds timeout for error message
                    
//                     $('#progress-container').hide();
                    
//                 }
//             },
//             error: function (jqXHR, textStatus, errorThrown) {
                
//                 $('#progress-container').hide();
//                 alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
//             }
//         });
//     });

//     // // Handle file delete
//     $('body').on('click', '.delete-file', function() {
//         var $row = $(this).closest('tr');
//         var upload_id = $row.data('file-id');

//         if (confirm('Are you sure you want to delete this file?')) {
//             $.ajax({
//                 url: blog.ajaxurl,
//                 type: 'POST',
//                 data: {
//                     action: 'delete_file',
//                     security: blog.delete_file_nonce,
//                     upload_id: upload_id,
//                 },
//                 success: function(response) {
//                     if (response.success) {
//                         $row.remove();
//                         $('#file').val('');  // This clears the file input

//                         if ($('#uploaded-files tbody tr').length === 0) {
//                             $('#uploaded-files').hide();
//                         }
//                         $('#generate-report').prop('disabled', true);
//                         alert('File deleted successfully.');
                        
//                     } else {
//                         alert('Failed to delete the file: ' + response.data);
//                     }
//                 },
//                 error: function(jqXHR, textStatus, errorThrown) {
//                     alert('AJAX Error: ' + jqXHR.status + '-' +  jqXHR.responseText + ' - ' + errorThrown);
//                 }
//             });

//         }
//     });
// });


// jQuery(function($) {
//     // Enable "Generate Report" button when a row is clicked
//     $('#uploaded-files tbody').on('click', 'tr', function() {
//         // Get the upload_id from the clicked row
//         var uploadId = $(this).data('file-id');

//         // Store the upload_id in session storage
//         sessionStorage.setItem('upload_id', uploadId);
//         $('#uploaded-files tbody tr').removeClass('selected-row');
//         $(this).addClass('selected-row');


//         // Enable the Generate Report button
//         $('#generate-report').prop('disabled', false);
//     });

//     // Handle the "Generate Report" button click
//     $('#generate-report').on('click', function() {
//         // Get the upload_id from session storage
//         var uploadId = sessionStorage.getItem('upload_id');

//         if (uploadId) {
//             // Redirect to the next page and pass the upload_id in the URL
//             window.location.href = '/generate-report-page/?upload_id=' + uploadId;
//         } else {
//             alert('No file selected.');
//         }
//     });
// });


// jQuery(function ($) {
//     // Fetch all uploads for the logged-in user
//     function fetchUserUploads() {
//         $.ajax({
//             url: blog.ajaxurl,
//             type: 'POST',
//             data: {
//                 action: 'get_user_uploads',
//                 security: blog.get_user_uploads_nonce,
//             },
//             success: function (response) {
//                 if (response.success) {
//                     var uploads = response.data.uploads;
//                     var $tableBody = $('#uploaded-files tbody');
//                     $tableBody.empty(); // Clear existing rows

//                     if (uploads.length > 0) {
//                         uploads.forEach(function (upload) {
//                             var row = `
//                                 <tr data-file-id="${upload.upload_id}">
//                                     <td>${upload.file_name}</td>
//                                     <td>${upload.file_type}</td>
//                                     <td>${upload.created_at}</td>
//                                     <td>
//                                         <button class="delete-file" style="color: red; cursor: pointer; background:white;">🗑 Delete</button>
//                                     </td>
//                                 </tr>`;
//                             $tableBody.append(row);
//                         });
//                         $('#uploaded-files').show();
//                     } else {
//                         $('#uploaded-files').hide();
//                     }
//                 } else {
//                     alert('Failed to fetch uploads: ' + response.data.message);
//                 }
//             },
//             error: function (jqXHR, textStatus, errorThrown) {
//                 alert('AJAX Error: ' + jqXHR.textStatus + ' - ' + errorThrown);
//             }
//         });
//     }

//     // Fetch uploads on page load
//     fetchUserUploads();


//     // Sorting functionality
//     function sortTable(columnIndex, columnName) {
//         const tbody = $('#uploaded-files tbody');
//         const rows = tbody.find('tr').toArray();
//         const sortIcons = $('.sort-icon');
//         const currentIcon = sortIcons.eq(columnIndex);
//         const isAscending = !currentIcon.hasClass('active');

//         sortIcons.removeClass('active');
//         if (isAscending) {
//             currentIcon.addClass('active');
//         }

//         rows.sort(function(a, b) {
//             const aValue = $(a).find('td').eq(columnIndex).text();
//             const bValue = $(b).find('td').eq(columnIndex).text();

//             if (columnName === 'created_at') {
//                 return isAscending ? 
//                     new Date(aValue) - new Date(bValue) :
//                     new Date(bValue) - new Date(aValue);
//             } else {
//                 return isAscending ?
//                     aValue.localeCompare(bValue) :
//                     bValue.localeCompare(aValue);
//             }
//         });

//         tbody.append(rows);
//     }

//     // Add click handlers for sorting
//     $('#uploaded-files th').click(function() {
//         const columnIndex = $(this).index();
//         const columnName = $(this).data('sort');
//         sortTable(columnIndex, columnName);
//     });

//     // Search functionality
//     $('#searchInput').on('input', function() {
//         const searchText = $(this).val().toLowerCase();
//         $('#uploaded-files tbody tr').each(function() {
//             const text = $(this).text().toLowerCase();
//             $(this).toggle(text.includes(searchText));
//         });
//     });

//     // Existing file upload and delete handlers...
// });


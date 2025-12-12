jQuery(function ($) {
    function downloadResults() {
        var $button = $(this);
        $button.prop('disabled', true).text('Downloading...');
        const resultId = $button.data('result-id');

        console.log(resultId);

        $.ajax({
            url: downloadResult.ajaxurl,
            type: 'POST',
            data: {
                action: 'download_pdf',
                security: downloadResult.download_pdf,
                result_id: resultId
            },
            success: function (response) {
                if (response.success && response.data.url) {
                    const fileUrl = response.data.url;
                    const filename = fileUrl.split('/').pop(); // Extract filename from URL

                    // Fetch the file as blob to ensure forced download
                    fetch(fileUrl)
                        .then(res => res.blob())
                        .then(blob => {
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        })
                        .catch(() => {
                            alert('Failed to fetch the file for download.');
                        });
                } else {
                    alert('Error: ' + (response.data?.message || 'No file URL provided.'));
                }
            },
            error: function () {
                alert('An error occurred while downloading.');
            },
            complete: function () {
                $button.prop('disabled', false).text('Download Report');
            }
        });
    }

    $(document).on('click', '#download-btn', downloadResults);
});

jQuery(document).ready(function($) {
    // This script handles the media uploader functionality for the plugin's admin page.

    // When an "Upload Image" button is clicked...
    $('body').on('click', '.ci-upload-btn', function(e) {
        e.preventDefault();

        const button = $(this);
        const inputField = button.prev('.ci-image-url-field');

        // Create a new media frame
        const frame = wp.media({
            title: 'Select or Upload an Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        // When an image is selected in the media frame...
        frame.on('select', function() {
            // Get the attachment details
            const attachment = frame.state().get('selection').first().toJSON();
            // Set the image URL in the input field
            inputField.val(attachment.url);
        });

        // Open the media frame
        frame.open();
    });
});
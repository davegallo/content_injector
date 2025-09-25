document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('ci-popup-overlay');
    const closeBtn = document.getElementById('ci-popup-close');
    let hasBeenShown = sessionStorage.getItem('ci_popup_shown');

    function showPopup() {
        if (!hasBeenShown) {
            overlay.style.display = 'flex';
            sessionStorage.setItem('ci_popup_shown', 'true');
            hasBeenShown = true;
            // Disable body scroll when popup is active
            document.body.style.overflow = 'hidden';
        }
    }

    function hidePopup() {
        overlay.style.display = 'none';
        // Re-enable body scroll
        document.body.style.overflow = '';
    }

    document.addEventListener('mouseout', function(e) {
        if (!e.toElement && !e.relatedTarget && e.clientY < 10) {
            showPopup();
        }
    }, {once: true}); // Trigger only once

    closeBtn.addEventListener('click', hidePopup);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            hidePopup();
        }
    });
});
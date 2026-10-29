/**
 * Campus Resource Sharing Platform - Client Interactions JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Confirm dialogs for destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // 2. Image upload preview handler
    const imageInput = document.querySelector('input[type="file"][data-preview]');
    if (imageInput) {
        const previewTargetId = imageInput.getAttribute('data-preview');
        const previewElement = document.getElementById(previewTargetId);

        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file && previewElement) {
                // Check size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Selected file exceeds the 5MB size limit. Please choose a smaller image.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewElement.src = e.target.result;
                    previewElement.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Auto-scroll chat box to bottom
    const chatBox = document.querySelector('.chat-box');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // 4. Form validation styling feedback
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 5. Tooltips initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

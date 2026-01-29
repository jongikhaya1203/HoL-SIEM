/**
 * Admin Portal JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        if (input.accept && input.accept.includes('image')) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let preview = document.getElementById('preview-' + input.id);
                        if (!preview) {
                            preview = document.createElement('img');
                            preview.id = 'preview-' + input.id;
                            preview.style.maxWidth = '200px';
                            preview.style.maxHeight = '100px';
                            preview.style.marginTop = '10px';
                            preview.style.border = '2px solid #e0e0e0';
                            preview.style.borderRadius = '5px';
                            input.parentNode.appendChild(preview);
                        }
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // Task completion animation
    const taskCheckboxes = document.querySelectorAll('.task-item input[type="checkbox"]');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskItem = this.closest('.task-item');
            if (this.checked) {
                taskItem.style.opacity = '0.6';
                taskItem.style.textDecoration = 'line-through';
            } else {
                taskItem.style.opacity = '1';
                taskItem.style.textDecoration = 'none';
            }
        });
    });
});

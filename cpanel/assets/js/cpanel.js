/**
 * IOC Control Panel - Main JavaScript
 * Version 1.0
 */

(function() {
    'use strict';

    // =====================================================
    // Utility Functions
    // =====================================================

    /**
     * Debounce function for rate-limiting events
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * Format bytes to human readable
     */
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Format date to relative time
     */
    function timeAgo(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 }
        ];
        for (const interval of intervals) {
            const count = Math.floor(seconds / interval.seconds);
            if (count >= 1) {
                return `${count} ${interval.label}${count > 1 ? 's' : ''} ago`;
            }
        }
        return 'Just now';
    }

    // =====================================================
    // Modal Management
    // =====================================================

    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        if (modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        } else {
            // Close all modals
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
        document.body.style.overflow = '';
    };

    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // =====================================================
    // Notifications
    // =====================================================

    window.showNotification = function(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container') || createNotificationContainer();

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(notification);

        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    };

    function createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 10px;';
        document.body.appendChild(container);
        return container;
    }

    // =====================================================
    // Form Handling
    // =====================================================

    // Auto-save form data to localStorage
    function enableFormAutoSave(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const storageKey = `cpanel_form_${formId}`;

        // Load saved data
        const savedData = localStorage.getItem(storageKey);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const input = form.elements[key];
                    if (input && input.type !== 'password') {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.error('Error loading saved form data:', e);
            }
        }

        // Save on change
        form.addEventListener('change', debounce(function() {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                if (!key.includes('password')) {
                    data[key] = value;
                }
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        }, 500));

        // Clear on submit
        form.addEventListener('submit', function() {
            localStorage.removeItem(storageKey);
        });
    }

    // Form validation helper
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');

                // Remove error class on input
                input.addEventListener('input', function() {
                    this.classList.remove('error');
                }, { once: true });
            }
        });

        return isValid;
    };

    // =====================================================
    // Toggle Switches
    // =====================================================

    // Handle toggle switch changes via AJAX
    document.addEventListener('change', function(e) {
        if (e.target.matches('.toggle-switch input[data-ajax]')) {
            const toggle = e.target;
            const url = toggle.dataset.ajax;
            const id = toggle.dataset.id;
            const field = toggle.dataset.field || 'is_enabled';
            const value = toggle.checked ? 1 : 0;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&${field}=${value}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    toggle.checked = !toggle.checked;
                    showNotification(data.message || 'Update failed', 'error');
                }
            })
            .catch(error => {
                toggle.checked = !toggle.checked;
                showNotification('Connection error', 'error');
            });
        }
    });

    // =====================================================
    // Search Functionality
    // =====================================================

    const searchInput = document.querySelector('.cpanel-search input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value.toLowerCase();

            // Search in tables
            document.querySelectorAll('.config-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Search in cards
            document.querySelectorAll('.module-card, .protocol-card, .channel-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        }, 300));
    }

    // =====================================================
    // Sidebar Navigation
    // =====================================================

    // Mobile sidebar toggle
    window.toggleSidebar = function() {
        const sidebar = document.querySelector('.cpanel-sidebar');
        const main = document.querySelector('.cpanel-main');

        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            if (main) {
                main.classList.toggle('expanded');
            }
        }
    };

    // Collapse sidebar sections
    document.querySelectorAll('.sidebar-section h3').forEach(header => {
        header.addEventListener('click', function() {
            const section = this.parentElement;
            section.classList.toggle('collapsed');
        });
    });

    // =====================================================
    // Data Tables
    // =====================================================

    // Sortable table headers
    document.querySelectorAll('.config-table th[data-sortable]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const index = Array.from(this.parentElement.children).indexOf(this);
            const isAsc = this.classList.contains('sort-asc');

            // Remove sort classes from all headers
            this.parentElement.querySelectorAll('th').forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });

            // Add sort class to current header
            this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

            // Sort rows
            rows.sort((a, b) => {
                const aVal = a.children[index]?.textContent.trim() || '';
                const bVal = b.children[index]?.textContent.trim() || '';

                // Try numeric sort first
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? bNum - aNum : aNum - bNum;
                }

                // Fall back to string sort
                return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // =====================================================
    // Charts (if needed)
    // =====================================================

    window.createSimpleChart = function(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;
        const chartWidth = width - padding * 2;
        const chartHeight = height - padding * 2;

        // Clear canvas
        ctx.clearRect(0, 0, width, height);

        // Draw bars
        if (data.type === 'bar') {
            const barWidth = chartWidth / data.values.length * 0.8;
            const maxValue = Math.max(...data.values);

            data.values.forEach((value, index) => {
                const barHeight = (value / maxValue) * chartHeight;
                const x = padding + (index * (chartWidth / data.values.length)) + (chartWidth / data.values.length * 0.1);
                const y = height - padding - barHeight;

                ctx.fillStyle = data.colors?.[index] || '#6366f1';
                ctx.fillRect(x, y, barWidth, barHeight);

                // Labels
                ctx.fillStyle = '#64748b';
                ctx.font = '12px system-ui';
                ctx.textAlign = 'center';
                ctx.fillText(data.labels?.[index] || '', x + barWidth / 2, height - 10);
            });
        }
    };

    // =====================================================
    // Session Management
    // =====================================================

    // Session timeout warning
    let sessionTimeout;
    const SESSION_WARNING_TIME = 5 * 60 * 1000; // 5 minutes before timeout

    function resetSessionTimer() {
        clearTimeout(sessionTimeout);
        sessionTimeout = setTimeout(() => {
            showNotification('Your session will expire soon. Please save your work.', 'warning', 0);
        }, SESSION_WARNING_TIME);
    }

    // Reset timer on user activity
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
        document.addEventListener(event, debounce(resetSessionTimer, 1000), { passive: true });
    });

    resetSessionTimer();

    // =====================================================
    // Keyboard Shortcuts
    // =====================================================

    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S = Save (if form is focused)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                e.preventDefault();
                activeForm.submit();
            }
        }

        // Ctrl/Cmd + K = Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const search = document.querySelector('.cpanel-search input');
            if (search) search.focus();
        }
    });

    // =====================================================
    // Initialize
    // =====================================================

    document.addEventListener('DOMContentLoaded', function() {
        // Add loaded class for CSS transitions
        document.body.classList.add('loaded');

        // Initialize tooltips
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.dataset.tooltip;
                document.body.appendChild(tooltip);

                const rect = this.getBoundingClientRect();
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
                tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
            });

            el.addEventListener('mouseleave', function() {
                document.querySelectorAll('.tooltip').forEach(t => t.remove());
            });
        });

        console.log('IOC Control Panel initialized');
    });

})();

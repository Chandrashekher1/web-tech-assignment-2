/**
 * University Result Management System
 * Main JavaScript — Interactivity & Animations
 */

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initFileUpload();
    initScrollReveal();
    initMarksAnimations();
    initFormValidation();
});

/* ========== Mobile Sidebar Toggle ========== */
function initMobileMenu() {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!menuBtn || !sidebar) return;

    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
    });

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

/* ========== File Upload Drag & Drop ========== */
function initFileUpload() {
    const zone = document.querySelector('.file-upload-zone');
    if (!zone) return;

    const fileInput = zone.querySelector('input[type="file"]');
    const uploadText = zone.querySelector('.upload-text');

    ['dragenter', 'dragover'].forEach(event => {
        zone.addEventListener(event, (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        zone.addEventListener(event, (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
        });
    });

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                const fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2);

                // Validate PDF
                if (!fileName.toLowerCase().endsWith('.pdf')) {
                    showToast('Only PDF files are allowed', 'error');
                    fileInput.value = '';
                    return;
                }

                if (uploadText) {
                    uploadText.innerHTML = `<strong>📄 ${fileName}</strong> (${fileSize} MB)`;
                }
                zone.style.borderColor = 'var(--accent-500)';
            }
        });
    }
}

/* ========== Scroll Reveal Animation ========== */
function initScrollReveal() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    elements.forEach(el => observer.observe(el));
}

/* ========== Marks Bar Animations ========== */
function initMarksAnimations() {
    const bars = document.querySelectorAll('.marks-fill');
    if (!bars.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const width = entry.target.dataset.width;
                if (width) {
                    setTimeout(() => {
                        entry.target.style.width = width + '%';
                    }, 200);
                }
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    bars.forEach(bar => {
        bar.style.width = '0%';
        observer.observe(bar);
    });
}

/* ========== Form Validation ========== */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const inputs = form.querySelectorAll('[required]');
            let valid = true;

            inputs.forEach(input => {
                clearError(input);

                if (!input.value.trim()) {
                    showError(input, 'This field is required');
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
            } else {
                // Show loading state on submit button
                const btn = form.querySelector('.btn-primary');
                if (btn) {
                    btn.classList.add('loading');
                    btn.disabled = true;
                }
            }
        });
    });
}

function showError(input, message) {
    input.style.borderColor = 'var(--error-500)';
    const errorEl = document.createElement('span');
    errorEl.className = 'form-error';
    errorEl.style.cssText = 'color: var(--error-400); font-size: 0.75rem; margin-top: 4px; display: block;';
    errorEl.textContent = message;
    input.parentElement.appendChild(errorEl);
}

function clearError(input) {
    input.style.borderColor = '';
    const existing = input.parentElement.querySelector('.form-error');
    if (existing) existing.remove();
}

/* ========== Toast Notifications ========== */
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${type === 'success' ? '✓' : '✕'}</span>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/* ========== Semester Tab Switching ========== */
function switchSemester(semesterId) {
    // Update active tab
    document.querySelectorAll('.semester-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');

    // Submit form or redirect
    const form = document.getElementById('semester-form');
    if (form) {
        const input = form.querySelector('input[name="semester_id"]');
        if (input) {
            input.value = semesterId;
            form.submit();
        }
    } else {
        window.location.href = '?semester=' + semesterId;
    }
}

/* ========== Confirm Action ========== */
function confirmAction(message) {
    return confirm(message);
}

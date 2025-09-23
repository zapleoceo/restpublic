/**
 * North Republic Admin Panel - Modern JavaScript
 * Complete UI refactoring with modern interactions
 */

class AdminPanel {
    constructor() {
        this.sidebarOpen = false;
        this.userMenuOpen = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAccessibility();
        this.initTheme();
        this.initNotifications();
    }

    bindEvents() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // User menu toggle
        const userMenuToggle = document.getElementById('user-menu-toggle');
        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', (e) => this.toggleUserMenu(e));
        }

        // Sidebar overlay
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => this.closeSidebar());
        }

        // Close user menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.userMenuOpen && !e.target.closest('.user-dropdown')) {
                this.closeUserMenu();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllMenus();
            }
        });

        // Window resize
        window.addEventListener('resize', () => this.handleResize());

        // Form enhancements
        this.enhanceForms();

        // Table enhancements
        this.enhanceTables();
    }

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebar && overlay) {
            sidebar.classList.toggle('open', this.sidebarOpen);
            overlay.classList.toggle('active', this.sidebarOpen);

            // Update ARIA attributes
            sidebar.setAttribute('aria-hidden', !this.sidebarOpen);
            document.body.style.overflow = this.sidebarOpen ? 'hidden' : '';
        }
    }

    closeSidebar() {
        this.sidebarOpen = false;
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebar && overlay) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            sidebar.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    toggleUserMenu(e) {
        e.stopPropagation();
        this.userMenuOpen = !this.userMenuOpen;
        const userMenu = document.getElementById('user-menu');
        const userMenuToggle = document.getElementById('user-menu-toggle');

        if (userMenu && userMenuToggle) {
            userMenu.setAttribute('aria-hidden', !this.userMenuOpen);
            userMenuToggle.setAttribute('aria-expanded', this.userMenuOpen);
        }
    }

    closeUserMenu() {
        this.userMenuOpen = false;
        const userMenu = document.getElementById('user-menu');
        const userMenuToggle = document.getElementById('user-menu-toggle');

        if (userMenu && userMenuToggle) {
            userMenu.setAttribute('aria-hidden', 'true');
            userMenuToggle.setAttribute('aria-expanded', 'false');
        }
    }

    closeAllMenus() {
        this.closeSidebar();
        this.closeUserMenu();
    }

    handleResize() {
        // Close mobile sidebar on desktop
        if (window.innerWidth > 768 && this.sidebarOpen) {
            this.closeSidebar();
        }
    }

    setupAccessibility() {
        // Add ARIA attributes
        const sidebar = document.getElementById('admin-sidebar');
        if (sidebar) {
            sidebar.setAttribute('aria-hidden', 'false');
        }

        // Focus management
        const focusableElements = 'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])';
        this.focusableElements = document.querySelectorAll(focusableElements);
    }

    initTheme() {
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('admin-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                if (!localStorage.getItem('admin-theme')) {
                    document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    initNotifications() {
        // Auto-hide notifications after 5 seconds
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                this.hideNotification(notification);
            }, 5000);
        });
    }

    enhanceForms() {
        // Add loading states to forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    this.setButtonLoading(submitButton, true);
                    setTimeout(() => {
                        this.setButtonLoading(submitButton, false);
                    }, 3000);
                }
            });
        });

        // Form validation
        const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }

    enhanceTables() {
        // Add sorting to tables
        const sortableHeaders = document.querySelectorAll('th[data-sort]');
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => this.sortTable(header));
        });

        // Add search functionality
        const searchInputs = document.querySelectorAll('.table-search');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => this.searchTable(e.target));
        });
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'Загрузка...';
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText;
            button.classList.remove('loading');
        }
    }

    validateField(field) {
        const isValid = field.checkValidity();
        field.classList.toggle('error', !isValid);

        // Remove existing error message
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add error message if invalid
        if (!isValid) {
            const errorMsg = document.createElement('div');
            errorMsg.className = 'field-error';
            errorMsg.textContent = field.validationMessage;
            field.parentNode.appendChild(errorMsg);
        }

        return isValid;
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorMsg = field.parentNode.querySelector('.field-error');
        if (errorMsg) {
            errorMsg.remove();
        }
    }

    sortTable(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const column = header.dataset.sort;
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Determine sort direction
        const isAscending = header.classList.contains('sort-asc');

        // Update header classes
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');

        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
            const bValue = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';

            const comparison = aValue.localeCompare(bValue, undefined, { numeric: true });
            return isAscending ? -comparison : comparison;
        });

        // Rebuild table
        rows.forEach(row => tbody.appendChild(row));
    }

    searchTable(searchInput) {
        const table = searchInput.closest('.card')?.querySelector('.table');
        if (!table) return;

        const searchTerm = searchInput.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }

    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="adminPanel.hideNotification(this.parentNode.parentNode)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;

        const container = document.querySelector('.admin-main');
        if (container) {
            container.insertBefore(notification, container.firstChild);

            // Auto-hide
            setTimeout(() => {
                this.hideNotification(notification);
            }, duration);
        }
    }

    hideNotification(notification) {
        notification.classList.add('hiding');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    formatCurrency(amount, currency = 'VND') {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
}

// Initialize admin panel when DOM is loaded
let adminPanel;
document.addEventListener('DOMContentLoaded', function() {
    adminPanel = new AdminPanel();

    // Make admin panel globally available
    window.adminPanel = adminPanel;
});

function addEventListeners() {
    // Обработчик для мобильного меню
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        console.log('Кнопка мобильного меню найдена, добавляем обработчики');
        
        // Обработчик для клика
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Клик по кнопке меню');
            toggleMobileMenu();
        });
        
        // Обработчик для touch
        mobileMenuBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Touch по кнопке меню');
            toggleMobileMenu();
        });
        
        // Обработчик для touchend
        mobileMenuBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    } else {
        console.error('Кнопка мобильного меню не найдена');
    }
    
    // Обработчик для оверлея
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
        overlay.addEventListener('touchstart', closeMobileMenu);
    }
    
    // Закрытие меню при клике на пункт меню
    const menuItems = document.querySelectorAll('.menu-item a');
    menuItems.forEach(item => {
        item.addEventListener('click', closeMobileMenu);
    });
    
    // Обработчики для форм
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
    
    // Обработчики для кнопок удаления
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', handleDelete);
    });
    
    // Обработчики для модальных окон
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', openModal);
    });
    
    const modalCloses = document.querySelectorAll('.modal-close');
    modalCloses.forEach(close => {
        close.addEventListener('click', closeModal);
    });
}

function initComponents() {
    // Инициализация таблиц с сортировкой
    initSortableTables();
    
    // Инициализация поиска
    initSearch();
    
    // Инициализация фильтров
    initFilters();
}

function toggleMobileMenu() {
    console.log('toggleMobileMenu вызвана');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        console.log('Меню переключено, классы:', sidebar.classList.toString());
    } else {
        console.error('Элементы меню не найдены');
    }
}

function closeMobileMenu() {
    console.log('closeMobileMenu вызвана');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        console.log('Меню закрыто');
    }
}

function handleFormSubmit(event) {
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Сохранение...';
        
        // Восстанавливаем кнопку через 3 секунды
        setTimeout(() => {
            submitButton.disabled = false;
            submitButton.textContent = 'Сохранить';
        }, 3000);
    }
}

function handleDelete(event) {
    event.preventDefault();
    
    const button = event.target;
    const itemName = button.dataset.itemName || 'элемент';
    
    if (confirm(`Вы уверены, что хотите удалить ${itemName}?`)) {
        // Показываем индикатор загрузки
        button.disabled = true;
        button.textContent = 'Удаление...';
        
        // Отправляем запрос на удаление
        const form = button.closest('form');
        if (form) {
            form.submit();
        }
    }
}

function openModal(event) {
    event.preventDefault();
    const modalId = event.target.dataset.modal;
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(event) {
    const modal = event.target.closest('.modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function initSortableTables() {
    const tables = document.querySelectorAll('.table-sortable');
    
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(table, header));
        });
    });
}

function sortTable(table, header) {
    const column = header.dataset.sort;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Определяем направление сортировки
    const isAscending = header.classList.contains('sort-asc');
    
    // Убираем классы сортировки со всех заголовков
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Добавляем класс к текущему заголовку
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    // Сортируем строки
    rows.sort((a, b) => {
        const aValue = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
        const bValue = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';
        
        if (isAscending) {
            return bValue.localeCompare(aValue);
        } else {
            return aValue.localeCompare(bValue);
        }
    });
    
    // Перестраиваем таблицу
    rows.forEach(row => tbody.appendChild(row));
}

function initSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(handleSearch, 300));
    });
}

function handleSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    const table = event.target.closest('.card').querySelector('.table');
    
    if (table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }
}

function initFilters() {
    const filterSelects = document.querySelectorAll('.filter-select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', handleFilter);
    });
}

function handleFilter(event) {
    const filterValue = event.target.value;
    const table = event.target.closest('.card').querySelector('.table');
    
    if (table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const filterColumn = row.querySelector(`[data-filter="${event.target.dataset.filter}"]`);
            const cellValue = filterColumn?.textContent || '';
            
            if (filterValue === '' || cellValue === filterValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
}

// Утилиты
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

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const container = document.querySelector('.admin-main');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        // Убираем уведомление через 5 секунд
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatCurrency(amount, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// Экспорт функций для использования в других скриптах
window.AdminPanel = {
    showAlert,
    formatDate,
    formatCurrency,
    openModal,
    closeModal
};

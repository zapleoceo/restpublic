// Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация
    initAdminPanel();
});

function initAdminPanel() {
    // Добавляем обработчики событий
    addEventListeners();
    
    // Инициализируем компоненты
    initComponents();
}

function addEventListeners() {
    // Обработчик для мобильного меню
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }
    
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
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('open');
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

<?php
/**
 * Storage Management Page
 * Управление остатками складов
 */

// Подключение общих файлов
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/layout.php';

// Определяем переменные для layout
$page_title = 'Управление складами';
$current_section = 'storage';
$breadcrumb = [
    ['title' => 'Склады', 'url' => '/admin/storage/']
];

// API настройки
$api_token = '922371:489411264005b482039f38b8ee21f6fb';
$api_base = 'https://joinposter.com/api';

// Функция для запросов к Poster API
function makePosterRequest($method, $params = []) {
    global $api_token, $api_base;
    
    $url = $api_base . '/' . $method . '?token=' . $api_token;
    foreach ($params as $key => $value) {
        $url .= '&' . $key . '=' . urlencode($value);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception("HTTP Error: " . $http_code);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Error: " . json_last_error_msg());
    }
    
    return $data;
}

// Получение списка складов
$storages = [];
$storages_error = '';

try {
    $storages_data = makePosterRequest('storage.getStorages');
    if (isset($storages_data['response'])) {
        $storages = $storages_data['response'];
    }
} catch (Exception $e) {
    $storages_error = $e->getMessage();
}

// Получение остатков (если выбран склад)
$leftovers = [];
$leftovers_error = '';
$selected_storage = '';

if (isset($_POST['get_leftovers']) && !empty($_POST['storage_id'])) {
    $selected_storage = $_POST['storage_id'];
    try {
        $leftovers_data = makePosterRequest('storage.getStorageLeftovers', ['storage_id' => $selected_storage]);
        if (isset($leftovers_data['response'])) {
            $leftovers = $leftovers_data['response'];
        }
    } catch (Exception $e) {
        $leftovers_error = $e->getMessage();
    }
}

// Начинаем вывод
ob_start();
?>

<style>
    .storage-page {
        padding: 0;
    }
    
    .storage-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        margin: -30px -30px 30px -30px;
    }
    
    .storage-header h1 {
        margin: 0 0 10px 0;
        font-size: 2rem;
        font-weight: 300;
    }
    
    .storage-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .filter-section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }
    
    .filter-row {
        display: flex;
        gap: 20px;
        align-items: end;
        flex-wrap: wrap;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #495057;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .error {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        border-left: 4px solid #dc3545;
    }
    
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        text-align: center;
    }
    
    .stat-card h3 {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 5px;
    }
    
    .stat-card p {
        color: #6c757d;
        font-weight: 500;
    }
    
    .table-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 15px;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 14px;
        cursor: pointer;
        user-select: none;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .table th:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    }
    
    .table th.sortable::after {
        content: '↕';
        position: absolute;
        right: 10px;
        opacity: 0.5;
        font-size: 12px;
    }
    
    .table th.sort-asc::after {
        content: '↑';
        opacity: 1;
    }
    
    .table th.sort-desc::after {
        content: '↓';
        opacity: 1;
    }
    
    .table td {
        padding: 15px;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: middle;
    }
    
    .table tr:hover {
        background-color: #f8f9fa;
    }
    
    .table tr:nth-child(even) {
        background-color: #fafbfc;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-product {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .badge-ingredient {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .badge-semi {
        background: #e8f5e8;
        color: #388e3c;
    }
    
    .quantity {
        font-weight: 600;
        font-size: 16px;
    }
    
    .quantity.positive {
        color: #28a745;
    }
    
    .quantity.negative {
        color: #dc3545;
    }
    
    .quantity.zero {
        color: #6c757d;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .form-group {
            min-width: 100%;
        }
        
        .table-container {
            overflow-x: auto;
        }
    }
</style>

<div class="storage-page">
    <div class="storage-header">
        <h1>📦 Управление складами</h1>
        <p>Получение и просмотр остатков товаров на складах</p>
    </div>
    
    <!-- Фильтр выбора склада -->
    <div class="filter-section">
        <form method="POST">
            <div class="filter-row">
                <div class="form-group">
                    <label for="storage_id">Выберите склад:</label>
                    <select name="storage_id" id="storage_id" class="form-control" required>
                        <option value="">-- Выберите склад --</option>
                        <?php foreach ($storages as $storage): ?>
                            <option value="<?= htmlspecialchars($storage['storage_id']) ?>" 
                                    <?= $selected_storage == $storage['storage_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($storage['storage_name']) ?> (ID: <?= $storage['storage_id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="get_leftovers" class="btn">
                        📦 Получить остатки
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Ошибки -->
    <?php if ($storages_error): ?>
        <div class="error">
            <strong>Ошибка загрузки складов:</strong> <?= htmlspecialchars($storages_error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($leftovers_error): ?>
        <div class="error">
            <strong>Ошибка загрузки остатков:</strong> <?= htmlspecialchars($leftovers_error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <?php if (!empty($leftovers)): ?>
        <div class="stats">
            <div class="stat-card">
                <h3><?= count($leftovers) ?></h3>
                <p>Всего позиций</p>
            </div>
            <div class="stat-card">
                <h3><?= count(array_filter($leftovers, function($item) { return $item['ingredient_left'] > 0; })) ?></h3>
                <p>С положительным остатком</p>
            </div>
            <div class="stat-card">
                <h3><?= count(array_filter($leftovers, function($item) { return $item['ingredient_left'] < 0; })) ?></h3>
                <p>С отрицательным остатком</p>
            </div>
            <div class="stat-card">
                <h3><?= count(array_filter($leftovers, function($item) { return $item['ingredient_left'] == 0; })) ?></h3>
                <p>С нулевым остатком</p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Таблица остатков -->
    <?php if (!empty($leftovers)): ?>
        <div class="table-container">
            <table class="table" id="leftoversTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="ingredient_name">Наименование</th>
                        <th class="sortable" data-sort="ingredient_left">Остаток</th>
                        <th class="sortable" data-sort="ingredient_unit">Единица измерения</th>
                        <th class="sortable" data-sort="ingredients_type">Тип</th>
                        <th class="sortable" data-sort="ingredient_cost">Цена за единицу</th>
                        <th class="sortable" data-sort="total_cost">Общая стоимость</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leftovers as $item): ?>
                        <?php
                        $quantity = $item['ingredient_left'];
                        $quantityClass = $quantity > 0 ? 'positive' : ($quantity < 0 ? 'negative' : 'zero');
                        $typeClass = '';
                        $typeName = '';
                        
                        switch($item['ingredients_type']) {
                            case '1':
                                $typeClass = 'badge-product';
                                $typeName = 'Товар';
                                break;
                            case '2':
                                $typeClass = 'badge-ingredient';
                                $typeName = 'Ингредиент';
                                break;
                            case '3':
                                $typeClass = 'badge-semi';
                                $typeName = 'Полуфабрикат';
                                break;
                            default:
                                $typeClass = 'badge-ingredient';
                                $typeName = 'Ингредиент';
                        }
                        ?>
                        <tr>
                            <td data-sort-value="<?= htmlspecialchars($item['ingredient_name']) ?>">
                                <strong><?= htmlspecialchars($item['ingredient_name']) ?></strong>
                            </td>
                            <td data-sort-value="<?= $quantity ?>">
                                <span class="quantity <?= $quantityClass ?>">
                                    <?= number_format($quantity, 3) ?>
                                </span>
                            </td>
                            <td data-sort-value="<?= htmlspecialchars($item['ingredient_unit']) ?>">
                                <?= htmlspecialchars($item['ingredient_unit']) ?>
                            </td>
                            <td data-sort-value="<?= $item['ingredients_type'] ?>">
                                <span class="badge <?= $typeClass ?>">
                                    <?= $typeName ?>
                                </span>
                            </td>
                            <td data-sort-value="<?= isset($item['ingredient_cost']) ? $item['ingredient_cost'] : 0 ?>">
                                <?= isset($item['ingredient_cost']) ? number_format($item['ingredient_cost'], 2) . ' ₫' : '—' ?>
                            </td>
                            <td data-sort-value="<?= isset($item['ingredient_cost']) && isset($item['ingredient_left']) ? $item['ingredient_cost'] * abs($item['ingredient_left']) : 0 ?>">
                                <?= isset($item['ingredient_cost']) && isset($item['ingredient_left']) ? 
                                    number_format($item['ingredient_cost'] * abs($item['ingredient_left']), 2) . ' ₫' : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (isset($_POST['get_leftovers']) && empty($leftovers_error)): ?>
        <div class="loading">
            Загрузка остатков...
        </div>
    <?php endif; ?>
</div>

<script>
// Сортировка таблицы
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('leftoversTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('th.sortable');
    let currentSort = { column: null, direction: 'asc' };
    
    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.sort;
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Определяем направление сортировки
            let direction = 'asc';
            if (currentSort.column === column && currentSort.direction === 'asc') {
                direction = 'desc';
            }
            
            // Убираем классы сортировки со всех заголовков
            headers.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
                h.classList.add('sortable');
            });
            
            // Добавляем классы к текущему заголовку
            this.classList.remove('sortable');
            this.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
            
            // Сортируем строки
            rows.sort((a, b) => {
                const aCell = a.querySelector(`td:nth-child(${Array.from(headers).indexOf(this) + 1})`);
                const bCell = b.querySelector(`td:nth-child(${Array.from(headers).indexOf(this) + 1})`);
                
                const aData = aCell ? aCell.getAttribute('data-sort-value') : '';
                const bData = bCell ? bCell.getAttribute('data-sort-value') : '';
                
                // Проверяем, являются ли значения числами
                const aNum = parseFloat(aData);
                const bNum = parseFloat(bData);
                
                let comparison = 0;
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    // Числовая сортировка
                    comparison = aNum - bNum;
                } else {
                    // Строковая сортировка
                    comparison = aData.localeCompare(bData, 'ru', { numeric: true });
                }
                
                return direction === 'asc' ? comparison : -comparison;
            });
            
            // Перестраиваем таблицу
            rows.forEach(row => tbody.appendChild(row));
            
            // Сохраняем текущую сортировку
            currentSort = { column, direction };
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>

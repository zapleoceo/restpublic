## Poster POS API v3 — Руководство и каталог методов (GoodZone)

Источник: [Poster POS API v3 (официальная документация)](https://dev.joinposter.com/docs/v3/web/index)

### Назначение
Этот документ описывает, как проект GoodZone должен обращаться к Poster Web API: базовые правила, формат параметров, обработка цен и дат, а также каталог методов Web API, сгруппированный по разделам. Примеры из ТЗ перенесены ниже. При любых расхождениях руководствуйтесь официальной документацией Poster.

### Базовые правила обращения к API
- **Базовый URL**: `https://joinposter.com/api`
- **Аутентификация**: каждый запрос содержит `token=POSTER_API_TOKEN` в query. Токен хранится ТОЛЬКО на backend и добавляется либо при прямых серверных запросах, либо прокси-роутером `/api/poster/*`.
- **Методы/форматы**:
  - Чтение: `GET` с query-параметрами
  - Создание/изменение: чаще `POST` с `application/x-www-form-urlencoded` (например, `clients.createClient`), либо `application/json` по описанию метода
- **Даты**:
  - В агрегирующих методах (например, `dash.getProductsSales`) используйте `date_from`/`date_to` формата `YYYYMMDD` (например, `20250131`)
- **Деньги**:
  - Poster возвращает суммы в минорных единицах (копейки). Наш backend нормализует цены, деля на 100 (донги). При отправке цен в Poster — умножайте на 100
- **Локализация наименований**:
  - Категории/товары используют поля `name_*` (например, `name_en`). В данных Poster заполнено `name_en`
- **Фильтрация видимости товаров**:
  - Исключать товары с `hidden === "1"`
  - Учитывать `spots[].visible !== "0"` (если массив `spots` присутствует)
- **Пагинация и поиск**:
  - Для списков: `num`, `offset`
  - Для поиска клиентов всегда передавайте `phone` (серверная фильтрация на стороне Poster)
- **Ошибки/таймауты**:
  - Обрабатывать HTTP-ошибки и ошибки уровня тела ответа
  - Таймауты/повторы — только на backend; на фронте использовать кэш

### Примеры запросов (серверные)

1) Получение категорий и продуктов меню (кэшируется backend 5 минут):
```bash
GET https://joinposter.com/api/menu.getCategories?token=POSTER_API_TOKEN
GET https://joinposter.com/api/menu.getProducts?token=POSTER_API_TOKEN
```

2) Поиск клиента по телефону (рекомендуемый способ):
```bash
GET https://joinposter.com/api/clients.getClients?token=POSTER_API_TOKEN&phone=%2B84349338758
```

3) Создание клиента (`application/x-www-form-urlencoded`):
```http
POST https://joinposter.com/api/clients.createClient?token=POSTER_API_TOKEN
Content-Type: application/x-www-form-urlencoded

client_name=John&client_lastname=Doe&client_phone=%2B84349338758&client_birthday=1990-01-01&client_sex=1&client_groups_id_client=2
```

4) Продажи по товарам для вычисления популярности:
```bash
GET https://joinposter.com/api/dash.getProductsSales?token=POSTER_API_TOKEN&date_from=20250101&date_to=20250131
```

5) Создание входящего заказа (цены — в копейках; скидка 20% при первом заказе):
```http
POST https://joinposter.com/api/incomingOrders.createIncomingOrder?token=POSTER_API_TOKEN
Content-Type: application/json

{
  "spot_id": 1,
  "client_id": 12345,
  "products": [
    { "product_id": 1001, "count": 2, "price": 1500000 },
    { "product_id": 1002, "count": 1, "price": 990000 }
  ],
  "comment": "Table A1",
  "discounts": [
    { "type": "percent", "value": 20 }
  ]
}
```

6) Проверка «первого заказа» клиента (по ТЗ):
```bash
GET https://joinposter.com/api/dash.getTransactions?token=POSTER_API_TOKEN&dateFrom=2020-01-01&dateTo=2025-12-31
```
Фильтрация по `client_id` выполняется на backend (если не поддерживается соответствующей параметризацией напрямую методом аккаунта).

7) Добавление товаров к существующему входящему заказу (если доступно в вашем аккаунте):
```http
POST https://joinposter.com/api/incomingOrders.updateIncomingOrder?token=POSTER_API_TOKEN
Content-Type: application/json

{
  "incoming_order_id": 98765,
  "products": [
    { "product_id": 1003, "count": 1, "price": 1250000 },
    { "product_id": 1004, "count": 2, "price": 990000 }
  ],
  "comment": "Добавлены позиции из веб-корзины"
}
```
Примечание: альтернативные методы добавления без полной замены состава могут различаться; руководствуйтесь документацией вашего аккаунта.

8) Закрытие чека по оплате (при наличии метода в аккаунте):
```http
POST https://joinposter.com/api/incomingOrders.closeIncomingOrder?token=POSTER_API_TOKEN
Content-Type: application/json

{
  "incoming_order_id": 98765,
  "pays": [
    { "type": "card", "sum": 3230000 }
  ],
  "comment": "Оплачено через SePay"
}
```
Примечание: если API-метод закрытия недоступен, закрытие чека выполняется кассой Poster; в нашем backend фиксируется факт оплаты (SePay) и связывается с заказом.

### Каталог методов Web API v3 по разделам (оглавление)
- Меню и каталог (`menu.*`)
- Клиенты и группы (`clients.*`)
- Входящие заказы (`incomingOrders.*`)
- Продажи и аналитика (`dash.*`)
- Склад и запасы (`storage.*`/`warehouse.*`)
- Точки продаж и столы (`spots.*`, `tables.*`)
- Сотрудники и роли (`employees.*`/`staff.*`)
- Платежи и касса (`payments.*`, `cash.*`, `shifts.*`)
- Скидки, акции, лояльность (`discounts.*`, `loyalty.*`, `clientGroups.*`)
- Настройки, печать, вебхуки (`settings.*`, `print.*`, `webhooks.*`)

Ниже приведены ключевые методы по каждому разделу. Детальные параметры, типы данных, ограничения и дополнительные методы смотрите в официальном справочнике: [Poster POS API v3](https://dev.joinposter.com/docs/v3/web/index).

#### Меню и каталог (`menu.*`)
- `menu.getCategories` — список категорий меню
- `menu.getProducts` — список продуктов (товары, блюда)
- `menu.getModifiers` — список модификаторов (если включено)
- `menu.getIngredients` — список ингредиентов (если используется склад)
- `menu.getStops` — стоп-лист (если доступно)
- `menu.getPrices` — цены по прайс-листам/точкам (если доступно)

#### Клиенты и группы (`clients.*`)
- `clients.getClients` — список клиентов (поддерживает `phone`)
- `clients.getClient` — карточка клиента по `client_id`
- `clients.createClient` — создание клиента
- `clients.updateClient` — обновление клиента (если доступно)
- `clients.deleteClient` — удаление/деактивация (если доступно)
- `clients.getClientsGroups` — список групп клиентов
- `clients.addClientToGroup`/`clients.updateClientGroup` — изменение группы клиента (если доступно)

#### Входящие заказы (`incomingOrders.*`)
- `incomingOrders.createIncomingOrder` — создать входящий заказ
- `incomingOrders.updateIncomingOrder` — обновить состав/поля заказа (если доступно)
- `incomingOrders.getIncomingOrders` — получить список/заказ по фильтрам (если доступно)
- `incomingOrders.closeIncomingOrder` — закрыть заказ c оплатой (если доступно)

#### Продажи и аналитика (`dash.*`)
- `dash.getProductsSales` — продажи по товарам за период
- `dash.getTransactions` — транзакции (используется для проверки первого заказа по ТЗ)
- `dash.getOrders` — заказы за период (если доступно)
- `dash.getRevenue`/`dash.getSales` — выручка/продажи (если доступно)

#### Склад и запасы (`storage.*`/`warehouse.*`)
- `storage.getRemains` — остатки (если доступно)
- `storage.getSupplies` — поставки (если доступно)
- `storage.createWriteOff`/`storage.getWriteOffs` — списания (если доступно)
- `storage.getInventory` — инвентаризации (если доступно)

#### Точки продаж и столы (`spots.*`, `tables.*`)
- `spots.getSpots` — список точек продаж
- `tables.getTables` — список столов/посадочных мест (если доступно)

#### Сотрудники и роли (`employees.*`/`staff.*`)
- `employees.getEmployees` — список сотрудников (если доступно)
- `employees.getEmployee` — карточка сотрудника (если доступно)
- `employees.createEmployee`/`employees.updateEmployee` — управление сотрудниками (если доступно)

#### Платежи и касса (`payments.*`, `cash.*`, `shifts.*`)
- `shifts.getShifts` — смены (если доступно)
- `cash.getCashMovements` — движения по кассе (если доступно)
- `payments.getPaymentTypes` — типы оплат (если доступно)

#### Скидки, акции, лояльность (`discounts.*`, `loyalty.*`, `clientGroups.*`)
- `discounts.getDiscounts` — скидки/акции (если доступно)
- `loyalty.getPrograms` — программы лояльности (если доступно)
- `clientGroups.getGroups` — группы клиентов (альтернативная точка, если разделён)

#### Настройки, печать, вебхуки (`settings.*`, `print.*`, `webhooks.*`)
- `settings.getSettings` — настройки (если доступно)
- `print.printReceipt`/`print.getTemplates` — печать/шаблоны (если доступно)
- `webhooks.getWebhooks`/`webhooks.createWebhook` — вебхуки (если доступно)

### Потоки по ТЗ: регистрация/авторизация и заказ
- Проверка телефона: `clients.getClients?phone=...`
- Регистрация: `clients.createClient`
- Определение первого заказа: `dash.getTransactions` (фильтрация по `client_id`)
- Создание заказа: `incomingOrders.createIncomingOrder` (+ скидка 20% при первом заказе)
- Обновление состава заказа: `incomingOrders.updateIncomingOrder` (если доступно)
- Закрытие по оплате: `incomingOrders.closeIncomingOrder` (если доступно) или закрытие на кассе

### Замечания по безопасности
- Токен Poster хранится только на сервере и никогда не передается на фронтенд
- Все запросы к Poster выполняются backend-ом напрямую или через прокси `/api/poster/*`
- Слежение за лимитами, обработка ошибок, логирование — только на серверной стороне

### Ссылки
- Официальная документация Poster: [https://dev.joinposter.com/docs/v3/web/index](https://dev.joinposter.com/docs/v3/web/index)



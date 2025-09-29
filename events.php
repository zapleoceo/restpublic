<?php
/**
 * Страница календаря событий с недельным видом
 * Отображает события в хронологическом порядке по неделям
 */

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Initialize page content service
require_once __DIR__ . '/classes/PageContentService.php';
$pageContentService = new PageContentService();

// Initialize events service
require_once __DIR__ . '/classes/EventsService.php';
$eventsService = new EventsService();

// Get current language
$currentLanguage = $pageContentService->getLanguage();

// Get page content from database
$pageContent = $pageContentService->getPageContent('events', $currentLanguage);
$pageMeta = $pageContent['meta'] ?? [];

// Helper function for safe HTML output
function safeHtml($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Set page title and meta tags
$pageTitle = $pageMeta['title'] ?? 'Календарь событий';
$pageDescription = $pageMeta['description'] ?? 'Календарь событий ресторана North Republic';
$pageKeywords = $pageMeta['keywords'] ?? 'события, календарь, ресторан';
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" class="no-js">
<head>
    <!--- basic page needs
    ================================================== -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo safeHtml($pageTitle); ?></title>
    <meta name="description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta name="keywords" content="<?php echo safeHtml($pageKeywords); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://northrepublic.me/events">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/events-widget.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/events-calendar.css?v=<?php echo time(); ?>">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="fonts/Serati.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="js/main.js" as="script">
    
    <!-- favicons
    ================================================== -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://northrepublic.me/events">
    <meta property="og:title" content="<?php echo safeHtml($pageTitle); ?>">
    <meta property="og:description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta property="og:image" content="https://northrepublic.me/images/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://northrepublic.me/events">
    <meta property="twitter:title" content="<?php echo safeHtml($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta property="twitter:image" content="https://northrepublic.me/images/logo.png">
</head>

<body id="top">
    
    <!-- preloader
    ================================================== -->
    <div id="preloader">
        <div id="loader" class="dots-fade">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- page wrap
    ================================================== -->
    <div id="page" class="s-pagewrap ss-events">

        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- # events calendar
        ================================================== -->
        <section id="events-calendar" class="container s-events target-section">
            <div class="row s-events__header">
                <div class="column xl-12 section-header-wrap">
                    <div class="section-header" data-num="01">
                        <h2 class="text-display-title"><?php echo safeHtml($pageMeta['events_calendar_title'] ?? 'Календарь событий'); ?></h2>
                    </div>
                </div> <!-- end section-header-wrap -->
            </div> <!-- end s-events__header -->

            <div class="events-calendar" role="region" aria-label="<?php echo safeHtml($pageMeta['events_calendar_aria'] ?? 'Календарь событий'); ?>">
                <div id="calendar-container">
                    <!-- Календарь будет загружен через JavaScript -->
                </div>
            </div>
        </section> <!-- end s-events -->

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div>

    <!-- Event Details Modal -->
    <div id="event-modal" class="event-modal" style="display: none;">
        <div class="event-modal__overlay"></div>
        <div class="event-modal__content">
            <button class="event-modal__close" aria-label="Закрыть модальное окно">&times;</button>
            <div class="event-modal__body">
                <div class="event-modal__image-container">
                    <img id="modal-event-image" class="event-modal__image" src="" alt="">
                </div>
                <div class="event-modal__info">
                    <h3 id="modal-event-title" class="event-modal__title"></h3>
                    <div id="modal-event-date" class="event-modal__date"></div>
                    <div id="modal-event-description" class="event-modal__description"></div>
                    <div id="modal-event-conditions" class="event-modal__conditions"></div>
                    <a id="modal-event-link" href="#" class="event-modal__link" target="_blank" rel="noopener noreferrer">
                        <?php echo safeHtml($pageMeta['event_details_link'] ?? 'Подробнее'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript
    ================================================== -->
    <script src="js/plugins.js" defer></script>
    <script src="js/main.js" defer></script>
    
    <script>
        // Events Calendar JavaScript
        class EventsCalendar {
            constructor() {
                this.events = [];
                this.language = document.documentElement.lang || 'ru';
                this.init();
            }

            async init() {
                await this.loadEvents();
                this.renderCalendar();
                this.bindEvents();
            }

            async loadEvents() {
                try {
                    // Загружаем события на 2 недели (текущая + следующая)
                    const today = new Date();
                    const startDate = today.toISOString().split('T')[0];
                    
                    const response = await fetch(`/api/events.php?start_date=${startDate}&days=14&language=${this.language}`);
                    const events = await response.json();
                    this.events = events;
                } catch (error) {
                    console.error('Ошибка загрузки событий:', error);
                    this.events = [];
                }
            }

            renderCalendar() {
                const container = document.getElementById('calendar-container');
                container.innerHTML = '';

                // Генерируем 2 недели (текущая + следующая)
                const today = new Date();
                const currentWeekStart = this.getWeekStart(today);
                
                // Первая неделя (текущая)
                const week1 = this.generateWeek(currentWeekStart, 1);
                container.appendChild(week1);
                
                // Вторая неделя (следующая)
                const nextWeekStart = new Date(currentWeekStart);
                nextWeekStart.setDate(currentWeekStart.getDate() + 7);
                const week2 = this.generateWeek(nextWeekStart, 2);
                container.appendChild(week2);
            }

            getWeekStart(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day; // Понедельник = 1, Воскресенье = 0
                return new Date(d.setDate(diff));
            }

            generateWeek(startDate, weekNumber) {
                const weekDiv = document.createElement('div');
                weekDiv.className = 'calendar-week';
                weekDiv.dataset.week = weekNumber;

                // Заголовок недели
                const weekHeader = document.createElement('div');
                weekHeader.className = 'calendar-week__header';
                weekHeader.innerHTML = `
                    <h3 class="calendar-week__title">
                        ${this.getWeekTitle(startDate, weekNumber)}
                    </h3>
                `;
                weekDiv.appendChild(weekHeader);

                // Дни недели
                const daysContainer = document.createElement('div');
                daysContainer.className = 'calendar-week__days';

                for (let i = 0; i < 7; i++) {
                    const dayDate = new Date(startDate);
                    dayDate.setDate(startDate.getDate() + i);
                    const dayElement = this.generateDay(dayDate);
                    daysContainer.appendChild(dayElement);
                }

                weekDiv.appendChild(daysContainer);
                return weekDiv;
            }

            getWeekTitle(startDate, weekNumber) {
                const endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 6);
                
                const monthNames = {
                    'ru': ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 
                           'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
                    'en': ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'],
                    'vi': ['tháng 1', 'tháng 2', 'tháng 3', 'tháng 4', 'tháng 5', 'tháng 6',
                           'tháng 7', 'tháng 8', 'tháng 9', 'tháng 10', 'tháng 11', 'tháng 12']
                };

                const months = monthNames[this.language] || monthNames['ru'];
                
                if (startDate.getMonth() === endDate.getMonth()) {
                    return `${startDate.getDate()} - ${endDate.getDate()} ${months[startDate.getMonth()]}`;
                } else {
                    return `${startDate.getDate()} ${months[startDate.getMonth()]} - ${endDate.getDate()} ${months[endDate.getMonth()]}`;
                }
            }

            generateDay(date) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.dataset.date = date.toISOString().split('T')[0];

                const dateStr = date.toISOString().split('T')[0];
                const dayEvents = this.events.filter(event => event.date === dateStr);

                // Заголовок дня
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day__header';
                
                const dayNames = {
                    'ru': ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                    'en': ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'vi': ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']
                };
                
                const dayName = (dayNames[this.language] || dayNames['ru'])[date.getDay()];
                dayHeader.innerHTML = `
                    <div class="calendar-day__name">${dayName}</div>
                    <div class="calendar-day__number">${date.getDate()}</div>
                `;
                dayDiv.appendChild(dayHeader);

                // События дня
                const eventsContainer = document.createElement('div');
                eventsContainer.className = 'calendar-day__events';

                if (dayEvents.length > 0) {
                    dayEvents.forEach(event => {
                        const eventElement = this.generateEventCard(event);
                        eventsContainer.appendChild(eventElement);
                    });
                } else {
                    // Пустой день
                    const emptyEvent = this.generateEmptyDay(date);
                    eventsContainer.appendChild(emptyEvent);
                }

                dayDiv.appendChild(eventsContainer);
                return dayDiv;
            }

            generateEventCard(event) {
                const eventDiv = document.createElement('div');
                eventDiv.className = 'calendar-event';
                eventDiv.dataset.eventId = event.id;
                eventDiv.dataset.eventLink = event.link || '#';

                const backgroundImage = event.image || '/images/event-default.png';
                const dateObj = new Date(event.date);
                const formattedDate = dateObj.toLocaleDateString('ru-RU');
                
                // Добавляем день недели к дате
                const dayNames = {
                    'ru': ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                    'en': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'vi': ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                };
                const dayOfWeek = (dayNames[this.language] || dayNames['ru'])[dateObj.getDay()];
                const dateWithDay = `${formattedDate} (${dayOfWeek})`;
                
                const conditionsLabels = {
                    'ru': 'Условия участия:',
                    'en': 'Participation conditions:',
                    'vi': 'Điều kiện tham gia:'
                };
                const conditionsLabel = conditionsLabels[this.language] || conditionsLabels['ru'];

                eventDiv.innerHTML = `
                    <div class="calendar-event__image-container">
                        <img class="calendar-event__image" 
                             data-src="${backgroundImage}" 
                             alt="${event.title}"
                             loading="lazy">
                        <div class="calendar-event__image-placeholder">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                    <div class="calendar-event__overlay">
                        <div class="calendar-event__title">${event.title}</div>
                        <div class="calendar-event__date">${dateWithDay} ${event.time || '19:00'}</div>
                        <div class="calendar-event__description">
                            ${event.description || ''}
                        </div>
                        <div class="calendar-event__conditions">
                            <strong>${conditionsLabel}</strong><br>
                            ${event.conditions || ''}
                        </div>
                    </div>
                `;

                return eventDiv;
            }

            generateEmptyDay(date) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'calendar-event empty-day';

                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('ru-RU');
                
                // Добавляем день недели к дате для пустых дней
                const dayNames = {
                    'ru': ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                    'en': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'vi': ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                };
                const dayOfWeek = (dayNames[this.language] || dayNames['ru'])[dateObj.getDay()];
                const dateWithDay = `${formattedDate} (${dayOfWeek})`;
                
                const messages = {
                    'ru': {
                        title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'Мы еще не придумали что у нас тут будет.'); ?>',
                        text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Есть идеи?'); ?>',
                        link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Свяжитесь с нами!'); ?>'
                    },
                    'en': {
                        title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'We haven\'t figured out what we\'ll have here yet.'); ?>',
                        text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Have ideas?'); ?>',
                        link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Contact us!'); ?>'
                    },
                    'vi': {
                        title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'Chúng tôi chưa nghĩ ra sẽ có gì ở đây.'); ?>',
                        text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Có ý tưởng?'); ?>',
                        link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Liên hệ với chúng tôi!'); ?>'
                    }
                };
                
                const msg = messages[this.language] || messages['ru'];

                emptyDiv.innerHTML = `
                    <div class="calendar-event__overlay">
                        <div class="calendar-event__title">${msg.title}</div>
                        <div class="calendar-event__date">${dateWithDay}</div>
                        <div class="calendar-event__description">
                            ${msg.text}
                        </div>
                        <div class="calendar-event__conditions">
                            <a href="#footer" class="contact-link">${msg.link}</a>
                        </div>
                    </div>
                `;

                return emptyDiv;
            }

            bindEvents() {
                // Обработка кликов по событиям
                document.addEventListener('click', (e) => {
                    const eventCard = e.target.closest('.calendar-event');
                    if (eventCard && !eventCard.classList.contains('empty-day')) {
                        const eventId = eventCard.dataset.eventId;
                        const event = this.events.find(e => e.id === eventId);
                        if (event) {
                            this.showEventModal(event);
                        }
                    }
                });

                // Обработка кликов по пустым дням
                document.addEventListener('click', (e) => {
                    const emptyDay = e.target.closest('.calendar-event.empty-day');
                    if (emptyDay) {
                        const footer = document.querySelector('#footer');
                        if (footer) {
                            footer.scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                });

                // Закрытие модального окна
                const modal = document.getElementById('event-modal');
                const closeBtn = modal.querySelector('.event-modal__close');
                const overlay = modal.querySelector('.event-modal__overlay');

                closeBtn.addEventListener('click', () => this.hideEventModal());
                overlay.addEventListener('click', () => this.hideEventModal());

                // Lazy loading изображений
                this.initLazyLoading();
            }

            showEventModal(event) {
                const modal = document.getElementById('event-modal');
                const image = document.getElementById('modal-event-image');
                const title = document.getElementById('modal-event-title');
                const date = document.getElementById('modal-event-date');
                const description = document.getElementById('modal-event-description');
                const conditions = document.getElementById('modal-event-conditions');
                const link = document.getElementById('modal-event-link');

                // Заполняем модальное окно данными события
                image.src = event.image || '/images/event-default.png';
                image.alt = event.title;
                title.textContent = event.title;
                
                const dateObj = new Date(event.date);
                const formattedDate = dateObj.toLocaleDateString('ru-RU');
                const dayNames = {
                    'ru': ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                    'en': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    'vi': ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                };
                const dayOfWeek = (dayNames[this.language] || dayNames['ru'])[dateObj.getDay()];
                date.textContent = `${formattedDate} (${dayOfWeek}) ${event.time || '19:00'}`;
                
                description.textContent = event.description || '';
                
                const conditionsLabels = {
                    'ru': 'Условия участия:',
                    'en': 'Participation conditions:',
                    'vi': 'Điều kiện tham gia:'
                };
                const conditionsLabel = conditionsLabels[this.language] || conditionsLabels['ru'];
                conditions.innerHTML = `<strong>${conditionsLabel}</strong><br>${event.conditions || ''}`;
                
                link.href = event.link || '#';
                link.style.display = (event.link && event.link !== '#') ? 'inline-block' : 'none';

                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            hideEventModal() {
                const modal = document.getElementById('event-modal');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            initLazyLoading() {
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                const container = img.closest('.calendar-event__image-container');
                                const placeholder = container?.querySelector('.calendar-event__image-placeholder');
                                
                                if (img.dataset.src) {
                                    const tempImg = new Image();
                                    
                                    tempImg.onload = () => {
                                        img.src = img.dataset.src;
                                        img.classList.add('loaded');
                                        img.removeAttribute('data-src');
                                        
                                        if (placeholder) {
                                            placeholder.style.opacity = '0';
                                            setTimeout(() => {
                                                placeholder.style.display = 'none';
                                            }, 300);
                                        }
                                    };
                                    
                                    tempImg.onerror = () => {
                                        img.src = '/images/event-default.png';
                                        img.classList.add('loaded', 'fallback');
                                        img.removeAttribute('data-src');
                                        
                                        if (placeholder) {
                                            placeholder.style.opacity = '0';
                                            setTimeout(() => {
                                                placeholder.style.display = 'none';
                                            }, 300);
                                        }
                                    };
                                    
                                    tempImg.src = img.dataset.src;
                                }
                                
                                observer.unobserve(img);
                            }
                        });
                    }, {
                        rootMargin: '50px 0px',
                        threshold: 0.1
                    });
                    
                    document.querySelectorAll('.calendar-event__image[data-src]').forEach(img => {
                        imageObserver.observe(img);
                    });
                }
            }
        }

        // Инициализация календаря после загрузки DOM
        document.addEventListener('DOMContentLoaded', () => {
            new EventsCalendar();
        });
    </script>

</body>
</html>

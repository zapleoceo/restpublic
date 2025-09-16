// Events Widget JavaScript
class EventsWidget {
    constructor() {
        this.datesSwiper = null;
        this.events = [];
        this.calendarDays = [];
        this.eventsByDate = {};
        this.init();
    }

    init() {
        this.initSwiper();
        this.loadEvents();
    }

    initSwiper() {
        // Инициализация слайдера дат
        this.datesSwiper = new Swiper('.dates-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 12,
            freeMode: true,
            mousewheel: {
                enabled: true
            },
            speed: 300,
            breakpoints: {
                320: {
                    slidesPerView: 'auto',
                    spaceBetween: 10
                },
                768: {
                    slidesPerView: 'auto',
                    spaceBetween: 12
                }
            }
        });
    }

    async loadEvents() {
        try {
            // Загружаем события из API
            const response = await fetch('/api/events.php');
            const events = await response.json();
            this.events = events;
            this.processEvents();
            this.generateCalendar();
            this.renderDates();
            this.renderEvents();
            this.bindEvents();
        } catch (error) {
            console.error('Ошибка загрузки событий:', error);
            // Загружаем тестовые данные
            this.loadTestData();
        }
    }

    loadTestData() {
        // Тестовые данные для демонстрации
        this.events = [
            {
                id: 1,
                title: 'Music Festival',
                event_date: this.getDateString(0), // сегодня
                price: 2500,
                image: '/images/events/music-festival.jpg',
                description: 'Грандиозный музыкальный фестиваль с участием лучших исполнителей'
            },
            {
                id: 2,
                title: 'Business Conference',
                event_date: this.getDateString(1), // завтра
                price: 1500,
                image: '/images/events/business-conference.jpg',
                description: 'Конференция для бизнес-лидеров и предпринимателей'
            },
            {
                id: 3,
                title: 'Art Exhibition',
                event_date: this.getDateString(2), // послезавтра
                price: 800,
                image: '/images/events/art-exhibition.jpg',
                description: 'Выставка современного искусства от местных художников'
            },
            {
                id: 4,
                title: 'Food Festival',
                event_date: this.getDateString(3),
                price: 1200,
                image: '/images/events/food-festival.jpg',
                description: 'Фестиваль кулинарного искусства и гастрономии'
            },
            {
                id: 5,
                title: 'Tech Meetup',
                event_date: this.getDateString(5),
                price: 500,
                image: '/images/events/tech-meetup.jpg',
                description: 'Встреча IT-специалистов и обсуждение новых технологий'
            }
        ];
        
        this.processEvents();
        this.generateCalendar();
        this.renderDates();
        this.renderEvents();
        this.bindEvents();
    }

    getDateString(daysFromToday) {
        const date = new Date();
        date.setDate(date.getDate() + daysFromToday);
        return date.toISOString().split('T')[0];
    }

    processEvents() {
        // Группируем события по дате
        this.eventsByDate = {};
        this.events.forEach(event => {
            const date = event.event_date;
            if (!this.eventsByDate[date]) {
                this.eventsByDate[date] = [];
            }
            this.eventsByDate[date].push(event);
        });
    }

    generateCalendar() {
        // Генерируем массив из 14 дней, начиная с сегодня
        this.calendarDays = [];
        const today = new Date();
        
        for (let i = 0; i < 14; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            
            const day = date.getDate();
            const month = this.getMonthShort(date.getMonth());
            const dateString = date.toISOString().split('T')[0];
            const hasEvent = this.eventsByDate[dateString] && this.eventsByDate[dateString].length > 0;
            
            this.calendarDays.push({
                day: day,
                month: month,
                date: dateString,
                hasEvent: hasEvent
            });
        }
    }

    getMonthShort(monthIndex) {
        const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 
                       'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
        return months[monthIndex];
    }

    renderDates() {
        const datesWrapper = document.getElementById('dates-wrapper');
        datesWrapper.innerHTML = '';
        
        this.calendarDays.forEach((day, index) => {
            const slideEl = document.createElement('div');
            slideEl.className = 'swiper-slide';
            slideEl.dataset.date = day.date;
            slideEl.dataset.index = index;
            
            if (day.hasEvent) {
                slideEl.classList.add('has-event');
            }
            
            if (index === 0) {
                slideEl.classList.add('active');
            }
            
            slideEl.innerHTML = `
                <div>${day.day}</div>
                <div style="font-size: 10px; margin-top: 2px;">${day.month}</div>
            `;
            
            datesWrapper.appendChild(slideEl);
        });
        
        this.datesSwiper.update();
    }

    renderEvents() {
        const eventsDisplay = document.getElementById('events-display');
        const activeDate = document.querySelector('.dates-swiper .swiper-slide.active');
        
        if (!activeDate) return;
        
        const selectedDate = activeDate.dataset.date;
        const eventsForDate = this.eventsByDate[selectedDate] || [];
        
        eventsDisplay.innerHTML = '';
        
        if (eventsForDate.length === 0) {
            eventsDisplay.innerHTML = '<div class="no-events">На эту дату событий нет</div>';
            return;
        }
        
        eventsForDate.forEach(event => {
            const eventCard = document.createElement('div');
            eventCard.className = 'event-card';
            
            const formattedPrice = Number(event.price).toLocaleString('ru-RU') + '₽';
            
            eventCard.innerHTML = `
                <img src="${event.image}" alt="${event.title}" class="event-card__image">
                <div class="event-card__content">
                    <h3 class="event-card__title">${event.title}</h3>
                    <div class="event-card__price">${formattedPrice}</div>
                    <p class="event-card__description">${event.description}</p>
                    <a href="/events/${event.id}" class="event-card__link">Подробнее</a>
                </div>
            `;
            
            eventsDisplay.appendChild(eventCard);
        });
    }

    bindEvents() {
        // Обработка кликов по датам
        const dateSlides = document.querySelectorAll('.dates-swiper .swiper-slide');
        dateSlides.forEach(slide => {
            slide.addEventListener('click', (e) => {
                // Убираем активный класс у всех дат
                dateSlides.forEach(s => s.classList.remove('active'));
                // Добавляем активный класс выбранной дате
                e.currentTarget.classList.add('active');
                // Отрисовываем события для выбранной даты
                this.renderEvents();
            });
        });
    }
}

// Инициализация виджета при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new EventsWidget();
});

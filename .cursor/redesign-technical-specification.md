# 🎨 Техническое задание по редизайну North Republic

## 📋 Общая информация

**Проект**: North Republic - развлекательный комплекс  
**Шаблон**: Lounge - Restaurant Website Template  
**Структура**: Единая страница с якорными ссылками  
**Технологии**: React 18, Tailwind CSS, Node.js, MongoDB  
**Домен**: https://northrepublic.me  

## 🏗️ Архитектура сайта

### Структура страниц
```
/ (главная) - единая страница с секциями
├── #intro - приветствие
├── #about - о нас  
├── #menu - превью меню (5 популярных категорий)
├── #services - услуги (кнопки)
├── #events - афиша (карусель постеров)
└── #testimonials - отзывы

/menu - полная страница меню с корзиной
/events - календарь событий
/fast/:tableId - быстрый доступ (наследует стили)
/events/:eventId - страница события
/admin - админ панель
```

## 🎨 Дизайн-система на основе шаблона Lounge

### Цветовая палитра
```css
/* Основные цвета шаблона */
--color-1-50: #f4f9f7;    /* Светло-зеленый */
--color-1-500: #468672;   /* Основной зеленый */
--color-1-900: #253c35;   /* Темно-зеленый */

--color-2-50: #f9f7f3;    /* Светло-бежевый */
--color-2-500: #b1885e;   /* Основной бежевый */
--color-2-900: #5a4134;   /* Темно-бежевый */

/* Нейтральные цвета */
--color-neutral-50: #efefef;
--color-neutral-500: #5f6362;
--color-neutral-900: #131414;
--color-neutral-950: #090a0a;
```

### Типографика
```css
/* Шрифты шаблона */
--font-1: "Roboto Flex", Sans-Serif;     /* Основной текст */
--font-2: "Playfair Display", Serif;     /* Заголовки */
--font-mono: Consolas, monospace;        /* Код */
```

### Компоненты шаблона для переиспользования
- **Header** - навигация с логотипом
- **Section Header** - заголовки секций с номерами
- **Tab Navigation** - табы для меню
- **Menu List** - список блюд
- **Gallery Grid** - сетка для афиши событий
- **Testimonials Slider** - карусель отзывов
- **Footer** - подвал с контактами

## 🏗️ Архитектурные принципы

### 1. **Единый источник истины (Single Source of Truth)**
- Все стили определяются только в одном месте
- Компоненты не переопределяют стили друг друга
- Конфигурация централизована

### 2. **Композиция вместо наследования**
- Используем композицию компонентов
- Избегаем глубокого наследования стилей
- Каждый компонент самодостаточен

### 3. **Принцип DRY (Don't Repeat Yourself)**
- Никакого дублирования кода
- Переиспользуемые утилиты и хуки
- Единые константы и типы

## 🎨 Система дизайн-токенов

### Централизованные токены
```javascript
// constants/designTokens.js
export const DESIGN_TOKENS = {
  colors: {
    primary: {
      50: '#f4f9f7',
      100: '#daede5',
      500: '#468672',
      600: '#366b5b',
      900: '#253c35',
    },
    secondary: {
      50: '#f9f7f3',
      100: '#f2ede2',
      500: '#b1885e',
      600: '#a47652',
      900: '#5a4134',
    },
    neutral: {
      50: '#efefef',
      500: '#5f6362',
      900: '#131414',
      950: '#090a0a',
    }
  },
  typography: {
    fonts: {
      sans: '"Roboto Flex", sans-serif',
      serif: '"Playfair Display", serif',
    },
    sizes: {
      xs: '0.75rem',
      sm: '0.875rem',
      base: '1rem',
      lg: '1.125rem',
      xl: '1.25rem',
      '2xl': '1.5rem',
      '3xl': '1.875rem',
    }
  },
  spacing: {
    xs: '0.25rem',
    sm: '0.5rem',
    md: '1rem',
    lg: '1.5rem',
    xl: '2rem',
    '2xl': '3rem',
  },
  breakpoints: {
    sm: '640px',
    md: '768px',
    lg: '1024px',
    xl: '1280px',
  }
};
```

## 🧩 Система компонентов

### Базовые компоненты (не переопределяются)
```jsx
// components/ui/BaseButton.jsx
export const BaseButton = ({ 
  variant = 'primary', 
  size = 'md', 
  children, 
  className = '', 
  ...props 
}) => {
  const baseClasses = 'font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
  
  const variants = {
    primary: 'bg-primary-500 hover:bg-primary-600 text-white focus:ring-primary-500',
    secondary: 'bg-secondary-500 hover:bg-secondary-600 text-white focus:ring-secondary-500',
    outline: 'border border-primary-500 text-primary-500 hover:bg-primary-50 focus:ring-primary-500',
    ghost: 'text-neutral-700 hover:bg-neutral-100 focus:ring-neutral-500'
  };
  
  const sizes = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  };

  const classes = `${baseClasses} ${variants[variant]} ${sizes[size]} ${className}`;

  return (
    <button className={classes} {...props}>
      {children}
    </button>
  );
};
```

### Компоненты секций (без переопределения стилей)
```jsx
// components/sections/SectionWrapper.jsx
export const SectionWrapper = ({ 
  id, 
  className = '', 
  children 
}) => (
  <section 
    id={id} 
    className={`container target-section ${className}`}
  >
    {children}
  </section>
);

// components/sections/SectionHeader.jsx
export const SectionHeader = ({ 
  number, 
  title, 
  className = '' 
}) => (
  <div className={`section-header ${className}`} data-num={number}>
    <h2 className="text-display-title">{title}</h2>
  </div>
);
```

## 🎯 Главная страница

### Навигация
```jsx
// components/layout/Header.jsx
<header className="header">
  <div className="header__content">
    <div className="header__logo">
      <Link to="/">
        <img src="/img/logo.png" alt="North Republic" />
      </Link>
    </div>
    
    <nav className="header__nav">
      <ul className="header__nav-list">
        <li><a href="#intro">{t('nav.home')}</a></li>
        <li><a href="#about">{t('nav.about')}</a></li>
        <li><a href="#menu">{t('nav.menu')}</a></li>
        <li><a href="#services">{t('nav.services')}</a></li>
        <li><a href="#events">{t('nav.events')}</a></li>
        <li><Link to="/events">{t('nav.events_calendar')}</Link></li>
        <li><Link to="/menu">{t('nav.full_menu')}</Link></li>
      </ul>
    </nav>
    
    <div className="header__actions">
      <LanguageSwitcher />
      <CartButton />
    </div>
  </div>
</header>
```

### Секция Intro
```jsx
<section id="intro" className="container s-intro target-section">
  <div className="grid-block s-intro__content">
    <div className="intro-header">
      <div className="intro-header__overline">
        {t('intro.welcome')} {/* "Добро пожаловать в" */}
      </div>
      <h1 className="intro-header__big-type">
        {t('intro.title')} {/* "Республика Север" */}
      </h1>
    </div>
    
    <figure className="intro-pic-primary">
      <img src="/img/hero-bg.jpg" alt="North Republic" />
    </figure>
    
    <div className="intro-block-content">
      <div className="intro-block-content__text-wrap">
        <p className="intro-block-content__text">
          {t('intro.subtitle')} {/* Описание из БД */}
        </p>
      </div>
    </div>
  </div>
</section>
```

### Секция About
```jsx
<section id="about" className="container s-about target-section">
  <div className="row s-about__content">
    <div className="column xl-4 lg-5 md-12 s-about__content-start">
      <div className="section-header" data-num="01">
        <h2 className="text-display-title">{t('about.title')}</h2>
      </div>
      <figure className="about-pic-primary">
        <img src="/img/about-main.jpg" alt="About" />
      </figure>
    </div>
    
    <div className="column xl-6 lg-6 md-12 s-about__content-end">
      <div dangerouslySetInnerHTML={{ __html: aboutContent }} />
    </div>
  </div>
</section>
```

### Секция Menu (Превью)
```jsx
<section id="menu" className="container s-menu target-section">
  <div className="row s-menu__content">
    <div className="column xl-4 lg-5 md-12 s-menu__content-start">
      <div className="section-header" data-num="02">
        <h2 className="text-display-title">{t('menu.title')}</h2>
      </div>
      
      <nav className="tab-nav">
        <ul className="tab-nav__list">
          {popularCategories.map((category, index) => (
            <li key={category.id}>
              <a href={`#tab-${category.id}`}>
                <span>{category.name}</span>
                <svg>...</svg>
              </a>
            </li>
          ))}
          <li>
            <a href="/menu" className="view-all-link">
              <span>{t('menu.view_all')}</span>
              <svg>...</svg>
            </a>
          </li>
        </ul>
      </nav>
    </div>
    
    <div className="column xl-6 lg-6 md-12 s-menu__content-end">
      <div className="tab-content menu-block">
        {popularCategories.map(category => (
          <div key={category.id} id={`tab-${category.id}`} className="menu-block__group tab-content__item">
            <h6 className="menu-block__cat-name">{category.name}</h6>
            <ul className="menu-list">
              {category.products.slice(0, 5).map(product => (
                <li key={product.id} className="menu-list__item">
                  <div className="menu-list__item-desc">
                    <h4>{product.name}</h4>
                  </div>
                  <div className="menu-list__item-price">
                    <span>₫</span>{formatPrice(product.price)}
                  </div>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  </div>
</section>
```

### Секция Services
```jsx
<section id="services" className="container s-services target-section">
  <div className="row s-services__content">
    <div className="column xl-12">
      <div className="section-header" data-num="03">
        <h2 className="text-display-title">{t('services.title')}</h2>
      </div>
      
      <div className="services-grid">
        {services.map(service => (
          <Link key={service.id} to={service.link} className="service-card">
            <div className="service-card__icon">
              <img src={service.icon} alt={service.title} />
            </div>
            <h3 className="service-card__title">{service.title}</h3>
            <p className="service-card__description">{service.description}</p>
          </Link>
        ))}
      </div>
    </div>
  </div>
</section>
```

### Секция Events (Афиша)
```jsx
<section id="events" className="container s-events target-section">
  <div className="row s-events__content">
    <div className="column xl-12">
      <div className="section-header" data-num="04">
        <h2 className="text-display-title">{t('events.title')}</h2>
      </div>
      
      <div className="swiper-container events-slider">
        <div className="swiper-wrapper">
          {events.map((event, index) => (
            <div key={event.id} className="events-slider__slide swiper-slide">
              <Link to={`/events/${event.id}`} className="event-card">
                <div className="event-card__poster">
                  <img 
                    src={event.poster} 
                    alt={event.title} 
                    className="event-card__image"
                  />
                  <div className="event-card__overlay">
                    <div className="event-card__date">
                      {formatEventDate(event.date)}
                    </div>
                    <h3 className="event-card__title">{event.title}</h3>
                    <p className="event-card__description">{event.shortDescription}</p>
                  </div>
                </div>
              </Link>
            </div>
          ))}
        </div>
        <div className="swiper-pagination"></div>
        <div className="swiper-button-next"></div>
        <div className="swiper-button-prev"></div>
      </div>
      
      <div className="events-view-all">
        <Link to="/events" className="btn btn--primary">
          {t('events.view_calendar')} {/* "Смотреть календарь" */}
        </Link>
      </div>
    </div>
  </div>
</section>
```

### Секция Testimonials
```jsx
<section id="testimonials" className="container s-testimonials">
  <div className="row s-testimonials__content">
    <div className="column xl-12">
      <div className="section-header" data-num="05">
        <h2 className="text-display-title">{t('testimonials.title')}</h2>
      </div>
      
      <div className="swiper-container testimonials-slider">
        <div className="swiper-wrapper">
          {testimonials.map((testimonial, index) => (
            <div key={index} className="testimonials-slider__slide swiper-slide">
              <div className="testimonials-slider__author">
                <img 
                  src={testimonial.photo || '/img/avatar-placeholder.jpg'} 
                  alt={testimonial.author} 
                  className="testimonials-slider__avatar"
                />
                <cite className="testimonials-slider__cite">
                  {testimonial.author}
                </cite>
              </div>
              <p>{testimonial.text}</p>
            </div>
          ))}
        </div>
        <div className="swiper-pagination"></div>
      </div>
    </div>
  </div>
</section>
```

## 🗄️ Структура базы данных

### Коллекция `sections`
```javascript
{
  intro: {
    title: "Республика Север",
    subtitle: "Развлекательный комплекс с рестораном...",
    background_image: "/img/hero-bg.jpg",
    active: true
  },
  about: {
    title: "О нас",
    content: "<p>HTML контент из WYSIWYG редактора</p>",
    images: ["/img/about1.jpg", "/img/about2.jpg"],
    active: true
  },
  services: {
    items: [
      {
        id: "lasertag",
        title: "Лазертаг",
        description: "Командная игра",
        icon: "/img/lazertag/icon.png",
        link: "/lasertag",
        active: true,
        order: 1
      },
      {
        id: "archery",
        title: "Archery Tag", 
        description: "Лучный бой",
        icon: "/img/archery/icon.png",
        link: "/archerytag",
        active: true,
        order: 2
      },
      // ... остальные услуги
    ]
  },
  events: {
    title: "Афиша",
    description: "Будущие события",
    active: true
  },
  testimonials: {
    items: [
      {
        id: 1,
        author: "Anna",
        photo: "blob:https://web.telegram.org/...",
        text: "На сегодня это лучший кинотеатр под открытым небом💛...",
        active: true,
        order: 1
      }
      // ... остальные отзывы
    ]
  }
}
```

### Коллекция `events`
```javascript
{
  _id: ObjectId,
  title: "Название события",
  slug: "nazvanie-sobytiya",
  shortDescription: "Краткое описание для афиши",
  content: "<p>Полное описание события из WYSIWYG редактора</p>",
  poster: "/img/events/event-poster.jpg",
  date: "2025-02-15T18:00:00.000Z",
  location: "Республика Север",
  price: "1500 ₫",
  category: "concert", // concert, party, workshop, etc.
  status: "upcoming", // upcoming, ongoing, completed, cancelled
  active: true,
  createdAt: "2025-01-27T10:00:00.000Z",
  updatedAt: "2025-01-27T10:00:00.000Z"
}
```

## 🛠️ Админ панель

### Структура админки
```jsx
const AdminPanel = () => {
  const [activeSection, setActiveSection] = useState('intro');
  const [sections, setSections] = useState({});
  
  return (
    <div className="admin-panel">
      <div className="admin-sidebar">
        <h2>Управление сайтом</h2>
        <nav className="admin-nav">
          <button 
            className={activeSection === 'intro' ? 'active' : ''}
            onClick={() => setActiveSection('intro')}
          >
            Intro
          </button>
          <button 
            className={activeSection === 'about' ? 'active' : ''}
            onClick={() => setActiveSection('about')}
          >
            About
          </button>
          <button 
            className={activeSection === 'services' ? 'active' : ''}
            onClick={() => setActiveSection('services')}
          >
            Services
          </button>
          <button 
            className={activeSection === 'events' ? 'active' : ''}
            onClick={() => setActiveSection('events')}
          >
            Events
          </button>
          <button 
            className={activeSection === 'testimonials' ? 'active' : ''}
            onClick={() => setActiveSection('testimonials')}
          >
            Testimonials
          </button>
        </nav>
      </div>
      
      <div className="admin-content">
        <SectionEditor 
          section={activeSection}
          data={sections[activeSection]}
          onSave={handleSave}
        />
      </div>
    </div>
  );
};
```

### Компонент редактора секций
```jsx
const SectionEditor = ({ section, data, onSave }) => {
  const [content, setContent] = useState(data);
  const [isEditing, setIsEditing] = useState(false);
  
  return (
    <div className="section-editor">
      <div className="section-header">
        <h3>{getSectionTitle(section)}</h3>
        <div className="section-controls">
          <label className="toggle-switch">
            <input 
              type="checkbox" 
              checked={content.active}
              onChange={(e) => setContent({...content, active: e.target.checked})}
            />
            <span className="slider"></span>
            Активна
          </label>
          <button 
            className="btn btn--primary"
            onClick={() => setIsEditing(!isEditing)}
          >
            {isEditing ? 'Отменить' : 'Редактировать'}
          </button>
        </div>
      </div>
      
      {isEditing ? (
        <div className="editor-content">
          <WYSIWYGEditor 
            value={content.content}
            onChange={(newContent) => setContent({...content, content: newContent})}
          />
          <ImageUploader 
            images={content.images}
            onChange={(images) => setContent({...content, images})}
          />
          <div className="editor-actions">
            <button className="btn btn--primary" onClick={handleSave}>
              Сохранить
            </button>
            <button className="btn btn--secondary" onClick={() => setIsEditing(false)}>
              Отмена
            </button>
          </div>
        </div>
      ) : (
        <div className="section-preview">
          <div dangerouslySetInnerHTML={{ __html: content.content }} />
        </div>
      )}
    </div>
  );
};
```

## 📱 Адаптивный дизайн

### Breakpoints (из шаблона)
```css
/* Extra large devices */
@media screen and (min-width: 1200px) { /* xl */ }

/* Large devices */
@media screen and (min-width: 992px) { /* lg */ }

/* Medium devices */
@media screen and (min-width: 768px) { /* md */ }

/* Tablet devices */
@media screen and (min-width: 640px) { /* tablet */ }

/* Mobile devices */
@media screen and (max-width: 639px) { /* mobile */ }

/* Small mobile devices */
@media screen and (max-width: 480px) { /* small-mobile */ }
```

### Grid система
```css
/* Grid columns из шаблона */
.grid-cols-1 { grid-template-columns: repeat(1, 1fr); }
.grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
.grid-cols-5 { grid-template-columns: repeat(5, 1fr); }
.grid-cols-6 { grid-template-columns: repeat(6, 1fr); }
.grid-cols-12 { grid-template-columns: repeat(12, 1fr); }
```

## 🎯 Функциональные требования

### Сохранение текущего функционала
- ✅ **Telegram авторизация** - остается без изменений
- ✅ **Корзина** - модальное окно в стиле шаблона
- ✅ **Заказы** - функционал "Мои заказы" + редактирование профиля
- ✅ **Мультиязычность** - RU/EN/VI
- ✅ **Fast Access** - отдельная страница с наследованием стилей
- ✅ **URL структура** - `/fast/:tableId` сохраняется

### Новые функции
- ✅ **WYSIWYG редактор** - для контента секций
- ✅ **Загрузчик изображений** - встроенный в админку
- ✅ **Управление секциями** - включение/выключение
- ✅ **Карусель отзывов** - 10 клонов с автопрокруткой
- ✅ **Календарь событий** - отдельная страница с календарным видом
- ✅ **SEO оптимизация** - метатеги и структурированные данные

## 🚀 Техническая реализация

### Интеграция стилей шаблона
```javascript
// tailwind.config.js
module.exports = {
  content: ["./src/**/*.{js,jsx,ts,tsx}"],
  theme: {
    extend: {
      colors: {
        // Цвета из шаблона Lounge
        primary: {
          50: '#f4f9f7',
          100: '#daede5',
          500: '#468672',
          600: '#366b5b',
          900: '#253c35',
        },
        secondary: {
          50: '#f9f7f3',
          100: '#f2ede2',
          500: '#b1885e',
          600: '#a47652',
          900: '#5a4134',
        },
        neutral: {
          50: '#efefef',
          500: '#5f6362',
          900: '#131414',
          950: '#090a0a',
        }
      },
      fontFamily: {
        'sans': ['"Roboto Flex"', 'sans-serif'],
        'serif': ['"Playfair Display"', 'serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
      }
    }
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ]
}
```

### Компоненты для переиспользования
```jsx
// components/ui/SectionHeader.jsx
export const SectionHeader = ({ number, title, className = '' }) => (
  <div className={`section-header ${className}`} data-num={number}>
    <h2 className="text-display-title">{title}</h2>
  </div>
);

// components/ui/TabNavigation.jsx
export const TabNavigation = ({ tabs, activeTab, onTabChange }) => (
  <nav className="tab-nav">
    <ul className="tab-nav__list">
      {tabs.map(tab => (
        <li key={tab.id}>
          <a 
            href={`#${tab.id}`}
            className={activeTab === tab.id ? 'active' : ''}
            onClick={() => onTabChange(tab.id)}
          >
            <span>{tab.title}</span>
            <svg>...</svg>
          </a>
        </li>
      ))}
    </ul>
  </nav>
);

// components/ui/MenuList.jsx
export const MenuList = ({ items }) => (
  <ul className="menu-list">
    {items.map(item => (
      <li key={item.id} className="menu-list__item">
        <div className="menu-list__item-desc">
          <h4>{item.name}</h4>
          {item.description && <p>{item.description}</p>}
        </div>
        <div className="menu-list__item-price">
          <span>₫</span>{formatPrice(item.price)}
        </div>
      </li>
    ))}
  </ul>
);
```

## 🎨 Система стилей

### CSS переменные (без дублирования)
```css
/* styles/globals.css */
:root {
  /* Используем только токены из designTokens.js */
  --color-primary-50: #f4f9f7;
  --color-primary-500: #468672;
  --color-primary-900: #253c35;
  
  --color-secondary-50: #f9f7f3;
  --color-secondary-500: #b1885e;
  --color-secondary-900: #5a4134;
  
  --font-sans: "Roboto Flex", sans-serif;
  --font-serif: "Playfair Display", serif;
  
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
}
```

### Утилиты для стилей
```javascript
// utils/styles.js
import { DESIGN_TOKENS } from '../constants/designTokens';

export const getColorClass = (color, shade = 500) => {
  return `text-${color}-${shade}`;
};

export const getSpacingClass = (size) => {
  const spacingMap = {
    xs: 'space-y-1',
    sm: 'space-y-2', 
    md: 'space-y-4',
    lg: 'space-y-6',
    xl: 'space-y-8'
  };
  return spacingMap[size] || spacingMap.md;
};

export const getResponsiveClass = (base, responsive) => {
  return `${base} ${Object.entries(responsive)
    .map(([breakpoint, value]) => `${breakpoint}:${value}`)
    .join(' ')}`;
};
```

## 🏗️ Архитектура страниц

### Главная страница (композиция компонентов)
```jsx
// pages/HomePage.jsx
import { SectionWrapper } from '../components/sections/SectionWrapper';
import { IntroSection } from '../components/sections/IntroSection';
import { AboutSection } from '../components/sections/AboutSection';
import { MenuPreviewSection } from '../components/sections/MenuPreviewSection';
import { ServicesSection } from '../components/sections/ServicesSection';
import { EventsSection } from '../components/sections/EventsSection';
import { TestimonialsSection } from '../components/sections/TestimonialsSection';

export const HomePage = () => {
  return (
    <div className="s-pagewrap ss-home">
      <IntroSection />
      <AboutSection />
      <MenuPreviewSection />
      <ServicesSection />
      <EventsSection />
      <TestimonialsSection />
    </div>
  );
};
```

### Страница календаря событий
```jsx
// pages/EventsPage.jsx
import { useState, useEffect } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { useEvents } from '../hooks/useEvents';
import { Calendar } from '../components/events/Calendar';
import { EventList } from '../components/events/EventList';
import { EventFilters } from '../components/events/EventFilters';

export const EventsPage = () => {
  const { t } = useTranslation();
  const { events, loading, error } = useEvents();
  const [viewMode, setViewMode] = useState('calendar'); // 'calendar' | 'list'
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [filters, setFilters] = useState({
    category: 'all',
    status: 'upcoming'
  });

  const filteredEvents = events.filter(event => {
    if (filters.category !== 'all' && event.category !== filters.category) return false;
    if (filters.status !== 'all' && event.status !== filters.status) return false;
    return true;
  });

  return (
    <div className="events-page">
      <div className="container">
        <div className="page-header">
          <h1 className="page-title">{t('events.calendar_title')}</h1>
          <p className="page-subtitle">{t('events.calendar_subtitle')}</p>
        </div>

        <div className="events-controls">
          <div className="view-toggle">
            <button 
              className={viewMode === 'calendar' ? 'active' : ''}
              onClick={() => setViewMode('calendar')}
            >
              {t('events.calendar_view')}
            </button>
            <button 
              className={viewMode === 'list' ? 'active' : ''}
              onClick={() => setViewMode('list')}
            >
              {t('events.list_view')}
            </button>
          </div>
          
          <EventFilters 
            filters={filters}
            onFiltersChange={setFilters}
          />
        </div>

        {viewMode === 'calendar' ? (
          <Calendar 
            events={filteredEvents}
            selectedDate={selectedDate}
            onDateSelect={setSelectedDate}
          />
        ) : (
          <EventList 
            events={filteredEvents}
            selectedDate={selectedDate}
          />
        )}
      </div>
    </div>
  );
};
```

### Секции (самодостаточные компоненты)
```jsx
// components/sections/IntroSection.jsx
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const IntroSection = () => {
  const { t } = useTranslation();
  const { introContent } = useSiteContent();
  
  return (
    <SectionWrapper id="intro" className="s-intro">
      <div className="grid-block s-intro__content">
        <div className="intro-header">
          <div className="intro-header__overline">
            {t('intro.welcome')}
          </div>
          <h1 className="intro-header__big-type">
            {introContent.title}
          </h1>
        </div>
        
        <figure className="intro-pic-primary">
          <img src={introContent.background_image} alt="North Republic" />
        </figure>
        
        <div className="intro-block-content">
          <div className="intro-block-content__text-wrap">
            <p className="intro-block-content__text">
              {introContent.subtitle}
            </p>
          </div>
        </div>
      </div>
    </SectionWrapper>
  );
};
```

## 🎣 Система хуков

### Хуки для данных (без дублирования логики)
```javascript
// hooks/useSiteContent.js
import { useState, useEffect } from 'react';
import { apiService } from '../services/apiService';

export const useSiteContent = () => {
  const [content, setContent] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchContent = async () => {
      try {
        const data = await apiService.get('/api/sections');
        setContent(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchContent();
  }, []);

  return { content, loading, error };
};

// hooks/useMenuData.js
import { useState, useEffect } from 'react';
import { menuService } from '../services/menuService';

export const useMenuData = () => {
  const [menuData, setMenuData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchMenuData = async () => {
      try {
        const data = await menuService.getMenuData();
        setMenuData(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchMenuData();
  }, []);

  return { menuData, loading, error };
};

// hooks/useEvents.js
import { useState, useEffect } from 'react';
import { apiService } from '../services/apiService';

export const useEvents = () => {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchEvents = async () => {
      try {
        const data = await apiService.get('/api/events');
        setEvents(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchEvents();
  }, []);

  return { events, loading, error };
};
```

## 🛠️ Утилиты (без дублирования)

### Единые утилиты для форматирования
```javascript
// utils/formatters.js
export const formatPrice = (price) => {
  if (!price && price !== 0) return 'Цена не указана';
  
  const numPrice = typeof price === 'object' ? 
    getMainPrice(price) : parseFloat(price);
  
  if (isNaN(numPrice)) return 'Цена не указана';
  
  return `${numPrice.toLocaleString('vi-VN')} ₫`;
};

export const getMainPrice = (priceObject) => {
  if (!priceObject) return null;
  return priceObject['1'] || Object.values(priceObject)[0];
};

export const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU');
};

export const formatEventDate = (date) => {
  const eventDate = new Date(date);
  const now = new Date();
  const diffTime = eventDate - now;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays < 0) {
    return eventDate.toLocaleDateString('ru-RU', { 
      day: 'numeric', 
      month: 'long', 
      year: 'numeric' 
    });
  } else if (diffDays === 0) {
    return 'Сегодня';
  } else if (diffDays === 1) {
    return 'Завтра';
  } else if (diffDays <= 7) {
    return `Через ${diffDays} дней`;
  } else {
    return eventDate.toLocaleDateString('ru-RU', { 
      day: 'numeric', 
      month: 'long' 
    });
  }
};

export const formatPhone = (phone) => {
  return phone.replace(/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/, '+$1 ($2) $3-$4-$5');
};
```

### Единые утилиты для валидации
```javascript
// utils/validators.js
export const validateEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
};

export const validatePhone = (phone) => {
  const digits = phone.replace(/\D/g, '');
  return digits.length >= 10 && digits.length <= 15;
};

export const validateRequired = (value) => {
  return value && value.trim().length > 0;
};
```

## 📊 SEO и производительность

### Метатеги
```jsx
// components/SEO.jsx
export const SEO = ({ title, description, image, url }) => (
  <Helmet>
    <title>{title} | North Republic</title>
    <meta name="description" content={description} />
    
    {/* Open Graph */}
    <meta property="og:title" content={title} />
    <meta property="og:description" content={description} />
    <meta property="og:image" content={image} />
    <meta property="og:url" content={url} />
    
    {/* Twitter */}
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content={title} />
    <meta name="twitter:description" content={description} />
    <meta name="twitter:image" content={image} />
  </Helmet>
);
```

### Структурированные данные
```jsx
// components/StructuredData.jsx
export const RestaurantSchema = () => {
  const schema = {
    "@context": "https://schema.org",
    "@type": "Restaurant",
    "name": "Республика Север",
    "description": "Развлекательный комплекс с рестораном",
    "address": {
      "@type": "PostalAddress",
      "addressCountry": "VN"
    },
    "telephone": "+84-xxx-xxx-xxxx",
    "servesCuisine": ["Vietnamese", "International"],
    "priceRange": "$$"
  };
  
  return (
    <script type="application/ld+json">
      {JSON.stringify(schema)}
    </script>
  );
};
```

## 📁 Структура файлов (предотвращение дублирования)

```
src/
├── components/
│   ├── ui/                    # Базовые компоненты (не переопределяются)
│   │   ├── BaseButton.jsx
│   │   ├── BaseInput.jsx
│   │   ├── BaseCard.jsx
│   │   └── index.js
│   ├── sections/              # Секции страниц
│   │   ├── IntroSection.jsx
│   │   ├── AboutSection.jsx
│   │   ├── MenuPreviewSection.jsx
│   │   ├── EventsSection.jsx
│   │   └── index.js
│   ├── events/                # Компоненты событий
│   │   ├── Calendar.jsx
│   │   ├── EventList.jsx
│   │   ├── EventFilters.jsx
│   │   ├── EventCard.jsx
│   │   └── index.js
│   ├── layout/                # Компоненты макета
│   │   ├── Header.jsx
│   │   ├── Footer.jsx
│   │   └── Navigation.jsx
│   └── features/              # Функциональные компоненты
│       ├── cart/
│       ├── auth/
│       └── admin/
├── pages/                     # Страницы приложения
│   ├── HomePage.jsx
│   ├── EventsPage.jsx
│   ├── EventDetailPage.jsx
│   └── index.js
├── hooks/                     # Переиспользуемые хуки
│   ├── useSiteContent.js
│   ├── useMenuData.js
│   ├── useEvents.js
│   └── useLocalStorage.js
├── utils/                     # Утилиты (без дублирования)
│   ├── formatters.js
│   ├── validators.js
│   └── styles.js
├── constants/                 # Константы
│   ├── designTokens.js
│   ├── apiEndpoints.js
│   └── routes.js
├── services/                  # API сервисы
│   ├── apiService.js
│   ├── menuService.js
│   ├── eventsService.js
│   └── authService.js
└── styles/                    # Глобальные стили
    ├── globals.css
    └── components.css
```

## 🚀 Этапы реализации

### Phase 1: Основы (1-2 недели)
1. Интеграция стилей шаблона в Tailwind
2. Создание базовых компонентов
3. Структура главной страницы
4. Настройка роутинга

### Phase 2: Контент (1-2 недели)
1. Интеграция с БД для контента
2. WYSIWYG редактор
3. Загрузчик изображений
4. Админ панель

### Phase 3: Функциональность (1-2 недели)
1. Карусель отзывов
2. Календарь событий
3. Адаптация существующих страниц
4. SEO оптимизация
5. Тестирование

### Phase 4: Полировка (1 неделя)
1. Анимации и переходы
2. Оптимизация производительности
3. Тестирование на устройствах
4. Документация

## ✅ Критерии приемки

### Функциональные
- [ ] Все секции отображаются корректно
- [ ] Админ панель работает для всех разделов
- [ ] WYSIWYG редактор функционирует
- [ ] Карусель отзывов работает с автопрокруткой
- [ ] Календарь событий работает корректно
- [ ] Навигация включает ссылку на календарь событий
- [ ] Сохранен весь существующий функционал

### Дизайн
- [ ] Стили соответствуют шаблону Lounge
- [ ] Адаптивность на всех устройствах
- [ ] Анимации и переходы работают
- [ ] Цветовая схема применена корректно

### Технические
- [ ] SEO метатеги настроены
- [ ] Структурированные данные добавлены
- [ ] Lazy loading для изображений
- [ ] Производительность оптимизирована

### Архитектурные
- [ ] Нет дублирования кода
- [ ] Компоненты самодостаточны
- [ ] Единый источник истины для стилей
- [ ] Легкость дебага и поддержки

---

**Дата создания**: 2025-01-27  
**Версия**: 1.0  
**Статус**: Утверждено

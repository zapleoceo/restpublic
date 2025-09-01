import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import Backend from 'i18next-http-backend';

// Локальные переводы (fallback)
const resources = {
  ru: {
    translation: {
      nav: {
        home: 'Главная',
        about: 'О нас',
        menu: 'Меню',
        services: 'Услуги',
        events: 'События',
        events_calendar: 'Календарь',
        full_menu: 'Полное меню',
      },
      intro: {
        welcome: 'Добро пожаловать в',
        title: 'Республика Север',
        subtitle: 'Развлекательный комплекс с рестораном, кинотеатром под открытым небом и множеством активностей для всей семьи.',
      },
      about: {
        title: 'О нас',
      },
      menu: {
        title: 'Наше меню',
        view_all: 'Смотреть все',
      },
      services: {
        title: 'Наши услуги',
      },
      events: {
        title: 'Афиша',
        view_calendar: 'Смотреть календарь',
        calendar_title: 'Календарь событий',
        calendar_subtitle: 'Все предстоящие события в Республике Север',
        calendar_view: 'Календарь',
        list_view: 'Список',
      },
      testimonials: {
        title: 'Отзывы гостей',
      },
      common: {
        loading: 'Загрузка...',
        error: 'Произошла ошибка',
        save: 'Сохранить',
        cancel: 'Отмена',
        edit: 'Редактировать',
        delete: 'Удалить',
        active: 'Активна',
        inactive: 'Неактивна',
      },
    },
  },
  en: {
    translation: {
      nav: {
        home: 'Home',
        about: 'About',
        menu: 'Menu',
        services: 'Services',
        events: 'Events',
        events_calendar: 'Calendar',
        full_menu: 'Full Menu',
      },
      intro: {
        welcome: 'Welcome to',
        title: 'North Republic',
        subtitle: 'Entertainment complex with restaurant, open-air cinema and many activities for the whole family.',
      },
      about: {
        title: 'About Us',
      },
      menu: {
        title: 'Our Menu',
        view_all: 'View All',
      },
      services: {
        title: 'Our Services',
      },
      events: {
        title: 'Events',
        view_calendar: 'View Calendar',
        calendar_title: 'Events Calendar',
        calendar_subtitle: 'All upcoming events at North Republic',
        calendar_view: 'Calendar',
        list_view: 'List',
      },
      testimonials: {
        title: 'Guest Reviews',
      },
      common: {
        loading: 'Loading...',
        error: 'An error occurred',
        save: 'Save',
        cancel: 'Cancel',
        edit: 'Edit',
        delete: 'Delete',
        active: 'Active',
        inactive: 'Inactive',
      },
    },
  },
  vi: {
    translation: {
      nav: {
        home: 'Trang chủ',
        about: 'Về chúng tôi',
        menu: 'Thực đơn',
        services: 'Dịch vụ',
        events: 'Sự kiện',
        events_calendar: 'Lịch',
        full_menu: 'Thực đơn đầy đủ',
      },
      intro: {
        welcome: 'Chào mừng đến',
        title: 'Cộng hòa Bắc',
        subtitle: 'Khu phức hợp giải trí với nhà hàng, rạp chiếu phim ngoài trời và nhiều hoạt động cho cả gia đình.',
      },
      about: {
        title: 'Về chúng tôi',
      },
      menu: {
        title: 'Thực đơn của chúng tôi',
        view_all: 'Xem tất cả',
      },
      services: {
        title: 'Dịch vụ của chúng tôi',
      },
      events: {
        title: 'Sự kiện',
        view_calendar: 'Xem lịch',
        calendar_title: 'Lịch sự kiện',
        calendar_subtitle: 'Tất cả sự kiện sắp tới tại Cộng hòa Bắc',
        calendar_view: 'Lịch',
        list_view: 'Danh sách',
      },
      testimonials: {
        title: 'Đánh giá của khách',
      },
      common: {
        loading: 'Đang tải...',
        error: 'Đã xảy ra lỗi',
        save: 'Lưu',
        cancel: 'Hủy',
        edit: 'Chỉnh sửa',
        delete: 'Xóa',
        active: 'Hoạt động',
        inactive: 'Không hoạt động',
      },
    },
  },
};

i18n
  .use(Backend)
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: 'ru',
    debug: import.meta.env.DEV,
    
    interpolation: {
      escapeValue: false,
    },
    
    detection: {
      order: ['localStorage', 'navigator'],
      caches: ['localStorage'],
    },
    
    backend: {
      loadPath: '/locales/{{lng}}/{{ns}}.json',
    },
  });

export default i18n;

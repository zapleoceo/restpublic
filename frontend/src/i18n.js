import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import Backend from 'i18next-http-backend';

const resources = {
  ru: {
    translation: {
      'nav.home': 'Главная',
      'nav.menu': 'Меню',
      'nav.events': 'События',
      'nav.about': 'О нас',
      'nav.contact': 'Контакты',
      'section.intro.title': 'Добро пожаловать в North Republic',
      'section.intro.subtitle': 'Уникальное место для отдыха и развлечений',
      'section.about.title': 'О нас',
      'section.services.title': 'Услуги',
      'section.events.title': 'События',
      'section.testimonials.title': 'Отзывы',
      'button.learn_more': 'Узнать больше',
      'button.book_now': 'Забронировать',
      'language.ru': 'Русский',
      'language.en': 'English',
      'language.vi': 'Tiếng Việt',
    }
  },
  en: {
    translation: {
      'nav.home': 'Home',
      'nav.menu': 'Menu',
      'nav.events': 'Events',
      'nav.about': 'About',
      'nav.contact': 'Contact',
      'section.intro.title': 'Welcome to North Republic',
      'section.intro.subtitle': 'A unique place for rest and entertainment',
      'section.about.title': 'About Us',
      'section.services.title': 'Services',
      'section.events.title': 'Events',
      'section.testimonials.title': 'Testimonials',
      'button.learn_more': 'Learn More',
      'button.book_now': 'Book Now',
      'language.ru': 'Русский',
      'language.en': 'English',
      'language.vi': 'Tiếng Việt',
    }
  },
  vi: {
    translation: {
      'nav.home': 'Trang chủ',
      'nav.menu': 'Thực đơn',
      'nav.events': 'Sự kiện',
      'nav.about': 'Về chúng tôi',
      'nav.contact': 'Liên hệ',
      'section.intro.title': 'Chào mừng đến với North Republic',
      'section.intro.subtitle': 'Nơi độc đáo để nghỉ ngơi và giải trí',
      'section.about.title': 'Về chúng tôi',
      'section.services.title': 'Dịch vụ',
      'section.events.title': 'Sự kiện',
      'section.testimonials.title': 'Đánh giá',
      'button.learn_more': 'Tìm hiểu thêm',
      'button.book_now': 'Đặt ngay',
      'language.ru': 'Русский',
      'language.en': 'English',
      'language.vi': 'Tiếng Việt',
    }
  }
};

i18n
  .use(Backend)
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: 'ru',
    debug: false,
    interpolation: {
      escapeValue: false,
    },
    detection: {
      order: ['localStorage', 'navigator'],
      caches: ['localStorage'],
    },
  });

export default i18n;

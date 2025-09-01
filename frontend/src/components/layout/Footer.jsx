import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from '../../hooks/useTranslation';

export const Footer = () => {
  const { t } = useTranslation();
  const currentYear = new Date().getFullYear();

  return (
    <footer className="footer bg-primary-900 text-white">
      <div className="container mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* О нас */}
          <div className="footer__about">
            <h3 className="text-xl font-serif font-bold mb-4">
              North Republic
            </h3>
            <p className="text-primary-100 leading-relaxed mb-4">
              Развлекательный комплекс с рестораном, где каждый найдет что-то для себя.
            </p>
            <div className="flex space-x-4">
              <a 
                href="#" 
                className="text-primary-100 hover:text-white transition-colors"
                aria-label="Telegram"
              >
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.692-1.653-1.123-2.678-1.799-1.185-.781-.417-1.21.258-1.911.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.48.329-.913.489-1.302.481-.428-.008-1.252-.241-1.865-.44-.752-.244-1.349-.374-1.297-.789.027-.216.324-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635.099-.002.321.023.465.178.164.172.213.421.227.592.007.096-.001.234-.001.234z"/>
                </svg>
              </a>
              <a 
                href="#" 
                className="text-primary-100 hover:text-white transition-colors"
                aria-label="Instagram"
              >
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
            </div>
          </div>

          {/* Быстрые ссылки */}
          <div className="footer__links">
            <h3 className="text-lg font-serif font-bold mb-4">
              {t('footer.quick_links') || "Быстрые ссылки"}
            </h3>
            <ul className="space-y-2">
              <li>
                <a 
                  href="#intro" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  {t('nav.home') || "Главная"}
                </a>
              </li>
              <li>
                <a 
                  href="#about" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  {t('nav.about') || "О нас"}
                </a>
              </li>
              <li>
                <Link 
                  to="/menu" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  {t('nav.full_menu') || "Полное меню"}
                </Link>
              </li>
              <li>
                <Link 
                  to="/events" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  {t('nav.events_calendar') || "Календарь"}
                </Link>
              </li>
            </ul>
          </div>

          {/* Услуги */}
          <div className="footer__services">
            <h3 className="text-lg font-serif font-bold mb-4">
              {t('nav.services') || "Услуги"}
            </h3>
            <ul className="space-y-2">
              <li>
                <Link 
                  to="/lasertag" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  Лазертаг
                </Link>
              </li>
              <li>
                <Link 
                  to="/archerytag" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  Archery Tag
                </Link>
              </li>
              <li>
                <Link 
                  to="/cinema" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  Кинотеатр
                </Link>
              </li>
              <li>
                <Link 
                  to="/bbq_zone" 
                  className="text-primary-100 hover:text-white transition-colors"
                >
                  BBQ зона
                </Link>
              </li>
            </ul>
          </div>

          {/* Контакты */}
          <div className="footer__contact">
            <h3 className="text-lg font-serif font-bold mb-4">
              {t('footer.contact') || "Контакты"}
            </h3>
            <div className="space-y-2 text-primary-100">
              <p className="flex items-center space-x-2">
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                </svg>
                <span>Вьетнам, Хошимин</span>
              </p>
              <p className="flex items-center space-x-2">
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                <span>info@northrepublic.me</span>
              </p>
              <p className="flex items-center space-x-2">
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                </svg>
                <span>+84 123 456 789</span>
              </p>
            </div>
          </div>
        </div>

        {/* Нижняя часть */}
        <div className="footer__bottom border-t border-primary-800 mt-8 pt-8 text-center">
          <p className="text-primary-100">
            © {currentYear} North Republic. {t('footer.rights') || "Все права защищены."}
          </p>
        </div>
      </div>
    </footer>
  );
};

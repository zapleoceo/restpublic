import React from 'react';
import { Link } from 'react-router-dom';
import './MenuFooter.css';

const MenuFooter = () => {
  return (
    <footer className="menu-footer">
      <div className="container">
        <div className="menu-footer__content">
          <div className="menu-footer__main">
            <div className="menu-footer__block">
              <ul className="menu-footer__social social-list">
                <li>
                  <a 
                    href="https://facebook.com/vngamezone" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Facebook"
                  >
                    FB
                  </a>
                </li>
                <li>
                  <a 
                    href="https://www.instagram.com/gamezone.vn/" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Instagram"
                  >
                    IG
                  </a>
                </li>
                <li>
                  <a 
                    href="https://www.tiktok.com/@gamezone.vn" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="TikTok"
                  >
                    TT
                  </a>
                </li>
                <li>
                  <a 
                    href="https://t.me/gamezone_vietnam" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    aria-label="Telegram"
                  >
                    TG
                  </a>
                </li>
              </ul>
            </div>

            <div className="menu-footer__block">
              <h4 className="menu-footer__title">Контакты</h4>
              <div className="menu-footer__contact">
                <a href="tel:+84349338758" className="menu-footer__phone">
                  +84 349 338 758
                </a>
                <p className="menu-footer__address">
                  Хошимин, Вьетнам
                </p>
              </div>
            </div>

            <div className="menu-footer__block">
              <h4 className="menu-footer__title">Навигация</h4>
              <ul className="menu-footer__links">
                <li>
                  <Link to="/" className="menu-footer__link">
                    Главная
                  </Link>
                </li>
                <li>
                  <Link to="/#about" className="menu-footer__link">
                    О нас
                  </Link>
                </li>
                <li>
                  <Link to="/#gallery" className="menu-footer__link">
                    Галерея
                  </Link>
                </li>
                <li>
                  <Link to="/menu" className="menu-footer__link">
                    Меню
                  </Link>
                </li>
              </ul>
            </div>
          </div>

          <div className="menu-footer__bottom">
            <p className="menu-footer__copyright">
              © 2024 North Republic. Все права защищены.
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default MenuFooter;

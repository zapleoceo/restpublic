import React from 'react';
import { useTranslation } from 'react-i18next';

const ContactSection = () => {
  const { t } = useTranslation();

  return (
    <div className="bg-gray-50 py-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Заголовок */}
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold text-gray-900 mb-4">{t('home.contacts')}</h2>
          <p className="text-lg text-gray-600 max-w-2xl mx-auto">
            {t('home.contacts_subtitle')}
          </p>
        </div>

        {/* Карта */}
        <div className="mb-12">
          <div className="bg-white rounded-xl shadow-md overflow-hidden">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3898.302584!2d109.207279!3d12.302584!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTLCsDE4JzA5LjMiTiAxMDnCsDEyJzI2LjIiRQ!5e0!3m2!1sru!2sru!4v1234567890"
              width="100%"
              height="400"
              style={{ border: 0 }}
              allowFullScreen=""
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="GoodZone Location"
              className="w-full"
            ></iframe>
          </div>
        </div>

                 {/* Иконки контактов и социальных сетей */}
         <div className="flex justify-center space-x-6 mb-8">
           {/* Телефон */}
           <a
             href="tel:+84349338758"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-orange-200 transition-colors">
               <i className="fas fa-phone text-orange-600 text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">+84 349 338 758</span>
             <span className="text-xs text-gray-500 mt-1">{t('home.phone')}</span>
           </a>

           {/* Локация */}
           <a
             href="https://maps.app.goo.gl/Hgbn5n83PA11NcqLA"
             target="_blank"
             rel="noopener noreferrer"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-orange-200 transition-colors">
               <i className="fas fa-map-marker-alt text-orange-600 text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">{t('home.location')}</span>
             <span className="text-xs text-gray-500 mt-1">Trần Khát Chân, Nha Trang</span>
           </a>

           {/* Telegram */}
           <a
             href="https://t.me/goodzone_vn"
             target="_blank"
             rel="noopener noreferrer"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-orange-200 transition-colors">
               <i className="fab fa-telegram text-orange-600 text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">{t('home.group')}</span>
             <span className="text-xs text-gray-500 mt-1">@goodzone_vn</span>
           </a>

           {/* Instagram */}
           <a
             href="https://www.instagram.com/gamezone_vietnam/"
             target="_blank"
             rel="noopener noreferrer"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mb-4 group-hover:bg-gradient-to-r group-hover:from-purple-600 group-hover:to-pink-600 transition-colors">
               <i className="fab fa-instagram text-white text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">Instagram</span>
             <span className="text-xs text-gray-500 mt-1">@gamezone_vietnam</span>
           </a>

           {/* TikTok */}
           <a
             href="https://www.tiktok.com/@gamezone_vietnam"
             target="_blank"
             rel="noopener noreferrer"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-black rounded-full flex items-center justify-center mb-4 group-hover:bg-gray-800 transition-colors">
               <i className="fab fa-tiktok text-white text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">TikTok</span>
             <span className="text-xs text-gray-500 mt-1">@gamezone_vietnam</span>
           </a>

           {/* Facebook */}
           <a
             href="https://www.facebook.com/gamezone.vietnam"
             target="_blank"
             rel="noopener noreferrer"
             className="flex flex-col items-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 group"
           >
             <div className="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-700 transition-colors">
               <i className="fab fa-facebook-f text-white text-xl"></i>
             </div>
             <span className="text-sm font-medium text-gray-900">Facebook</span>
             <span className="text-xs text-gray-500 mt-1">gamezone.vietnam</span>
           </a>
         </div>

                 {/* Адрес */}
         <div className="text-center mt-8">
           <p className="text-gray-600">
             Trần Khát Chân, Đường Đệ, Nha Trang, Khánh Hòa, Vietnam
           </p>
         </div>
      </div>
    </div>
  );
};

export default ContactSection;

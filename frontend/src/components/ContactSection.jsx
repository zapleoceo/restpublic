import React from 'react';
import { useTranslation } from 'react-i18next';

const ContactSection = () => {
  const { t } = useTranslation();

  return (
         <div className="bg-gray-50 py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                 {/* Карта */}
         <div className="mb-8">
          <div className="bg-white rounded-xl shadow-md overflow-hidden">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3898.302584!2d109.207279!3d12.302584!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTLCsDE4JzA5LjMiTiAxMDnCsDEyJzI2LjIiRQ!5e0!3m2!1sru!2sru!4v1234567890"
              width="100%"
              height="400"
              style={{ border: 0 }}
              allowFullScreen=""
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="North Republic Location"
              className="w-full"
            ></iframe>
          </div>
        </div>

        

                            {/* Мы на связи */}
         <div className="text-center mb-6">
           <h3 className="text-xl font-semibold text-gray-900 mb-4">{t('home.we_are_in_touch')}</h3>
           <div className="flex justify-center space-x-4">
             {/* Telegram */}
             <a
               href="https://t.me/goodzone_vn"
               target="_blank"
               rel="noopener noreferrer"
               className="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-200 shadow-lg hover:shadow-xl"
             >
               <i className="fab fa-telegram text-white text-xl"></i>
             </a>

             {/* Instagram */}
             <a
               href="https://www.instagram.com/gamezone_vietnam/"
               target="_blank"
               rel="noopener noreferrer"
               className="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-200 shadow-lg hover:shadow-xl"
             >
               <i className="fab fa-instagram text-white text-xl"></i>
             </a>

             {/* TikTok */}
             <a
               href="https://www.tiktok.com/@gamezone_vietnam"
               target="_blank"
               rel="noopener noreferrer"
               className="w-12 h-12 bg-black rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-200 shadow-lg hover:shadow-xl"
             >
               <i className="fab fa-tiktok text-white text-xl"></i>
             </a>

             {/* Facebook */}
             <a
               href="https://www.facebook.com/gamezone.vietnam"
               target="_blank"
               rel="noopener noreferrer"
               className="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-200 shadow-lg hover:shadow-xl"
             >
               <i className="fab fa-facebook-f text-white text-xl"></i>
             </a>
           </div>
         </div>

                 {/* Адрес */}
         <div className="text-center mt-6 pb-7">
           <a 
             href="https://www.google.com/maps/search/Tr%E1%BA%A7n+Kh%E1%A3t+Ch%C3%A2n,+%C4%90%C6%B0%E1%BB%9Dng+%C4%90%E1%BB%87,+Nha+Trang,+Kh%C3%A1nh+H%C3%B2a,+Vietnam" 
             target="_blank" 
             rel="noopener noreferrer"
             className="text-gray-600 hover:text-blue-600 hover:underline transition-colors"
           >
             Trần Khát Chân, Đường Đệ, Nha Trang, Khánh Hòa, Vietnam
           </a>
           <p className="text-gray-600 mt-2">
             <a 
               href="tel:+84349338758"
               className="hover:text-blue-600 hover:underline transition-colors"
             >
               +84 349 338 758
             </a>
           </p>
         </div>
      </div>
    </div>
  );
};

export default ContactSection;

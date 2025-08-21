import React from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';

const HomePage = () => {
  const { t } = useTranslation();

  const sections = [
    {
      id: 'menu',
      title: 'Меню',
      icon: '🍽️',
      link: '/m',
      description: 'Ресторанное меню'
    },
    {
      id: 'lasertag',
      title: 'Лазертаг',
      icon: '🎯',
      link: '/lt',
      description: 'Командная игра'
    },
    {
      id: 'bow',
      title: 'Стрельба из лука',
      icon: '🏹',
      link: '/bow',
      description: 'Традиционная стрельба'
    },
    {
      id: 'cinema',
      title: 'Кинотеатр',
      icon: '🎬',
      link: '/cinema',
      description: 'Просмотр фильмов'
    },
    {
      id: 'rent',
      title: 'Аренда беседки',
      icon: '🏕️',
      link: '/rent',
      description: 'Аренда беседок'
    }
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-center h-16">
            <h1 className="text-2xl font-bold text-gray-900">GoodZone</h1>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Welcome Section */}
        <div className="text-center mb-12">
          <div className="text-6xl mb-4">🎯</div>
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            Добро пожаловать в GoodZone
          </h2>
          <p className="text-lg text-gray-600 max-w-2xl mx-auto">
            Развлекательный комплекс с рестораном, лазертагом, стрельбой из лука, кинотеатром и арендой беседок
          </p>
        </div>

        {/* Services Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto">
          {sections.map((section) => (
            <Link
              key={section.id}
              to={section.link}
              className="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 p-6 text-center group"
            >
              <div className="text-4xl mb-4 group-hover:scale-110 transition-transform duration-200">
                {section.icon}
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                {section.title}
              </h3>
              <p className="text-gray-600 text-sm">
                {section.description}
              </p>
            </Link>
          ))}
        </div>

        {/* Contact Section */}
        <div className="mt-16 text-center">
          <div className="bg-white rounded-xl shadow-md p-8 max-w-2xl mx-auto">
            <h3 className="text-2xl font-bold text-gray-900 mb-4">Контакты</h3>
            <div className="space-y-3 text-gray-600">
              <p>📍 <a href="https://maps.app.goo.gl/Hgbn5n83PA11NcqLA" target="_blank" rel="noopener noreferrer" className="text-orange-600 hover:text-orange-700">Наша локация</a></p>
              <p>📞 +84 349 338 758</p>
              <p>🌐 <a href="https://t.me/goodzone_vn" target="_blank" rel="noopener noreferrer" className="text-orange-600 hover:text-orange-700">Telegram группа</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HomePage;

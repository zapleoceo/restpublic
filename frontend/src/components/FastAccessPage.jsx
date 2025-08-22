import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { validateTableId, createBotUrl, createMenuUrl, createHomeUrl } from '../utils/tableUtils';
import { TableProvider } from '../contexts/TableContext';
import TableErrorPage from './TableErrorPage';
import { Bot, Menu, Globe, ChevronRight } from 'lucide-react';

const FastAccessPage = () => {
  const { tableId } = useParams();
  const navigate = useNavigate();
  const [isValidTable, setIsValidTable] = useState(true);

  // Валидация номера столика
  useEffect(() => {
    if (!validateTableId(tableId)) {
      console.warn(`Invalid table ID: ${tableId}`);
      setIsValidTable(false);
    } else {
      setIsValidTable(true);
    }
  }, [tableId]);

  // Показываем страницу ошибки для неверного номера столика
  if (!isValidTable) {
    return <TableErrorPage tableId={tableId} />;
  }

  const handleMenuClick = () => {
    navigate(createMenuUrl(tableId));
  };

  const handleHomeClick = () => {
    window.location.href = createHomeUrl();
  };

  const actions = [
    {
      icon: Bot,
      title: 'Telegram Bot',
      description: 'Быстрый заказ в один клик',
      href: createBotUrl(tableId),
      gradient: 'from-blue-500 to-blue-600',
      iconBg: 'bg-blue-500',
      iconColor: 'text-white'
    },
    {
      icon: Menu,
      title: 'Меню онлайн',
      description: 'Полное меню нашего ресторана',
      onClick: handleMenuClick,
      gradient: 'from-orange-500 to-red-500',
      iconBg: 'bg-orange-500',
      iconColor: 'text-white'
    },
    {
      icon: Globe,
      title: 'Посетить наш сайт',
      description: 'Главная страница сайта',
      onClick: handleHomeClick,
      gradient: 'from-green-500 to-emerald-600',
      iconBg: 'bg-green-500',
      iconColor: 'text-white'
    }
  ];

  return (
    <TableProvider tableId={tableId}>
      <div className="min-h-screen bg-gradient-to-br from-orange-50 via-red-50 to-amber-50">
        <div className="container mx-auto px-4 py-8">
          <div className="max-w-md mx-auto">
            
            {/* Номер столика */}
            <div className="text-center mb-12">
              <div className="inline-flex items-center justify-center w-28 h-28 bg-gradient-to-br from-orange-400 to-red-500 rounded-full shadow-xl border-4 border-white mb-6">
                <span className="text-4xl font-bold text-white">№{tableId}</span>
              </div>
              <h1 className="text-xl font-semibold text-gray-800 mb-2">Ваш столик</h1>
              <p className="text-sm text-gray-600">Выберите действие для продолжения</p>
            </div>

            {/* Карточки действий */}
            <div className="space-y-6">
              {actions.map((action, index) => {
                const IconComponent = action.icon;
                const Component = action.href ? 'a' : 'button';
                const props = action.href ? { 
                  href: action.href, 
                  target: '_blank', 
                  rel: 'noopener noreferrer' 
                } : {};

                return (
                  <Component
                    key={index}
                    {...props}
                    onClick={action.onClick}
                    className="group w-full bg-white rounded-3xl shadow-lg hover:shadow-2xl border-0 p-6 text-left transition-all duration-300 hover:scale-[1.02] cursor-pointer overflow-hidden relative"
                  >
                    {/* Градиентный фон при наведении */}
                    <div className={`absolute inset-0 bg-gradient-to-r ${action.gradient} opacity-0 group-hover:opacity-5 transition-opacity duration-300`}></div>
                    
                    <div className="relative flex items-center justify-between">
                      <div className="flex items-center space-x-5">
                        <div className={`w-14 h-14 rounded-2xl ${action.iconBg} flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-lg`}>
                          <IconComponent className="w-7 h-7 text-white" />
                        </div>
                        <div className="flex-1">
                          <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-gray-800 transition-colors duration-200">
                            {action.title}
                          </h3>
                          <p className="text-sm text-gray-600 group-hover:text-gray-700 transition-colors duration-200">
                            {action.description}
                          </p>
                        </div>
                      </div>
                      <div className="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 group-hover:bg-gray-200 transition-colors duration-200">
                        <ChevronRight className="w-4 h-4 text-gray-600 group-hover:text-gray-800 transition-colors duration-200" />
                      </div>
                    </div>
                  </Component>
                );
              })}
            </div>

            {/* Футер */}
            <div className="mt-16 text-center">
              <div className="inline-flex items-center space-x-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-sm">
                <div className="w-2 h-2 bg-orange-500 rounded-full"></div>
                <p className="text-sm font-medium text-gray-700">
                  GoodZone • Ресторан и развлечения
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </TableProvider>
  );
};

export default FastAccessPage;

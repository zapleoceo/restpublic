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
      color: 'bg-blue-500 hover:bg-blue-600',
      iconColor: 'text-blue-500'
    },
    {
      icon: Menu,
      title: 'Меню онлайн',
      description: 'Полное меню нашего ресторана',
      onClick: handleMenuClick,
      color: 'bg-orange-500 hover:bg-orange-600',
      iconColor: 'text-orange-500'
    },
    {
      icon: Globe,
      title: 'Посетить наш сайт',
      description: 'Главная страница сайта',
      onClick: handleHomeClick,
      color: 'bg-green-500 hover:bg-green-600',
      iconColor: 'text-green-500'
    }
  ];

  return (
    <TableProvider tableId={tableId}>
      <div className="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50">
        <div className="container mx-auto px-4 py-8">
          <div className="max-w-md mx-auto">
            
            {/* Номер столика */}
            <div className="text-center mb-12">
              <div className="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full shadow-lg border-4 border-orange-100 mb-4">
                <span className="text-3xl font-bold text-gray-900">№{tableId}</span>
              </div>
              <h1 className="text-lg font-medium text-gray-700">Ваш столик</h1>
            </div>

            {/* Карточки действий */}
            <div className="space-y-4">
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
                    className="group w-full bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 p-6 text-left transition-all duration-200 hover:scale-[1.02] cursor-pointer"
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-4">
                        <div className={`w-12 h-12 rounded-xl ${action.color} flex items-center justify-center group-hover:scale-110 transition-transform duration-200`}>
                          <IconComponent className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-semibold text-gray-900 mb-1">
                            {action.title}
                          </h3>
                          <p className="text-sm text-gray-600">
                            {action.description}
                          </p>
                        </div>
                      </div>
                      <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors duration-200" />
                    </div>
                  </Component>
                );
              })}
            </div>

            {/* Футер */}
            <div className="mt-12 text-center">
              <p className="text-xs text-gray-400">
                GoodZone • Ресторан и развлечения
              </p>
            </div>
          </div>
        </div>
      </div>
    </TableProvider>
  );
};

export default FastAccessPage;

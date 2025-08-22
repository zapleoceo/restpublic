import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { validateTableId, createBotUrl, createMenuUrl, createHomeUrl } from '../utils/tableUtils';
import { TableProvider } from '../contexts/TableContext';
import TableNumber from './TableNumber';
import FastActionCard from './FastActionCard';
import TableErrorPage from './TableErrorPage';

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
      icon: '🤖',
      title: 'Telegram Bot',
      description: 'Быстрый заказ в один клик',
      href: createBotUrl(tableId)
    },
    {
      icon: '🍽️',
      title: 'Меню онлайн',
      description: 'Полное меню нашего ресторана',
      onClick: handleMenuClick
    },
    {
      icon: '🌐',
      title: 'Посетить наш сайт',
      description: 'Главная страница сайта',
      onClick: handleHomeClick
    }
  ];

  return (
    <TableProvider tableId={tableId}>
      <div className="min-h-screen bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center p-4">
        <div className="w-full max-w-md">
          {/* Номер столика */}
          <TableNumber tableId={tableId} />

          {/* Карточки действий */}
          <div className="space-y-4">
            {actions.map((action, index) => (
              <FastActionCard
                key={index}
                icon={action.icon}
                title={action.title}
                description={action.description}
                onClick={action.onClick}
                href={action.href}
              />
            ))}
          </div>

          {/* Версия приложения (скрытая) */}
          <div className="fixed bottom-2 right-2 text-xs text-gray-400 opacity-30 pointer-events-none">
            v2.2.13
          </div>
        </div>
      </div>
    </TableProvider>
  );
};

export default FastAccessPage;

import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { validateTableId, createBotUrl, createMenuUrl, createHomeUrl } from '../utils/tableUtils';
import { TableProvider } from '../contexts/TableContext';
import TableErrorPage from './TableErrorPage';
import LanguageSwitcher from './LanguageSwitcher';
import { getSiteName } from '../constants/siteConfig';
import { Menu, Globe, ChevronRight } from 'lucide-react';

const FastAccessPage = () => {
  const { t, i18n } = useTranslation();
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
      icon: Menu,
      title: t('fast_access.online_menu'),
      description: t('fast_access.online_menu_desc'),
      onClick: handleMenuClick,
      gradient: 'from-orange-500 to-red-500',
      iconBg: 'bg-orange-500'
    },
    {
      icon: Globe,
      title: t('fast_access.home_page'),
      description: t('fast_access.home_page_desc'),
      onClick: handleHomeClick,
      gradient: 'from-green-500 to-emerald-600',
      iconBg: 'bg-green-500'
    }
  ];

  return (
    <TableProvider tableId={tableId}>
      <div className="min-h-screen bg-gradient-to-br from-orange-50 via-red-50 to-amber-50">
        {/* Header с переключателем языка */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center justify-between h-16">
              <div className="flex-1"></div>
              <h1 className="text-2xl font-bold text-gray-900">{getSiteName(i18n.language)}</h1>
              <div className="flex-1 flex justify-end">
                <LanguageSwitcher />
              </div>
            </div>
          </div>
        </div>

        <div className="container mx-auto px-4 py-8">
          <div className="max-w-md mx-auto">
            
            {/* Номер столика */}
            <div className="text-center mb-12">
              <div className="inline-flex items-center justify-center w-28 h-28 bg-gradient-to-br from-orange-400 to-red-500 rounded-full shadow-xl border-4 border-white mb-6">
                <span className="text-4xl font-bold text-white">№{tableId}</span>
              </div>
              <h1 className="text-xl font-semibold text-gray-800 mb-2">{t('fast_access.your_table')}</h1>
              <p className="text-sm text-gray-600">{t('fast_access.choose_action')}</p>
            </div>

            {/* Три большие кнопки */}
            <div className="space-y-4">
              {actions.map((action, index) => {
                const IconComponent = action.icon;

                return (
                  <button
                    key={index}
                    onClick={action.onClick}
                    className="group w-full h-20 bg-white rounded-2xl shadow-lg hover:shadow-xl border-0 transition-all duration-300 hover:scale-[1.02] cursor-pointer overflow-hidden relative"
                  >
                    {/* Градиентный фон при наведении */}
                    <div className={`absolute inset-0 bg-gradient-to-r ${action.gradient} opacity-0 group-hover:opacity-5 transition-opacity duration-300`}></div>
                    
                    <div className="relative flex items-center justify-between h-full px-6">
                      <div className="flex items-center space-x-4">
                        <div className={`w-12 h-12 rounded-xl ${action.iconBg} flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-lg`}>
                          <IconComponent className="w-6 h-6 text-white" />
                        </div>
                        <div>
                          <h3 className="text-lg font-bold text-gray-900 group-hover:text-gray-800 transition-colors duration-200">
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
                  </button>
                );
              })}
            </div>

            {/* Футер */}
            <div className="mt-16 text-center">
              <div className="inline-flex items-center space-x-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-sm">
                <div className="w-2 h-2 bg-orange-500 rounded-full"></div>
                <p className="text-sm font-medium text-gray-700">
                  {t('fast_access.goodzone_restaurant')}
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

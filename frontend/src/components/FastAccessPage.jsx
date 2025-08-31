import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { validateTableId, createBotUrl, createMenuUrl, createHomeUrl } from '../utils/tableUtils';
import { TableProvider } from '../contexts/TableContext';
import TableErrorPage from './TableErrorPage';
import { Header, Footer } from './layout';
import { SEOHead } from './seo/SEOHead';
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
      gradient: 'from-primary-500 to-primary-600',
      iconBg: 'bg-primary-500'
    },
    {
      icon: Globe,
      title: t('fast_access.home_page'),
      description: t('fast_access.home_page_desc'),
      onClick: handleHomeClick,
      gradient: 'from-secondary-500 to-secondary-600',
      iconBg: 'bg-secondary-500'
    }
  ];

  return (
    <TableProvider tableId={tableId}>
      <div className="fast-access-page min-h-screen bg-neutral-50">
        <SEOHead 
          title={`Столик №${tableId} - Быстрый доступ`}
          description={`Быстрый доступ к меню и услугам для столика №${tableId} в North Republic`}
          keywords={`столик ${tableId}, меню, заказ, North Republic`}
        />
        
        <Header />
        
        <main className="main-content pt-16">
          <div className="container mx-auto px-4 py-12">
            <div className="max-w-md mx-auto">
              
              {/* Номер столика */}
              <div className="text-center mb-12">
                <div className="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full shadow-xl border-4 border-white mb-6">
                  <span className="text-5xl font-serif font-bold text-white">№{tableId}</span>
                </div>
                <h1 className="text-2xl font-serif font-bold text-primary-900 mb-2">
                  {t('fast_access.your_table')}
                </h1>
                <p className="text-neutral-600">
                  {t('fast_access.choose_action')}
                </p>
              </div>

              {/* Кнопки действий */}
              <div className="space-y-6">
                {actions.map((action, index) => {
                  const IconComponent = action.icon;

                  return (
                    <button
                      key={index}
                      onClick={action.onClick}
                      className="group w-full bg-white rounded-xl shadow-lg hover:shadow-xl border border-neutral-200 transition-all duration-300 hover:scale-[1.02] cursor-pointer overflow-hidden relative"
                    >
                      <div className="p-6">
                        <div className="flex items-center space-x-4">
                          <div className={`flex-shrink-0 w-12 h-12 ${action.iconBg} rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300`}>
                            <IconComponent className="w-6 h-6 text-white" />
                          </div>
                          <div className="flex-1 text-left">
                            <h3 className="text-lg font-serif font-bold text-primary-900 mb-1">
                              {action.title}
                            </h3>
                            <p className="text-sm text-neutral-600">
                              {action.description}
                            </p>
                          </div>
                          <ChevronRight className="w-5 h-5 text-neutral-400 group-hover:text-primary-500 transition-colors" />
                        </div>
                      </div>
                    </button>
                  );
                })}
              </div>

              {/* Дополнительная информация */}
              <div className="mt-12 text-center">
                <p className="text-sm text-neutral-500">
                  {t('fast_access.help_text') || "Нужна помощь? Обратитесь к персоналу"}
                </p>
              </div>
            </div>
          </div>
        </main>
        
        <Footer />
      </div>
    </TableProvider>
  );
};

export default FastAccessPage;

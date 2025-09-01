import React from 'react';
import { useTranslation } from 'react-i18next';
import { ChevronDown } from 'lucide-react';

const SortSelector = ({ sortType, onSortChange }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center space-x-2">
      <span className="text-sm font-medium text-gray-700">
        {t('menu.sort.title')}:
      </span>
      <div className="relative">
        <select
          value={sortType}
          onChange={(e) => onSortChange(e.target.value)}
          className="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 cursor-pointer hover:border-orange-400 transition-colors"
        >
          <option value="popularity">{t('menu.sort.popularity')}</option>
          <option value="alphabet">{t('menu.sort.alphabet')}</option>
        </select>
        <ChevronDown className="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
      </div>
    </div>
  );
};

export default SortSelector;

import React from 'react';
import { BaseButton } from '../ui/BaseButton';

export const EventFilters = ({ filters, onFiltersChange }) => {
  const categories = [
    { value: 'all', label: 'Все категории' },
    { value: 'concert', label: 'Концерты' },
    { value: 'party', label: 'Вечеринки' },
    { value: 'workshop', label: 'Мастер-классы' },
    { value: 'sport', label: 'Спорт' },
    { value: 'food', label: 'Кулинария' }
  ];

  const statuses = [
    { value: 'all', label: 'Все события' },
    { value: 'upcoming', label: 'Предстоящие' },
    { value: 'ongoing', label: 'Текущие' },
    { value: 'completed', label: 'Завершенные' }
  ];

  const handleFilterChange = (filterType, value) => {
    onFiltersChange({
      ...filters,
      [filterType]: value
    });
  };

  return (
    <div className="event-filters bg-white rounded-lg shadow-md p-6">
      <h3 className="text-lg font-serif font-bold text-primary-900 mb-4">
        Фильтры
      </h3>
      
      <div className="space-y-4">
        {/* Категории */}
        <div>
          <label className="block text-sm font-medium text-neutral-700 mb-2">
            Категория
          </label>
          <div className="flex flex-wrap gap-2">
            {categories.map(category => (
              <BaseButton
                key={category.value}
                variant={filters.category === category.value ? 'primary' : 'outline'}
                size="sm"
                onClick={() => handleFilterChange('category', category.value)}
              >
                {category.label}
              </BaseButton>
            ))}
          </div>
        </div>

        {/* Статус */}
        <div>
          <label className="block text-sm font-medium text-neutral-700 mb-2">
            Статус
          </label>
          <div className="flex flex-wrap gap-2">
            {statuses.map(status => (
              <BaseButton
                key={status.value}
                variant={filters.status === status.value ? 'primary' : 'outline'}
                size="sm"
                onClick={() => handleFilterChange('status', status.value)}
              >
                {status.label}
              </BaseButton>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

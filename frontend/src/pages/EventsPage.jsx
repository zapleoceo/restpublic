import React, { useState } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { useEvents } from '../hooks/useEvents';
import { Calendar, EventList, EventFilters } from '../components/events';

export const EventsPage = () => {
  const { t } = useTranslation();
  const { events, loading, error } = useEvents();
  const [viewMode, setViewMode] = useState('calendar'); // 'calendar' | 'list'
  const [selectedDate, setSelectedDate] = useState(new Date());
  const [filters, setFilters] = useState({
    category: 'all',
    status: 'upcoming'
  });

  const filteredEvents = events.filter(event => {
    if (filters.category !== 'all' && event.category !== filters.category) return false;
    if (filters.status !== 'all' && event.status !== filters.status) return false;
    return true;
  });

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="text-6xl mb-4">❌</div>
          <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
            Ошибка загрузки
          </h3>
          <p className="text-neutral-600">{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="events-page min-h-screen bg-neutral-50">
      <div className="container mx-auto px-4 py-8">
        <div className="page-header text-center mb-8">
          <h1 className="page-title text-4xl md:text-5xl font-serif font-bold text-primary-900 mb-4">
            {t('events.calendar_title') || "Календарь событий"}
          </h1>
          <p className="page-subtitle text-lg text-neutral-600 max-w-2xl mx-auto">
            {t('events.calendar_subtitle') || "Откройте для себя увлекательные события в Республике Север"}
          </p>
        </div>

        <div className="events-controls mb-8">
          <div className="flex flex-col lg:flex-row gap-6">
            <div className="view-toggle flex-1">
              <div className="bg-white rounded-lg shadow-md p-2 inline-flex">
                <button 
                  className={`px-4 py-2 rounded-md transition-colors ${
                    viewMode === 'calendar' 
                      ? 'bg-primary-500 text-white' 
                      : 'text-neutral-600 hover:text-neutral-900'
                  }`}
                  onClick={() => setViewMode('calendar')}
                >
                  {t('events.calendar_view') || "Календарь"}
                </button>
                <button 
                  className={`px-4 py-2 rounded-md transition-colors ${
                    viewMode === 'list' 
                      ? 'bg-primary-500 text-white' 
                      : 'text-neutral-600 hover:text-neutral-900'
                  }`}
                  onClick={() => setViewMode('list')}
                >
                  {t('events.list_view') || "Список"}
                </button>
              </div>
            </div>
            
            <div className="filters-section flex-1">
              <EventFilters 
                filters={filters}
                onFiltersChange={setFilters}
              />
            </div>
          </div>
        </div>

        <div className="events-content">
          {viewMode === 'calendar' ? (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div className="lg:col-span-2">
                <Calendar 
                  events={filteredEvents}
                  selectedDate={selectedDate}
                  onDateSelect={setSelectedDate}
                />
              </div>
              <div className="lg:col-span-1">
                <div className="bg-white rounded-lg shadow-md p-6">
                  <h3 className="text-lg font-serif font-bold text-primary-900 mb-4">
                    События на {selectedDate.toLocaleDateString('ru-RU')}
                  </h3>
                  <EventList 
                    events={filteredEvents}
                    selectedDate={selectedDate}
                  />
                </div>
              </div>
            </div>
          ) : (
            <EventList 
              events={filteredEvents}
              selectedDate={null}
            />
          )}
        </div>
      </div>
    </div>
  );
};

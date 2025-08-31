import React from 'react';
import { formatDate } from '../../utils/formatters';

export const Calendar = ({ events, selectedDate, onDateSelect }) => {
  const currentDate = selectedDate || new Date();
  const currentMonth = currentDate.getMonth();
  const currentYear = currentDate.getFullYear();

  // Получаем первый день месяца и количество дней
  const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
  const lastDayOfMonth = new Date(currentYear, currentMonth + 1, 0);
  const daysInMonth = lastDayOfMonth.getDate();
  const startingDayOfWeek = firstDayOfMonth.getDay();

  // Создаем массив дней для отображения
  const days = [];
  
  // Добавляем пустые ячейки для начала месяца
  for (let i = 0; i < startingDayOfWeek; i++) {
    days.push(null);
  }
  
  // Добавляем дни месяца
  for (let i = 1; i <= daysInMonth; i++) {
    days.push(new Date(currentYear, currentMonth, i));
  }

  // Получаем события для конкретной даты
  const getEventsForDate = (date) => {
    if (!date || !events) return [];
    return events.filter(event => {
      const eventDate = new Date(event.date);
      return eventDate.toDateString() === date.toDateString();
    });
  };

  const weekDays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];

  return (
    <div className="calendar">
      <div className="calendar-header mb-6">
        <h3 className="text-2xl font-serif font-bold text-primary-900">
          {currentDate.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' })}
        </h3>
      </div>
      
      <div className="calendar-grid">
        {/* Дни недели */}
        <div className="grid grid-cols-7 gap-1 mb-2">
          {weekDays.map(day => (
            <div key={day} className="text-center text-sm font-medium text-neutral-600 py-2">
              {day}
            </div>
          ))}
        </div>
        
        {/* Дни месяца */}
        <div className="grid grid-cols-7 gap-1">
          {days.map((day, index) => {
            const eventsForDay = getEventsForDate(day);
            const isToday = day && day.toDateString() === new Date().toDateString();
            const isSelected = day && day.toDateString() === selectedDate?.toDateString();
            
            return (
              <div
                key={index}
                className={`calendar-day min-h-[60px] p-2 border border-neutral-200 cursor-pointer transition-colors ${
                  !day ? 'bg-neutral-50' : ''
                } ${
                  isToday ? 'bg-primary-100 border-primary-300' : ''
                } ${
                  isSelected ? 'bg-primary-500 text-white' : ''
                } ${
                  day ? 'hover:bg-neutral-50' : ''
                }`}
                onClick={() => day && onDateSelect(day)}
              >
                {day && (
                  <>
                    <div className="text-sm font-medium mb-1">
                      {day.getDate()}
                    </div>
                    {eventsForDay.length > 0 && (
                      <div className="flex flex-wrap gap-1">
                        {eventsForDay.slice(0, 2).map(event => (
                          <div
                            key={event.id}
                            className="w-2 h-2 rounded-full bg-secondary-500"
                            title={event.title}
                          />
                        ))}
                        {eventsForDay.length > 2 && (
                          <div className="text-xs text-neutral-500">
                            +{eventsForDay.length - 2}
                          </div>
                        )}
                      </div>
                    )}
                  </>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

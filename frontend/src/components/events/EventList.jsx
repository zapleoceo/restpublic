import React from 'react';
import { Link } from 'react-router-dom';
import { formatEventDate } from '../../utils/formatters';

export const EventList = ({ events, selectedDate }) => {
  const filteredEvents = selectedDate 
    ? events.filter(event => {
        const eventDate = new Date(event.date);
        return eventDate.toDateString() === selectedDate.toDateString();
      })
    : events;

  return (
    <div className="event-list">
      <div className="space-y-4">
        {filteredEvents.length > 0 ? (
          filteredEvents.map(event => (
            <Link
              key={event.id}
              to={`/events/${event.id}`}
              className="event-item block bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6"
            >
              <div className="flex items-start space-x-4">
                <div className="event-image flex-shrink-0">
                  <img
                    src={event.poster}
                    alt={event.title}
                    className="w-24 h-24 object-cover rounded-lg"
                  />
                </div>
                <div className="event-content flex-1">
                  <div className="event-date text-sm text-primary-600 font-medium mb-1">
                    {formatEventDate(event.date)}
                  </div>
                  <h3 className="event-title text-xl font-serif font-bold text-primary-900 mb-2">
                    {event.title}
                  </h3>
                  <p className="event-description text-neutral-600 mb-3 line-clamp-2">
                    {event.shortDescription}
                  </p>
                  <div className="event-meta flex items-center space-x-4 text-sm text-neutral-500">
                    <span className="event-location">
                      📍 {event.location}
                    </span>
                    {event.price && (
                      <span className="event-price">
                        💰 {event.price}
                      </span>
                    )}
                    <span className="event-category">
                      🏷️ {event.category}
                    </span>
                  </div>
                </div>
                <div className="event-arrow flex-shrink-0">
                  <svg className="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </div>
            </Link>
          ))
        ) : (
          <div className="text-center py-12">
            <div className="text-6xl mb-4">📅</div>
            <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
              {selectedDate ? 'На эту дату событий нет' : 'События скоро появятся'}
            </h3>
            <p className="text-neutral-600">
              {selectedDate ? 'Выберите другую дату или следите за обновлениями' : 'Следите за нашей афишей'}
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

import React from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useEvents } from '../../hooks/useEvents';
import { formatEventDate } from '../../utils/formatters';
import { Link } from 'react-router-dom';

export const EventsSection = () => {
  const { t } = useTranslation();
  const { events, loading } = useEvents();
  
  const eventsContent = {
    title: "Афиша",
    description: "Будущие события"
  };

  if (loading) {
    return (
      <section id="events" className="container s-events target-section">
        <div className="text-center">
          <div className="text-2xl" style={{ color: 'var(--color-text)' }}>⏳ Загружаем события...</div>
        </div>
      </section>
    );
  }

  const upcomingEvents = events?.filter(event => event.status === 'upcoming') || [];

  return (
    <section id="events" className="container s-events target-section">
      <div className="row s-events__content">
        <div className="column xl-12">
          <div className="section-header" data-num="04">
            <h2 className="text-display-title">{eventsContent.title}</h2>
          </div>
          
          {upcomingEvents.length > 0 ? (
            <>
              <div className="swiper-container events-slider">
                <div className="swiper-wrapper">
                  {upcomingEvents.map((event) => (
                    <div key={event.id} className="events-slider__slide swiper-slide">
                      <Link to={`/events/${event.id}`} className="event-card">
                        <div className="event-card__poster">
                          <img 
                            src={event.poster} 
                            alt={event.title} 
                            className="event-card__image"
                          />
                          <div className="event-card__overlay">
                            <div className="event-card__date">
                              {formatEventDate(event.date)}
                            </div>
                            <h3 className="event-card__title">{event.title}</h3>
                            <p className="event-card__description">{event.shortDescription}</p>
                          </div>
                        </div>
                      </Link>
                    </div>
                  ))}
                </div>
                <div className="swiper-pagination"></div>
                <div className="swiper-button-next"></div>
                <div className="swiper-button-prev"></div>
              </div>
              
              <div className="events-view-all">
                <Link to="/events" className="btn btn--primary">
                  {t('events.view_calendar') || "Смотреть календарь"}
                </Link>
              </div>
            </>
          ) : (
            <div className="text-center py-12">
              <div className="text-6xl mb-4">📅</div>
              <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
                События скоро появятся
              </h3>
              <p className="text-neutral-600">
                Следите за обновлениями нашей афиши
              </p>
            </div>
          )}
        </div>
      </div>
    </section>
  );
};

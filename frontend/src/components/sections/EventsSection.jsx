import React from 'react';
import { SectionWrapper } from '../ui/SectionWrapper';
import { SectionHeader } from '../ui/SectionHeader';
import { BaseButton } from '../ui/BaseButton';
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
      <SectionWrapper id="events" className="s-events">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto"></div>
        </div>
      </SectionWrapper>
    );
  }

  const upcomingEvents = events?.filter(event => event.status === 'upcoming') || [];

  return (
    <SectionWrapper id="events" className="s-events">
      <div className="text-center mb-12">
        <SectionHeader number="04" title={eventsContent.title} />
        <p className="text-lg text-neutral-600 max-w-2xl mx-auto">
          {eventsContent.description}
        </p>
      </div>
      
      {upcomingEvents.length > 0 ? (
        <>
          <div className="swiper-container events-slider">
            <div className="swiper-wrapper grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {upcomingEvents.map((event) => (
                <div key={event.id} className="events-slider__slide swiper-slide">
                  <Link to={`/events/${event.id}`} className="event-card block group">
                    <div className="event-card__poster relative overflow-hidden rounded-lg shadow-lg">
                      <img 
                        src={event.poster} 
                        alt={event.title} 
                        className="event-card__image w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                      <div className="event-card__overlay absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-6">
                        <div className="event-card__date text-sm text-white/80 mb-2">
                          {formatEventDate(event.date)}
                        </div>
                        <h3 className="event-card__title text-xl font-serif font-bold text-white mb-2">
                          {event.title}
                        </h3>
                        <p className="event-card__description text-white/90 text-sm line-clamp-2">
                          {event.shortDescription}
                        </p>
                      </div>
                    </div>
                  </Link>
                </div>
              ))}
            </div>
          </div>
          
          <div className="events-view-all text-center mt-8">
            <Link to="/events">
              <BaseButton variant="primary" size="lg">
                {t('events.view_calendar') || "Смотреть календарь"}
              </BaseButton>
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
    </SectionWrapper>
  );
};

import { Link } from 'react-router-dom';
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';
import { formatEventDate } from '../../utils/formatters';

export const EventsSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const eventsContent = content.events || {
    title: t('events.title'),
    description: 'Будущие события',
    items: [
      {
        id: 1,
        title: "Киновечер под звездами",
        shortDescription: "Показ фильма под открытым небом",
        poster: "/template/images/gallery/gallery-01.jpg",
        date: "2025-02-15T18:00:00.000Z",
        active: true
      },
      {
        id: 2,
        title: "Концерт живой музыки",
        shortDescription: "Вечер с местными музыкантами",
        poster: "/template/images/gallery/gallery-02.jpg",
        date: "2025-02-20T19:00:00.000Z",
        active: true
      },
      {
        id: 3,
        title: "Фестиваль еды",
        shortDescription: "Дегустация блюд разных кухонь",
        poster: "/template/images/gallery/gallery-03.jpg",
        date: "2025-02-25T16:00:00.000Z",
        active: true
      }
    ]
  };
  
  return (
    <SectionWrapper id="events" className="s-events">
      <div className="row s-events__content">
        <div className="column xl-12">
          <SectionHeader number="04" title={eventsContent.title} />
          
          <div className="swiper-container events-slider">
            <div className="swiper-wrapper">
              {eventsContent.items
                .filter(event => event.active)
                .map((event, index) => (
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
              {t('events.view_calendar')}
            </Link>
          </div>
        </div>
      </div>
    </SectionWrapper>
  );
};

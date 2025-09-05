// import { Link } from 'react-router-dom'; // Removed to avoid Router context issues
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
// import { useTranslation } from '../../hooks/useTranslation'; // Temporarily disabled
// import { useSiteContent } from '../../hooks/useSiteContent'; // Temporarily disabled
// import { formatEventDate } from '../../utils/formatters'; // Temporarily disabled

export const EventsSection = () => {
  // const { t } = useTranslation(); // Temporarily disabled
  // const { content } = useSiteContent(); // Temporarily disabled
  
  const eventsContent = {
    title: 'События', // t('section.events.title'),
    events: [
      {
        title: 'Турнир по лазертагу',
        date: '2024-01-15T18:00:00',
        description: 'Еженедельный турнир для всех желающих',
        image: '/template/images/sample-image.jpg',
        link: '/events/lasertag-tournament'
      },
      {
        title: 'Мастер-класс по стрельбе из лука',
        date: '2024-01-20T14:00:00',
        description: 'Обучение традиционной стрельбе из лука',
        image: '/template/images/sample-image.jpg',
        link: '/events/archery-masterclass'
      },
      {
        title: 'Квест "Тайны Северной Республики"',
        date: '2024-01-25T19:00:00',
        description: 'Новый захватывающий квест для команд',
        image: '/template/images/sample-image.jpg',
        link: '/events/quest-mysteries'
      }
    ]
  };

  return (
    <SectionWrapper id="events" className="bg-gray-50">
      <SectionHeader number="03" title={eventsContent.title} />
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {eventsContent.events.map((event, index) => (
          <a
            key={index}
            href={event.link}
            className="group block bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
          >
            <div className="aspect-video overflow-hidden">
              <img
                src={event.image}
                alt={event.title}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
            <div className="p-6">
              <div className="text-sm text-primary-600 font-medium mb-2">
                {new Date(event.date).toLocaleDateString('ru-RU')}
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
                {event.title}
              </h3>
              <p className="text-gray-600">
                {event.description}
              </p>
            </div>
          </a>
        ))}
      </div>
      
      <div className="text-center mt-12">
        <a
          href="/events"
          className="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors"
        >
          Все события
        </a>
      </div>
    </SectionWrapper>
  );
};

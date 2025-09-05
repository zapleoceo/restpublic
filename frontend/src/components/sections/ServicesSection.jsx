// import { Link } from 'react-router-dom'; // Removed to avoid Router context issues
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
// import { useTranslation } from '../../hooks/useTranslation'; // Temporarily disabled
// import { useSiteContent } from '../../hooks/useSiteContent'; // Temporarily disabled

export const ServicesSection = () => {
  // const { t } = useTranslation(); // Temporarily disabled
  // const { content } = useSiteContent(); // Temporarily disabled
  
  const servicesContent = {
    title: 'Наши услуги', // t('section.services.title'),
    services: [
      {
        title: 'Лазертаг',
        description: 'Захватывающие игры в современном лазертаг-арене',
        icon: '🎯',
        link: '/services/lasertag'
      },
      {
        title: 'Квесты',
        description: 'Увлекательные квесты для всех возрастов',
        icon: '🔍',
        link: '/services/quests'
      },
      {
        title: 'BBQ зона',
        description: 'Уютная зона для барбекю и отдыха',
        icon: '🍖',
        link: '/services/bbq'
      },
      {
        title: 'Стрельба из лука',
        description: 'Традиционная стрельба из лука',
        icon: '🏹',
        link: '/services/archery'
      }
    ]
  };

  return (
    <SectionWrapper id="services">
      <SectionHeader number="02" title={servicesContent.title} />
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {servicesContent.services.map((service, index) => (
          <a
            key={index}
            href={service.link}
            className="group block p-6 bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 border border-gray-200 hover:border-primary-300"
          >
            <div className="text-4xl mb-4">{service.icon}</div>
            <h3 className="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
              {service.title}
            </h3>
            <p className="text-gray-600">
              {service.description}
            </p>
          </a>
        ))}
      </div>
    </SectionWrapper>
  );
};

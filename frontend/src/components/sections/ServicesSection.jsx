import { Link } from 'react-router-dom';
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const ServicesSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const servicesContent = content.services || {
    title: t('section.services.title'),
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
          <Link
            key={index}
            to={service.link}
            className="group block p-6 bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 border border-gray-200 hover:border-primary-300"
          >
            <div className="text-4xl mb-4">{service.icon}</div>
            <h3 className="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
              {service.title}
            </h3>
            <p className="text-gray-600">
              {service.description}
            </p>
          </Link>
        ))}
      </div>
    </SectionWrapper>
  );
};

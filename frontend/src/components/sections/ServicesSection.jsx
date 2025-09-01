import { Link } from 'react-router-dom';
import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const ServicesSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const servicesContent = content.services || {
    title: t('services.title'),
    items: [
      {
        id: "lasertag",
        title: "Лазертаг",
        description: "Командная игра",
        icon: "/template/images/icons/icon-close.svg",
        link: "/lasertag",
        active: true,
        order: 1
      },
      {
        id: "archery",
        title: "Archery Tag", 
        description: "Лучный бой",
        icon: "/template/images/icons/icon-close.svg",
        link: "/archerytag",
        active: true,
        order: 2
      },
      {
        id: "cinema",
        title: "Кинотеатр",
        description: "Под открытым небом",
        icon: "/template/images/icons/icon-close.svg",
        link: "/cinema",
        active: true,
        order: 3
      },
      {
        id: "bbq",
        title: "BBQ зона",
        description: "Мангалы и отдых",
        icon: "/template/images/icons/icon-close.svg",
        link: "/bbq",
        active: true,
        order: 4
      }
    ]
  };
  
  return (
    <SectionWrapper id="services" className="s-services">
      <div className="row s-services__content">
        <div className="column xl-12">
          <SectionHeader number="03" title={servicesContent.title} />
          
          <div className="services-grid">
            {servicesContent.items
              .filter(service => service.active)
              .sort((a, b) => a.order - b.order)
              .map(service => (
                <Link key={service.id} to={service.link} className="service-card">
                  <div className="service-card__icon">
                    <img src={service.icon} alt={service.title} />
                  </div>
                  <h3 className="service-card__title">{service.title}</h3>
                  <p className="service-card__description">{service.description}</p>
                </Link>
              ))}
          </div>
        </div>
      </div>
    </SectionWrapper>
  );
};

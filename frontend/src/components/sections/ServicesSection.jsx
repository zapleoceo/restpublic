import React from 'react';
import { SectionWrapper } from '../ui/SectionWrapper';
import { SectionHeader } from '../ui/SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';
import { Link } from 'react-router-dom';

export const ServicesSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const servicesContent = content?.services || {
    title: "Услуги",
    items: [
      {
        id: "lasertag",
        title: "Лазертаг",
        description: "Командная игра",
        icon: "/img/lazertag/icon.png",
        link: "/lasertag",
        active: true,
        order: 1
      },
      {
        id: "archery",
        title: "Archery Tag", 
        description: "Лучный бой",
        icon: "/img/archery/icon.png",
        link: "/archerytag",
        active: true,
        order: 2
      },
      {
        id: "cinema",
        title: "Кинотеатр",
        description: "Фильмы под открытым небом",
        icon: "/img/cinema/icon.png",
        link: "/cinema",
        active: true,
        order: 3
      },
      {
        id: "bbq",
        title: "BBQ зона",
        description: "Шашлыки и барбекю",
        icon: "/img/bbq/icon.png",
        link: "/bbq_zone",
        active: true,
        order: 4
      },
      {
        id: "quests",
        title: "Квесты",
        description: "Интерактивные приключения",
        icon: "/img/quests/icon.png",
        link: "/quests",
        active: true,
        order: 5
      },
      {
        id: "guitar",
        title: "Гитара",
        description: "Музыкальные вечера",
        icon: "/img/guitar/icon.png",
        link: "/guitar",
        active: true,
        order: 6
      },
      {
        id: "boardgames",
        title: "Настольные игры",
        description: "Интеллектуальный досуг",
        icon: "/img/boardgames/icon.png",
        link: "/boardgames",
        active: true,
        order: 7
      },
      {
        id: "yoga",
        title: "Йога",
        description: "Здоровье и гармония",
        icon: "/img/yoga/icon.png",
        link: "/yoga",
        active: true,
        order: 8
      },
      {
        id: "bathhouse",
        title: "Баня",
        description: "Традиционная русская баня",
        icon: "/img/bathhouse/icon.png",
        link: "/bathhouse",
        active: true,
        order: 9
      }
    ]
  };

  const activeServices = servicesContent.items?.filter(service => service.active) || [];

  return (
    <SectionWrapper id="services" className="s-services bg-neutral-50">
      <div className="text-center mb-12">
        <SectionHeader number="03" title={servicesContent.title} />
      </div>
      
      <div className="services-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        {activeServices.map(service => (
          <Link 
            key={service.id} 
            to={service.link} 
            className="service-card group bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 p-6 text-center"
          >
            <div className="service-card__icon mb-4">
              <img 
                src={service.icon} 
                alt={service.title} 
                className="w-16 h-16 mx-auto group-hover:scale-110 transition-transform duration-300"
              />
            </div>
            <h3 className="service-card__title text-xl font-serif font-bold text-primary-900 mb-2">
              {service.title}
            </h3>
            <p className="service-card__description text-neutral-600">
              {service.description}
            </p>
          </Link>
        ))}
      </div>
    </SectionWrapper>
  );
};

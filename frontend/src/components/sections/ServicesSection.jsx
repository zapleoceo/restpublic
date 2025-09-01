import React from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';
import { Link } from 'react-router-dom';

export const ServicesSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const servicesContent = content?.services || {
    title: "Услуги",
    description: "Широкий спектр развлечений для всех возрастов",
    items: [
      { id: 'lasertag', name: 'Лазертаг', icon: '/img/lazertag/icon.png', link: '/lasertag' },
      { id: 'archery', name: 'Стрельба из лука', icon: '/img/archery/icon.png', link: '/archerytag' },
      { id: 'cinema', name: 'Кинотеатр', icon: '/img/cinema/icon.png', link: '/cinema' },
      { id: 'bbq', name: 'BBQ зона', icon: '/img/bbq/icon.png', link: '/bbq_zone' },
      { id: 'quests', name: 'Квесты', icon: '/img/quests/icon.png', link: '/quests' },
      { id: 'guitar', name: 'Гитара', icon: '/img/guitar/icon.png', link: '/guitar' },
      { id: 'boardgames', name: 'Настольные игры', icon: '/img/boardgames/icon.png', link: '/boardgames' },
      { id: 'yoga', name: 'Йога', icon: '/img/yoga/icon.png', link: '/yoga' },
      { id: 'bathhouse', name: 'Банный комплекс', icon: '/img/bathhouse/icon.png', link: '/bathhouse' }
    ]
  };

  const services = servicesContent.items || [];

  return (
    <section id="services" className="container s-services target-section">
      <div className="row s-services__content">
        <div className="column xl-12">
          <div className="section-header" data-num="03">
            <h2 className="text-display-title">{servicesContent.title}</h2>
          </div>
          
          <div className="services-grid">
            {services.map(service => (
              <Link key={service.id} to={service.link} className="service-card">
                <div className="service-card__icon">
                  <img src={service.icon} alt={service.name} />
                </div>
                <h3 className="service-card__title">{service.name}</h3>
                <p className="service-card__description">{service.description}</p>
              </Link>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};

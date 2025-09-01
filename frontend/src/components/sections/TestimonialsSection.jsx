import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const TestimonialsSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const testimonialsContent = content.testimonials || {
    title: t('testimonials.title'),
    items: [
      {
        id: 1,
        author: "Anna",
        photo: "/template/images/avatars/user-01.jpg",
        text: "На сегодня это лучший кинотеатр под открытым небом💛...",
        active: true,
        order: 1
      },
      {
        id: 2,
        author: "John",
        photo: "/template/images/avatars/user-02.jpg",
        text: "Отличное место для отдыха с семьей. Еда вкусная, атмосфера приятная.",
        active: true,
        order: 2
      },
      {
        id: 3,
        author: "Maria",
        photo: "/template/images/avatars/user-03.jpg",
        text: "Лазертаг был просто потрясающим! Обязательно вернемся еще.",
        active: true,
        order: 3
      },
      {
        id: 4,
        author: "David",
        photo: "/template/images/avatars/user-04.jpg",
        text: "Прекрасное место для проведения мероприятий. Персонал очень дружелюбный.",
        active: true,
        order: 4
      }
    ]
  };
  
  return (
    <SectionWrapper id="testimonials" className="s-testimonials">
      <div className="row s-testimonials__content">
        <div className="column xl-12">
          <SectionHeader number="05" title={testimonialsContent.title} />
          
          <div className="swiper-container testimonials-slider">
            <div className="swiper-wrapper">
              {testimonialsContent.items
                .filter(testimonial => testimonial.active)
                .sort((a, b) => a.order - b.order)
                .map((testimonial, index) => (
                  <div key={testimonial.id} className="testimonials-slider__slide swiper-slide">
                    <div className="testimonials-slider__author">
                      <img 
                        src={testimonial.photo || '/template/images/avatars/user-01.jpg'} 
                        alt={testimonial.author} 
                        className="testimonials-slider__avatar"
                      />
                      <cite className="testimonials-slider__cite">
                        {testimonial.author}
                      </cite>
                    </div>
                    <p>{testimonial.text}</p>
                  </div>
                ))}
            </div>
            <div className="swiper-pagination"></div>
          </div>
        </div>
      </div>
    </SectionWrapper>
  );
};

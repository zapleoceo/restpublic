import React from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const TestimonialsSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const testimonialsContent = content?.testimonials || {
    title: "Что говорят наши клиенты",
    items: [
      {
        id: 1,
        author: "Anna",
        photo: "/img/avatar-placeholder.jpg",
        text: "На сегодня это лучший кинотеатр под открытым небом💛 надеюсь мы вместе посмотрим и обсудим ещё много фильмов.) Шикарный звук, большой хороший экран, свобода , где хочешь там и лежишь смотришь.) Спасибо огромное организаторам.) шаурма от Олега тоже была вкусна.) 🙃🌊👍",
        active: true,
        order: 1
      }
    ]
  };

  const activeTestimonials = testimonialsContent.items?.filter(testimonial => testimonial.active) || [];
  
  // Создаем 10 клонов для карусели
  const testimonialsForCarousel = [];
  for (let i = 0; i < 10; i++) {
    activeTestimonials.forEach(testimonial => {
      testimonialsForCarousel.push({
        ...testimonial,
        id: `${testimonial.id}-clone-${i}`
      });
    });
  }

  return (
    <section id="testimonials" className="container s-testimonials">
      <div className="row s-testimonials__content">
        <div className="column xl-12">
          <div className="section-header" data-num="05">
            <h2 className="text-display-title">{testimonialsContent.title}</h2>
          </div>
          
          {activeTestimonials.length > 0 ? (
            <div className="swiper-container testimonials-slider">
              <div className="swiper-wrapper">
                {testimonialsForCarousel.map((testimonial, index) => (
                  <div key={testimonial.id} className="testimonials-slider__slide swiper-slide">
                    <div className="testimonials-slider__author">
                      <img 
                        src={testimonial.photo || '/img/avatar-placeholder.jpg'} 
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
          ) : (
            <div className="text-center py-12">
              <div className="text-6xl mb-4">💬</div>
              <h3 className="text-xl font-serif font-bold text-primary-900 mb-2">
                Отзывы скоро появятся
              </h3>
              <p className="text-neutral-600">
                Наши гости поделятся своими впечатлениями
              </p>
            </div>
          )}
        </div>
      </div>
    </section>
  );
};

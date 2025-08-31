import React from 'react';
import { SectionWrapper } from '../ui/SectionWrapper';
import { SectionHeader } from '../ui/SectionHeader';
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
        photo: "blob:https://web.telegram.org/af2e1524-c8c3-4789-85e3-def2153d1841",
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
    <SectionWrapper id="testimonials" className="s-testimonials bg-neutral-50">
      <div className="text-center mb-12">
        <SectionHeader number="05" title={testimonialsContent.title} />
      </div>
      
      {activeTestimonials.length > 0 ? (
        <div className="swiper-container testimonials-slider max-w-4xl mx-auto">
          <div className="swiper-wrapper">
            {testimonialsForCarousel.map((testimonial, index) => (
              <div key={testimonial.id} className="testimonials-slider__slide swiper-slide">
                <div className="bg-white rounded-xl shadow-lg p-8 text-center">
                  <div className="testimonials-slider__author mb-6">
                    <img 
                      src={testimonial.photo || '/img/avatar-placeholder.jpg'} 
                      alt={testimonial.author} 
                      className="testimonials-slider__avatar w-16 h-16 rounded-full mx-auto mb-4 object-cover"
                    />
                    <cite className="testimonials-slider__cite text-lg font-serif font-bold text-primary-900">
                      {testimonial.author}
                    </cite>
                  </div>
                  <blockquote className="text-neutral-700 leading-relaxed italic">
                    "{testimonial.text}"
                  </blockquote>
                </div>
              </div>
            ))}
          </div>
          <div className="swiper-pagination mt-6"></div>
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
    </SectionWrapper>
  );
};

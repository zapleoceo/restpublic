import { SectionWrapper } from './SectionWrapper';
import { SectionHeader } from './SectionHeader';
// import { useTranslation } from '../../hooks/useTranslation'; // Temporarily disabled
// import { useSiteContent } from '../../hooks/useSiteContent'; // Temporarily disabled

export const TestimonialsSection = () => {
  // const { t } = useTranslation(); // Temporarily disabled
  // const { content } = useSiteContent(); // Temporarily disabled
  
  const testimonialsContent = {
    title: 'Отзывы клиентов', // t('section.testimonials.title'),
    testimonials: [
      {
        name: 'Александр Петров',
        role: 'Клиент',
        text: 'Отличное место для отдыха с друзьями! Лазертаг просто супер, персонал очень дружелюбный.',
        avatar: '/template/images/avatars/user-01.jpg',
        rating: 5
      },
      {
        name: 'Мария Сидорова',
        role: 'Клиент',
        text: 'Квесты здесь просто потрясающие! Очень интересные сюжеты и качественное оборудование.',
        avatar: '/template/images/avatars/user-02.jpg',
        rating: 5
      },
      {
        name: 'Дмитрий Козлов',
        role: 'Клиент',
        text: 'BBQ зона - идеальное место для корпоративов. Все организовано на высшем уровне.',
        avatar: '/template/images/avatars/user-03.jpg',
        rating: 5
      }
    ]
  };

  return (
    <SectionWrapper id="testimonials">
      <SectionHeader number="04" title={testimonialsContent.title} />
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {testimonialsContent.testimonials.map((testimonial, index) => (
          <div
            key={index}
            className="bg-white p-6 rounded-lg shadow-md border border-gray-200"
          >
            <div className="flex items-center mb-4">
              <img
                src={testimonial.avatar}
                alt={testimonial.name}
                className="w-12 h-12 rounded-full mr-4"
              />
              <div>
                <h4 className="font-semibold text-gray-900">{testimonial.name}</h4>
                <p className="text-sm text-gray-600">{testimonial.role}</p>
              </div>
            </div>
            
            <div className="flex items-center mb-4">
              {[...Array(testimonial.rating)].map((_, i) => (
                <svg
                  key={i}
                  className="w-5 h-5 text-yellow-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              ))}
            </div>
            
            <p className="text-gray-600 italic">"{testimonial.text}"</p>
          </div>
        ))}
      </div>
    </SectionWrapper>
  );
};

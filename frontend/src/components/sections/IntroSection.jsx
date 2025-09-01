import { SectionWrapper } from './SectionWrapper';
import { useTranslation } from '../../hooks/useTranslation';
import { useSiteContent } from '../../hooks/useSiteContent';

export const IntroSection = () => {
  const { t } = useTranslation();
  const { content } = useSiteContent();
  
  const introContent = content.intro || {
    title: t('section.intro.title'),
    subtitle: t('section.intro.subtitle'),
    description: 'Откройте для себя уникальный мир развлечений и отдыха в North Republic.',
    image: '/template/images/intro-pic-primary.jpg'
  };

  return (
    <SectionWrapper id="intro" className="min-h-screen flex items-center">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div className="space-y-6">
          <h1 className="text-4xl md:text-6xl font-bold text-gray-900 leading-tight">
            {introContent.title}
          </h1>
          <p className="text-xl md:text-2xl text-gray-600">
            {introContent.subtitle}
          </p>
          <p className="text-lg text-gray-500">
            {introContent.description}
          </p>
          <div className="flex flex-col sm:flex-row gap-4">
            <button className="bg-primary-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-primary-700 transition-colors">
              {t('button.learn_more')}
            </button>
            <button className="border-2 border-primary-600 text-primary-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-primary-600 hover:text-white transition-colors">
              {t('button.book_now')}
            </button>
          </div>
        </div>
        <div className="relative">
          <img
            src={introContent.image}
            alt="North Republic"
            className="w-full h-auto rounded-lg shadow-2xl"
          />
        </div>
      </div>
    </SectionWrapper>
  );
};

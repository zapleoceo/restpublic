import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { getFeaturedCategories } from '../utils/menuUtils';
import Section from './Section';
import Grid from './Grid';

const MenuSection = ({ categories = [], products = [], limit = 2, className = '' }) => {
  const { t } = useTranslation();
  
  // Используем утилиту для получения категорий
  const featuredCategories = getFeaturedCategories(categories, products, limit);

  if (featuredCategories.length === 0) {
    return null;
  }

  return (
    <Section className={className}>
      <h2 className="text-3xl font-bold text-gray-900 mb-8 text-center">
        {t('menu.title')}
      </h2>
      <Grid cols={1} mdCols={2} gap={8}>
        {featuredCategories.map((category, index) => (
          <Link
            key={category.category_id}
            to={`/menu/${category.category_id}`}
            className="bg-white rounded-lg shadow-sm p-8 hover:shadow-md transition-shadow text-center"
          >
            <h3 className="text-2xl font-semibold text-gray-900 mb-4">
              {index === 0 ? 'Еда' : 'Бар'}
            </h3>
            <p className="text-gray-600">
              {category.products ? category.products.length : 0} позиций
            </p>
          </Link>
        ))}
      </Grid>
    </Section>
  );
};

export default MenuSection;

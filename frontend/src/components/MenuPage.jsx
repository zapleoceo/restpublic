import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { groupProductsByCategory } from '../utils/menuUtils';
import PageContainer from './PageContainer';
import PageHeader from './PageHeader';
import CategoryColumn from './CategoryColumn';

const MenuPage = ({ menuData }) => {
  const { t } = useTranslation();
  const { categoryId } = useParams();
  const [activeTab, setActiveTab] = useState(categoryId || '');

  const categories = menuData?.categories || [];
  const products = menuData?.products || [];

  // Используем утилиту для группировки продуктов по категориям
  const productsByCategory = groupProductsByCategory(categories, products);

  // Берем только первые две категории для двух колонок
  const displayCategories = productsByCategory.slice(0, 2);

  return (
    <PageContainer>
      {/* Header */}
      <PageHeader title="Меню" />

      {/* Two Column Layout */}
      <div className="flex flex-col lg:flex-row min-h-[calc(100vh-120px)]">
        {displayCategories.map((category, index) => (
          <CategoryColumn 
            key={category.category_id}
            category={category}
            index={index}
          />
        ))}
      </div>
    </PageContainer>
  );
};

export default MenuPage;

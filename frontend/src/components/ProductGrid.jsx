import React from 'react';
import { useTranslation } from 'react-i18next';
import ProductCard from './ProductCard';
import EmptyState from './EmptyState';
import Grid from './Grid';

const ProductGrid = ({ products = [], className = '' }) => {
  const { t } = useTranslation();
  
  if (!products || products.length === 0) {
    return (
      <EmptyState 
        title={t('no_products')}
        description={t('no_dishes_in_category')}
      />
    );
  }

  return (
    <Grid cols={2} mdCols={3} lgCols={4} gap={4} className={className}>
      {products.map((product) => (
        <ProductCard key={product.product_id} product={product} />
      ))}
    </Grid>
  );
};

export default ProductGrid;

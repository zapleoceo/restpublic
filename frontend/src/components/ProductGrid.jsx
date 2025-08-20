import React from 'react';
import ProductCard from './ProductCard';
import EmptyState from './EmptyState';
import Grid from './Grid';

const ProductGrid = ({ products = [], className = '' }) => {
  if (!products || products.length === 0) {
    return (
      <EmptyState 
        title="Блюда не найдены"
        description="В данной категории пока нет блюд"
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

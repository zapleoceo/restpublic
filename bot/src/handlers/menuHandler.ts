import { Context } from 'telegraf';
import posterService from '../services/posterService.js';
import { formatPrice, getMainPrice } from '../utils/priceUtils.js';
import { groupProductsByCategory, getCategoryById } from '../utils/menuUtils.js';

export const menuHandler = {
  // Показать главное меню (категории)
  async showMainMenu(ctx: Context) {
    try {
      const categories = await posterService.getCategories();
      
      if (!categories || categories.length === 0) {
        await ctx.reply('❌ Категории не найдены');
        return;
      }

      const message = '🍽️ *Выберите категорию:*\n\n' +
        categories.map(category => 
          `• ${category.category_name}`
        ).join('\n');

      await ctx.reply(message, { parse_mode: 'Markdown' });
    } catch (error) {
      console.error('Error showing main menu:', error);
      await ctx.reply('❌ Ошибка загрузки категорий');
    }
  },

  // Показать все продукты, сгруппированные по категориям
  async showAllProducts(ctx: Context) {
    try {
      const categories = await posterService.getCategories();
      const allProducts = await posterService.getProducts();
      
      if (!categories || categories.length === 0 || !allProducts || allProducts.length === 0) {
        await ctx.reply('❌ Товары не найдены');
        return;
      }

      let message = '🍽️ *Меню по категориям:*\n\n';
      
      // Используем утилиту для группировки продуктов
      const groupedCategories = groupProductsByCategory(categories, allProducts);
      
      for (const category of groupedCategories) {
        message += `📋 *${category.category_name}:*\n`;
        
        category.products.forEach(product => {
          const mainPrice = getMainPrice(product.price);
          const formattedPrice = formatPrice(mainPrice);
          message += `🍽️ ${product.product_name}\n💰 ${formattedPrice} ₫\n\n`;
        });
        
        message += '---\n';
      }

      await ctx.reply(message, { parse_mode: 'Markdown' });
    } catch (error) {
      console.error('Error showing all products:', error);
      await ctx.reply('❌ Ошибка загрузки товаров');
    }
  },

  // Показать продукты категории
  async showCategoryProducts(ctx: Context, categoryId: number) {
    try {
      const products = await posterService.getProducts(categoryId);
      const categories = await posterService.getCategories();
      const category = getCategoryById(categoryId.toString(), categories);
      
      if (!products || products.length === 0) {
        await ctx.reply('❌ Товары в этой категории не найдены');
        return;
      }

      const categoryName = category ? category.category_name : 'Категория';
      let message = `🍽️ *${categoryName}:*\n\n`;
      
      products.forEach(product => {
        const mainPrice = getMainPrice(product.price);
        const formattedPrice = formatPrice(mainPrice);
        message += `🍽️ ${product.product_name}\n💰 ${formattedPrice} ₫\n\n`;
      });

      await ctx.reply(message, { parse_mode: 'Markdown' });
    } catch (error) {
      console.error('Error showing category products:', error);
      await ctx.reply('❌ Ошибка загрузки товаров категории');
    }
  },

  // Показать контакты (без сайтов)
  async showContact(ctx: Context) {
    const contactMessage = 
      '📞 *Контакты North Republic*\n\n' +
      '📍 *Мы тут:* https://maps.app.goo.gl/Hgbn5n83PA11NcqLA\n' +
      '📞 *Телефон:* +84 349 338 758';

    await ctx.reply(contactMessage, { parse_mode: 'Markdown' });
  }
};

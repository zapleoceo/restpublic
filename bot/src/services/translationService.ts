import axios from 'axios';

// Сервис для перевода через внешний API
export const translationService = {
  // Перевести текст с английского на русский
  async translateToRussian(text: string): Promise<string> {
    try {
      // Используем Google Translate API или другой бесплатный сервис
      // Для демонстрации используем MyMemory API (бесплатный)
      const response = await axios.get('https://api.mymemory.translated.net/get', {
        params: {
          q: text,
          langpair: 'en|ru'
        },
        timeout: 5000
      });

      if (response.data && response.data.responseData) {
        return response.data.responseData.translatedText;
      }

      // Fallback: простые переводы для общих слов
      return this.getSimpleTranslation(text);
    } catch (error) {
      console.error('Translation API error:', error);
      // Fallback: простые переводы
      return this.getSimpleTranslation(text);
    }
  },

  // Простые переводы для общих слов (fallback)
  getSimpleTranslation(text: string): string {
    const translations: { [key: string]: string } = {
      'coffee': 'Кофе',
      'tea': 'Чай',
      'soup': 'Суп',
      'salad': 'Салат',
      'rice': 'Рис',
      'noodles': 'Лапша',
      'chicken': 'Курица',
      'beef': 'Говядина',
      'fish': 'Рыба',
      'vegetables': 'Овощи',
      'dessert': 'Десерт',
      'cake': 'Торт',
      'ice cream': 'Мороженое',
      'drinks': 'Напитки',
      'beverages': 'Напитки',
      'main': 'Основные блюда',
      'appetizers': 'Закуски',
      'starters': 'Закуски',
      'desserts': 'Десерты',
      'salads': 'Салаты',
      'soups': 'Супы',
      'sides': 'Гарниры',
      'snacks': 'Закуски',
      'pizza': 'Пицца',
      'pasta': 'Паста',
      'burger': 'Бургер',
      'sandwich': 'Сэндвич',
      'steak': 'Стейк',
      'grill': 'Гриль',
      'fresh': 'Свежий',
      'spicy': 'Острый',
      'sweet': 'Сладкий',
      'hot': 'Горячий',
      'cold': 'Холодный'
    };

    const lowerText = text.toLowerCase();
    
    // Ищем точное совпадение
    if (translations[lowerText]) {
      return translations[lowerText];
    }
    
    // Ищем частичные совпадения
    for (const [key, translation] of Object.entries(translations)) {
      if (lowerText.includes(key)) {
        return translation;
      }
    }
    
    // Если перевод не найден, возвращаем оригинальный текст
    return text;
  }
};

export default translationService;

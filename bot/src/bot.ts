import { Telegraf, Markup, session } from 'telegraf';
import dotenv from 'dotenv';
import fetch from 'node-fetch';
import { menuHandler } from './handlers/menuHandler.js';

// Загружаем переменные окружения
dotenv.config();

const bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN!);

// Настройка сессий
bot.use(session());

// Настройка команд бота (убираем /menu)
bot.telegram.setMyCommands([
  { command: 'start', description: '🚀 Запустить бота' },
  { command: 'categories', description: '📋 Категории блюд' },
  { command: 'help', description: '❓ Помощь' }
]);

// Создаем встроенную клавиатуру с тремя кнопками
const mainKeyboard = Markup.keyboard([
  ['🍽️ Меню', '📋 Категории'],
  ['📞 Контакты']
]).resize();

// Клавиатура для запроса контакта
const contactKeyboard = Markup.keyboard([
  [Markup.button.contactRequest('📱 Поделиться контактом')],
  ['❌ Отмена']
]).resize();

// Обработчик команды /start
bot.command('start', async (ctx) => {
  // Временно логируем chat_id для настройки уведомлений SePay
  const chat = ctx.chat;
  const username = 'username' in chat ? chat.username : 'N/A';
  const firstName = 'first_name' in chat ? chat.first_name : '';
  const lastName = 'last_name' in chat ? chat.last_name : '';
  console.log(`🆔 Chat ID: ${chat.id}, Username: @${username || 'N/A'}, Name: ${firstName || ''} ${lastName || ''}`);
  
  // Проверяем параметры команды start
  const startPayload = ctx.message.text.split(' ')[1];
  
  if (startPayload && startPayload.startsWith('auth_')) {
    // Режим авторизации
    const returnUrl = startPayload.replace('auth_', '');
    console.log(`🔐 Авторизация через Telegram. Return URL: ${returnUrl}`);
    
    await ctx.reply(
      '🔐 Для авторизации в приложении GoodZone, пожалуйста, поделитесь своим контактом:',
      contactKeyboard
    );
    
    // Сохраняем return URL в контексте пользователя
    ctx.session = { ...ctx.session, returnUrl, authMode: true };
  } else {
    // Обычный режим
    await ctx.reply(
      '🍜 Добро пожаловать в North Republic!\n\nИспользуйте кнопки ниже для навигации:',
      mainKeyboard
    );
  }
});

// Обработчик команды /categories
bot.command('categories', async (ctx) => {
  await menuHandler.showMainMenu(ctx);
});

// Обработчик команды /help
bot.command('help', async (ctx) => {
  const helpMessage = 
    '🤖 *Помощь по использованию бота*\n\n' +
    '🍽️ *Меню* - показать все блюда\n' +
    '📋 *Категории* - выбрать категорию блюд\n' +
    '📞 *Контакты* - информация о ресторане\n' +
    '❓ *Помощь* - это сообщение\n\n' +
    'Используйте кнопки внизу экрана для быстрой навигации!';

  await ctx.reply(helpMessage, { parse_mode: 'Markdown' });
});

// Обработчик контактов (авторизация)
bot.on('contact', async (ctx) => {
  const contact = ctx.message.contact;
  const session = ctx.session;
  
  console.log(`📱 Получен контакт: ${contact.phone_number}, ${contact.first_name} ${contact.last_name || ''}`);
  
  if (session?.authMode && session?.returnUrl) {
    try {
      // Отправляем данные на backend
      const backendUrl = process.env.BACKEND_URL || 'https://goodzone.zapleo.com';
      const response = await fetch(`${backendUrl}/api/auth/telegram-callback`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          phone: contact.phone_number,
          name: contact.first_name,
          lastName: contact.last_name || '',
          birthday: '',
          sessionToken: session.returnUrl
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        await ctx.reply(
          '✅ Авторизация успешна! Теперь вы можете вернуться в приложение.',
          mainKeyboard
        );
        
        // Очищаем сессию авторизации
        ctx.session = { ...ctx.session, authMode: false, returnUrl: null };
      } else {
        await ctx.reply(
          '❌ Ошибка авторизации. Попробуйте еще раз.',
          mainKeyboard
        );
      }
    } catch (error) {
      console.error('Error in telegram auth callback:', error);
      await ctx.reply(
        '❌ Ошибка подключения к серверу. Попробуйте позже.',
        mainKeyboard
      );
    }
  } else {
    // Обычный контакт (не для авторизации)
    await ctx.reply(
      '📱 Спасибо за контакт! Мы свяжемся с вами в ближайшее время.',
      mainKeyboard
    );
  }
});

// Обработчики текстовых сообщений (встроенные кнопки)
bot.hears('🍽️ Меню', async (ctx) => {
  await menuHandler.showAllProducts(ctx);
});

bot.hears('📋 Категории', async (ctx) => {
  await menuHandler.showMainMenu(ctx);
});

bot.hears('📞 Контакты', async (ctx) => {
  await menuHandler.showContact(ctx);
});

bot.hears('❌ Отмена', async (ctx) => {
  // Отмена авторизации
  if (ctx.session?.authMode) {
    ctx.session = { ...ctx.session, authMode: false, returnUrl: null };
    await ctx.reply(
      '❌ Авторизация отменена. Используйте кнопки ниже для навигации:',
      mainKeyboard
    );
  } else {
    await ctx.reply(
      'Используйте кнопки внизу экрана для навигации или отправьте /help для получения справки.',
      mainKeyboard
    );
  }
});

// Обработчик всех остальных текстовых сообщений
bot.on('text', async (ctx) => {
  // Временно логируем chat_id для настройки уведомлений SePay
  const chat = ctx.chat;
  const username = 'username' in chat ? chat.username : 'N/A';
  const firstName = 'first_name' in chat ? chat.first_name : '';
  const lastName = 'last_name' in chat ? chat.last_name : '';
  console.log(`🆔 Chat ID: ${chat.id}, Username: @${username || 'N/A'}, Name: ${firstName || ''} ${lastName || ''}, Text: ${ctx.message.text}`);
  
  if (ctx.session?.authMode) {
    await ctx.reply(
      '🔐 Для авторизации нажмите кнопку "📱 Поделиться контактом" или "❌ Отмена" для отмены.',
      contactKeyboard
    );
  } else {
    await ctx.reply(
      'Используйте кнопки внизу экрана для навигации или отправьте /help для получения справки.',
      mainKeyboard
    );
  }
});

// Обработка ошибок
bot.catch((err, ctx) => {
  console.error(`Error for ${ctx.updateType}:`, err);
  ctx.reply('❌ Произошла ошибка. Попробуйте позже.');
});

// Функция запуска бота
async function startBot() {
  try {
    console.log('🚀 Запуск Telegram бота...');
    await bot.launch();
    console.log('✅ Бот успешно запущен!');
  } catch (error) {
    console.error('❌ Ошибка запуска бота:', error);
    process.exit(1);
  }
}

// Graceful stop
process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));

startBot();
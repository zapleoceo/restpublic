import { Telegraf, Markup } from 'telegraf';
import dotenv from 'dotenv';
import { menuHandler } from './handlers/menuHandler.js';

// Загружаем переменные окружения
dotenv.config();

const bot = new Telegraf(process.env.TELEGRAM_BOT_TOKEN!);

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

// Обработчик команды /start
bot.command('start', async (ctx) => {
  // Временно логируем chat_id для настройки уведомлений SePay
  console.log(`🆔 Chat ID: ${ctx.chat.id}, Username: @${ctx.chat.username || 'N/A'}, Name: ${ctx.chat.first_name || ''} ${ctx.chat.last_name || ''}`);
  
  await ctx.reply(
    '🍜 Добро пожаловать в RestPublic!\n\nИспользуйте кнопки ниже для навигации:',
    mainKeyboard
  );
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

// Обработчик всех остальных текстовых сообщений
bot.on('text', async (ctx) => {
  // Временно логируем chat_id для настройки уведомлений SePay
  console.log(`🆔 Chat ID: ${ctx.chat.id}, Username: @${ctx.chat.username || 'N/A'}, Name: ${ctx.chat.first_name || ''} ${ctx.chat.last_name || ''}, Text: ${ctx.message.text}`);
  
  await ctx.reply(
    'Используйте кнопки внизу экрана для навигации или отправьте /help для получения справки.',
    mainKeyboard
  );
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

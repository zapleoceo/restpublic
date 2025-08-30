import { Telegraf, Markup, session, Context } from 'telegraf';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

// Загружаем переменные окружения
dotenv.config();

// Определяем интерфейс для сессии
interface SessionData {
  returnUrl?: string;
  authMode?: boolean;
}

// Расширяем контекст для поддержки сессий
interface MyContext extends Context {
  session?: SessionData;
}

const bot = new Telegraf<MyContext>(process.env.TELEGRAM_BOT_TOKEN!);

// Настройка сессий
bot.use(session());

// Клавиатура для авторизации
const authKeyboard = Markup.keyboard([
  [Markup.button.contactRequest('📱 Поделиться контактом')],
  ['🔐 АВТОРИЗОВАТЬСЯ'],
  ['❌ Отмена']
]).resize();

// Обычная клавиатура
const mainKeyboard = Markup.keyboard([
  ['🔐 АВТОРИЗОВАТЬСЯ']
]).resize();

// Обработчик команды /start
bot.command('start', async (ctx) => {
  const startPayload = ctx.message.text.split(' ')[1];
  
  if (startPayload && startPayload.startsWith('auth_')) {
    // Режим авторизации из приложения
    const returnUrl = startPayload.replace('auth_', '');
    console.log(`🔐 Авторизация через Telegram. Return URL: ${returnUrl}`);
    
    await ctx.reply(
      '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
      authKeyboard
    );
    
    ctx.session = { ...ctx.session, returnUrl, authMode: true };
  } else {
    // Обычный режим
    await ctx.reply(
      '🔐 Добро пожаловать! Для авторизации нажмите кнопку ниже:',
      mainKeyboard
    );
  }
});

// Обработчик кнопки "АВТОРИЗОВАТЬСЯ"
bot.hears('🔐 АВТОРИЗОВАТЬСЯ', async (ctx) => {
  await ctx.reply(
    '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
    authKeyboard
  );
  ctx.session = { ...ctx.session, authMode: true };
});

// Обработчик контактов
bot.on('contact', async (ctx) => {
  const contact = ctx.message.contact;
  const session = ctx.session;
  
  console.log(`📱 Получен контакт: ${contact.phone_number}, ${contact.first_name} ${contact.last_name || ''}`);
  
  if (session?.authMode) {
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
          sessionToken: session.returnUrl || ''
        })
      });
      
      const result = await response.json() as any;
      
      if (result.success) {
        await ctx.reply(
          '✅ Авторизация успешна! Теперь вы можете вернуться в приложение.',
          mainKeyboard
        );
        
        // Очищаем сессию авторизации
        ctx.session = { ...ctx.session, authMode: false, returnUrl: undefined };
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
    await ctx.reply(
      '📱 Спасибо за контакт! Для авторизации нажмите "🔐 АВТОРИЗОВАТЬСЯ".',
      mainKeyboard
    );
  }
});

// Обработчик отмены
bot.hears('❌ Отмена', async (ctx) => {
  ctx.session = { ...ctx.session, authMode: false, returnUrl: undefined };
  await ctx.reply(
    '❌ Авторизация отменена. Для авторизации нажмите "🔐 АВТОРИЗОВАТЬСЯ".',
    mainKeyboard
  );
});

// Обработчик всех остальных сообщений
bot.on('text', async (ctx) => {
  if (ctx.session?.authMode) {
    await ctx.reply(
      '🔐 Для авторизации нажмите кнопку "📱 Поделиться контактом" или "❌ Отмена" для отмены.',
      authKeyboard
    );
  } else {
    await ctx.reply(
      '🔐 Для авторизации нажмите кнопку "🔐 АВТОРИЗОВАТЬСЯ".',
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
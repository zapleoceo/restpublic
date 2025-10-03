import { Telegraf, Markup, session, Context } from 'telegraf';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

// Загружаем переменные окружения из общего .env файла
dotenv.config({ path: '../.env' });

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

// Убираем встроенное меню команд бота
bot.telegram.deleteMyCommands();

// Клавиатура для авторизации
const authKeyboard = Markup.keyboard([
  [Markup.button.contactRequest('📱 Авторизоваться')]
]).resize();

// Обычная клавиатура
const mainKeyboard = Markup.keyboard([
  ['📱 Авторизоваться']
]).resize();

// Обработчик команды /start
bot.command('start', async (ctx) => {
  const startPayload = ctx.message.text.split(' ')[1];

  // Проверяем тип чата
  if (ctx.chat?.type === 'private') {
    if (startPayload === 'auth') {
      // Режим авторизации из приложения
      console.log(`🔐 Авторизация через Telegram`);
      console.log(`🔐 Текущая сессия перед установкой:`, ctx.session);

      await ctx.reply(
        '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
        authKeyboard
      );

      ctx.session = { authMode: true, returnUrl: 'auth' };
      console.log(`🔐 Сессия после установки:`, ctx.session);
    } else {
      // Обычный режим - тоже готовим к авторизации
      await ctx.reply(
        '🔐 Добро пожаловать! Для авторизации нажмите кнопку ниже:',
        mainKeyboard
      );

      // Устанавливаем сессию для авторизации
      ctx.session = { authMode: false, returnUrl: 'start' };
    }
  } else {
    // В группах игнорируем команду /start
    return;
  }
});

// Обработчик кнопки "📱 Авторизоваться"
bot.hears('📱 Авторизоваться', async (ctx) => {
  // Проверяем тип чата - работаем только в личных чатах
  if (ctx.chat?.type === 'private') {
    console.log(`🔐 Пользователь нажал кнопку авторизации`);
    console.log(`🔐 Текущая сессия перед установкой:`, ctx.session);

    // В личном чате можно запрашивать контакт
    await ctx.reply(
      '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
      authKeyboard
    );

    // Устанавливаем режим авторизации и returnUrl
    ctx.session = { ...ctx.session, authMode: true, returnUrl: 'button_auth' };
    console.log(`🔐 Сессия после установки кнопки:`, ctx.session);
  } else {
    // В группах игнорируем
    return;
  }
});

// Обработчик контактов
bot.on('contact', async (ctx) => {
  // Проверяем тип чата - работаем только в личных чатах
  if (ctx.chat?.type !== 'private') {
    return; // Игнорируем контакты в группах
  }

  const contact = ctx.message.contact;
  const session = ctx.session;

  console.log(`📱 Получен контакт: ${contact.phone_number}, ${contact.first_name} ${contact.last_name || ''}`);
  console.log(`📋 Полные данные контакта:`, {
    phone_number: contact.phone_number,
    first_name: contact.first_name,
    last_name: contact.last_name,
    user_id: contact.user_id,
    vcard: contact.vcard
  });
  console.log(`🔐 Данные сессии:`, session);
  console.log(`🔐 Данные сессии:`, {
    authMode: session?.authMode,
    returnUrl: session?.returnUrl
  });

  // Проверяем, что контакт получен и сессия существует (авторизация через любой путь)
  if (session && session.returnUrl) {
    try {
      // Отправляем данные на backend
      const backendUrl = process.env.BACKEND_URL || 'https://veranda.my';
      const requestData = {
        phone: contact.phone_number,
        name: contact.first_name,
        lastName: contact.last_name || '',
        birthday: '',
        sessionToken: session.returnUrl || ''
      };
      
      console.log(`🚀 Отправляем данные на backend:`, {
        url: `${backendUrl}/api/auth/telegram-callback`,
        data: requestData
      });
      
      const response = await fetch(`${backendUrl}/api/auth/telegram-callback`, { 
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
      });

      const result = await response.json() as any;
      
      console.log(`📥 Ответ от backend:`, {
        status: response.status,
        success: result.success,
        data: result
      });

      if (result.success) {
        // Создаем клавиатуру с кнопкой возврата в приложение
        const returnUrl = result.redirectUrl || 'https://veranda.my/menu2.php?auth=success&session=' + result.sessionToken;
        const returnKeyboard = Markup.inlineKeyboard([
          [Markup.button.url('🔗 Вернуться в приложение', returnUrl)]
        ]);

        await ctx.reply(
          '✅ Авторизация успешна! Нажмите кнопку ниже, чтобы вернуться в приложение:',
          returnKeyboard
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
    // Если контакт получен не в режиме авторизации, просто игнорируем
    return;
  }
});

// Обработчик всех остальных сообщений
bot.on('text', async (ctx) => {
  // Проверяем тип чата - работаем только в личных чатах
  if (ctx.chat?.type === 'private') {
    if (ctx.session?.authMode) {
      await ctx.reply(
        '🔐 Для авторизации нажмите кнопку "📱 Авторизоваться" выше.',
        authKeyboard
      );
    } else {
      await ctx.reply(
        '🔐 Для авторизации нажмите кнопку "📱 Авторизоваться".',
        mainKeyboard
      );
    }
  } else {
    // В группах и супергруппах игнорируем сообщения
    return;
  }
});

// Обработка ошибок
bot.catch((err, ctx) => {
  console.error(`Error for ${ctx.updateType}:`, err);
  
  // Отправляем сообщение об ошибке только в личных чатах
  if (ctx.chat?.type === 'private') {
    ctx.reply('❌ Произошла ошибка. Попробуйте позже.').catch(console.error);
  }
});

// Функция запуска бота
async function startBot() {
  try {
    console.log('🚀 Запуск Telegram бота на NR сервере...');
    await bot.launch();
    console.log('✅ Бот успешно запущен на NR сервере!');
  } catch (error) {
    console.error('❌ Ошибка запуска бота:', error);
    process.exit(1);
  }
}

// Graceful stop
process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));

startBot();

import { Telegraf, Markup, session, Context } from 'telegraf';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

// Загружаем переменные окружения
dotenv.config({ path: '../.env' });

// Интерфейс для сессии
interface SessionData {
  sessionToken?: string;
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
    if (startPayload && startPayload.startsWith('auth_')) {
      // Режим авторизации из приложения с sessionToken
      const sessionToken = startPayload.replace('auth_', '');
      console.log(`🔐 Авторизация через Telegram с токеном: ${sessionToken}`);

      await ctx.reply(
        '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
        authKeyboard
      );

      ctx.session = { authMode: true, sessionToken: sessionToken };
    } else if (startPayload === 'auth') {
      // Режим авторизации из приложения без токена
      console.log(`🔐 Авторизация через Telegram`);

      await ctx.reply(
        '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
        authKeyboard
      );

      ctx.session = { authMode: true, sessionToken: 'auth' };
    } else {
      // Обычный режим
      await ctx.reply(
        '🔐 Добро пожаловать! Для авторизации нажмите кнопку ниже:',
        mainKeyboard
      );

      ctx.session = { authMode: false, sessionToken: 'start' };
    }
  }
});

// Обработчик кнопки "📱 Авторизоваться"
bot.hears('📱 Авторизоваться', async (ctx) => {
  if (ctx.chat?.type === 'private') {
    console.log(`🔐 Пользователь нажал кнопку авторизации`);

    await ctx.reply(
      '🔐 Для авторизации в приложении, пожалуйста, поделитесь своим контактом:',
      authKeyboard
    );

    ctx.session = { ...ctx.session, authMode: true, sessionToken: 'button_auth' };
  }
});

// Обработчик контактов
bot.on('contact', async (ctx) => {
  if (ctx.chat?.type !== 'private') {
    return;
  }

  const contact = ctx.message.contact;
  const session = ctx.session;

  console.log(`📱 Получен контакт: ${contact.phone_number}, ${contact.first_name} ${contact.last_name || ''}`);
  console.log(`🔐 Данные сессии:`, session);

  if (session && session.sessionToken) {
    try {
      // Генерируем sessionToken если нужно
      let sessionToken = session.sessionToken;
      if (sessionToken === 'auth' || sessionToken === 'start' || sessionToken === 'button_auth') {
        sessionToken = Date.now().toString(36) + Math.random().toString(36).substr(2);
      }
      
      const requestData = {
        phone: contact.phone_number,
        name: contact.first_name,
        lastName: contact.last_name || '',
        birthday: '',
        sessionToken: sessionToken
      };
      
      console.log(`🚀 Отправляем данные на backend:`, {
        url: `https://veranda.my/auth_telegram_callback.php`,
        data: requestData
      });
      
      const response = await fetch(`https://veranda.my/auth_telegram_callback.php`, { 
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
      });

      console.log(`📡 Response status: ${response.status}`);
      
      const responseText = await response.text();
      console.log(`📡 Response body:`, responseText);
      
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        console.error(`❌ JSON parse error:`, parseError);
        console.error(`❌ Response text:`, responseText);
        throw new Error(`Invalid JSON response: ${responseText.substring(0, 100)}...`);
      }
      
      console.log(`📥 Ответ от backend:`, {
        status: response.status,
        success: result.success,
        data: result
      });

      if (result.success) {
        // Создаем клавиатуру с кнопкой возврата в приложение
        const returnUrl = result.redirectUrl || `https://veranda.my/menu2.php?auth=success&session=${sessionToken}`;
        const returnKeyboard = Markup.inlineKeyboard([
          [Markup.button.url('🔗 Вернуться в приложение', returnUrl)]
        ]);

        await ctx.reply(
          '✅ Авторизация успешна! Нажмите кнопку ниже, чтобы вернуться в приложение:',
          returnKeyboard
        );

        // Очищаем сессию авторизации
        ctx.session = { ...ctx.session, authMode: false, sessionToken: undefined };
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
    return;
  }
});

// Обработчик всех остальных сообщений
bot.on('text', async (ctx) => {
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
  }
});

// Обработка ошибок
bot.catch((err, ctx) => {
  console.error(`Error for ${ctx.updateType}:`, err);
  
  if (ctx.chat?.type === 'private') {
    ctx.reply('❌ Произошла ошибка. Попробуйте позже.').catch(console.error);
  }
});

// Функция запуска бота
async function startBot() {
  try {
    console.log('🚀 Запуск нового Telegram бота...');
    await bot.launch();
    console.log('✅ Новый бот успешно запущен!');
  } catch (error) {
    console.error('❌ Ошибка запуска бота:', error);
    process.exit(1);
  }
}

// Graceful stop
process.once('SIGINT', () => bot.stop('SIGINT'));
process.once('SIGTERM', () => bot.stop('SIGTERM'));

startBot();

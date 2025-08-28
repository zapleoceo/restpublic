module.exports = {
  apps: [
    {
      name: 'northrepublic-backend',
      script: 'backend/server.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 3002
      },
      APP_VERSION: '2.4.13'
    }
    // Временно отключен бот из-за конфликта с другим экземпляром
    // {
    //   name: 'northrepublic-bot',
    //   script: 'bot/dist/bot.js',
    //   instances: 1,
    //   autorestart: true,
    //   watch: false,
    //   max_memory_restart: '1G',
    //   env: {
    //     NODE_ENV: 'production'
    //   }
    // }
  ]
};

const APP_VERSION = '3.0.5';

module.exports = {
  apps: [
    {
      name: 'northrepublic-backend',
      script: 'backend/server.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env_file: '.env',
      env: {
        NODE_ENV: 'production',
        PORT: 3002
      },
      APP_VERSION: APP_VERSION
    }
    // Временно отключен бот из-за rate limiting
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

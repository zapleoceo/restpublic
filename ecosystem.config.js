module.exports = {
  apps: [
    {
      name: 'northrepublic-backend',
      script: './backend/server.js',
      cwd: '/var/www/northrepubli_usr/data/www/northrepublic.me',
      env_file: '.env',
      env: {
        APP_VERSION: '2.4.7',
        NODE_ENV: 'production',
        PORT: 3002
      },
      log_file: './logs/backend.log',
      error_file: './logs/backend-error.log',
      out_file: './logs/backend-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G'
    },
    {
      name: 'northrepublic-bot',
      script: './bot/dist/bot.js',
      cwd: '/var/www/northrepubli_usr/data/www/northrepublic.me',
      env_file: '.env',
      env: {
        NODE_ENV: 'production'
      },
      log_file: './logs/bot.log',
      error_file: './logs/bot-error.log',
      out_file: './logs/bot-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G'
    }
  ]
};

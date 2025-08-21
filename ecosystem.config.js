module.exports = {
  apps: [
    {
      name: 'restpublic-backend',
      script: './backend/server.js',
      cwd: '/var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com',
      env: {
        NODE_ENV: 'production',
        PORT: 3001,
        env_file: '.env',
        APP_VERSION: '2.3.8'
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
      name: 'restpublic-bot',
      script: './bot/dist/bot.js',
      cwd: '/var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com',
      env: {
        NODE_ENV: 'production',
        env_file: '.env',
        env_file: '.env'
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

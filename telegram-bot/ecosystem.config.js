module.exports = {
  apps: [
    {
      name: 'northrepublic-telegram-bot',
      script: 'dist/bot.js',
      cwd: '/var/www/northrepubli_usr/data/www/northrepublic.me/telegram-bot',
      instances: 1,
      exec_mode: 'fork',
      watch: false,
      max_memory_restart: '200M',
      env: {
        NODE_ENV: 'production',
        TELEGRAM_BOT_TOKEN: process.env.TELEGRAM_BOT_TOKEN,
        BACKEND_URL: 'https://northrepublic.me'
      },
      error_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-telegram-bot-error.log',
      out_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-telegram-bot-out.log',
      log_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-telegram-bot.log',
      time: true,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000
    }
  ]
};

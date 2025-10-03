module.exports = {
  apps: [
    {
      name: 'veranda-telegram-bot',
      script: 'dist/bot.js',
      cwd: '/var/www/veranda_my_usr/data/www/veranda.my/telegram-bot',
      instances: 1,
      exec_mode: 'fork',
      watch: false,
      max_memory_restart: '200M',
      env_file: '/var/www/veranda_my_usr/data/www/veranda.my/.env',
      env: {
        NODE_ENV: 'production',
        BACKEND_URL: 'https://veranda.my'
      },
      error_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-telegram-bot-error.log',
      out_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-telegram-bot-out.log',
      log_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-telegram-bot.log',
      time: true,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000
    }
  ]
};

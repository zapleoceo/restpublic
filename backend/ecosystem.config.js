module.exports = {
  apps: [{
    name: 'veranda-backend',
    script: 'server.js',
    cwd: '/var/www/veranda_my_usr/data/www/veranda.my/backend',
    env_file: '../.env',
    env: {
      NODE_ENV: 'production',
      PORT: 3003
    },
    instances: 1,
    exec_mode: 'fork',
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    error_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-backend-error.log',
    out_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-backend-out.log',
    log_file: '/var/www/veranda_my_usr/data/.pm2/logs/veranda-backend-combined.log',
    time: true
  }]
};

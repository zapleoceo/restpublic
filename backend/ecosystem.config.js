module.exports = {
  apps: [{
    name: 'northrepublic-backend',
    script: 'server.js',
    cwd: '/var/www/northrepubli_usr/data/www/northrepublic.me/backend',
    env_file: '../.env',
    env: {
      NODE_ENV: 'production',
      PORT: 3002
    },
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    error_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-backend-error.log',
    out_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-backend-out.log',
    log_file: '/var/www/northrepubli_usr/data/.pm2/logs/northrepublic-backend-combined.log',
    time: true
  }]
};

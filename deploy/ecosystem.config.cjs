// PM2 untuk Nuxt SSR di app-01: `pm2 start deploy/ecosystem.config.cjs`
module.exports = {
  apps: [{
    name: 'kea-web',
    cwd: '/var/www/kea-web/current',
    script: '.output/server/index.mjs',
    exec_mode: 'cluster',
    instances: 2,
    env: {
      PORT: 3000,
      NITRO_HOST: '127.0.0.1',
      NUXT_API_ORIGIN: 'http://127.0.0.1:8080',
      NUXT_PUBLIC_SITE_URL: 'https://[DOMAIN]',
    },
  }],
}

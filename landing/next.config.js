/** @type {import('next').NextConfig} */
const nextConfig = {
  experimental: {
    appDir: true,
  },
  env: {
    FACTORY_CREATE_ENDPOINT: process.env.FACTORY_CREATE_ENDPOINT,
    SITE_FACTORY_TOKEN: process.env.SITE_FACTORY_TOKEN,
  },
}

module.exports = nextConfig


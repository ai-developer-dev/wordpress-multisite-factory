# WordPress Multisite Factory + Next.js Landing

A local-first WordPress Multisite "Website Factory" that automatically creates sub-sites via a secure REST API, paired with a Next.js Landing app for user interaction.

## 🚀 Quick Start

```bash
# 1. Clone and setup
git clone <your-repo>
cd wordpress-multisite-factory

# 2. Environment setup
cp .env.example .env
# Edit .env with your preferences

# 3. Start infrastructure
./scripts/phase-1-setup.sh

# 4. Test WordPress
curl -I http://factory.lvh.me
curl -I http://test.factory.lvh.me

# 5. Start landing app
cd landing && npm run dev
```

## 🌐 Access Points

- **WordPress Network Admin**: http://factory.lvh.me/wp-admin/network/
- **Test Sub-site**: http://test.factory.lvh.me/
- **Landing App**: http://localhost:3000
- **MailHog**: http://localhost:8025

## 🏗️ Architecture

- **Docker Stack**: Nginx, PHP-FPM 8.3, MariaDB, Redis, MailHog
- **WordPress**: Multisite with subdomain routing
- **Site Factory Plugin**: Secure REST API for creating sub-sites
- **Landing App**: Next.js with popup form → server-side API proxy
- **Security**: Rate limiting, validation, server-side token handling

## 📋 Phases

1. **Phase 1**: Docker infrastructure + WordPress Multisite
2. **Phase 2**: Site Factory plugin implementation
3. **Phase 3**: Next.js Landing app with form
4. **Phase 4**: Domain mapping simulation
5. **Phase 5**: Security controls and abuse prevention
6. **Phase 6**: Performance verification and backup

## 🔧 Development

```bash
# Run specific phase
./scripts/phase-1-setup.sh
./scripts/phase-2-plugin-tests.sh
# ... etc

# WordPress CLI
./scripts/common.sh wp --info

# Docker compose
./scripts/common.sh dc ps
```

## 📚 Documentation

See [docs/MULTISITE_FACTORY.md](docs/MULTISITE_FACTORY.md) for detailed implementation notes, testing results, and phase-by-phase progress.

## 🔒 Security Notes

- `SITE_FACTORY_TOKEN` is never exposed to the browser
- Landing app acts as a secure proxy to WordPress API
- Rate limiting and validation on factory endpoint
- Local development only (HTTP, no TLS)

## 🚧 Production Considerations

- HTTPS everywhere
- Domain-based routing
- SMTP provider (not MailHog)
- Redis cluster
- Monitoring and alerting
- CI/CD pipeline


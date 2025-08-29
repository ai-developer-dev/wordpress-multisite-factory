# WordPress Multisite Factory + Next.js Landing

## Project Overview
A local-first WordPress Multisite "Website Factory" using Docker (Nginx, PHP-FPM, MariaDB, Redis, MailHog) with a Next.js Landing app that creates sub-sites via a secure server-side API.

## Goals
- Local Dockerized Multisite at http://factory.lvh.me
- Secure REST factory endpoint for creating sub-sites
- Next.js Landing app at http://localhost:3000 with popup form
- Users fill form → submit → receive new sub-site URL
- All secrets remain server-side

## Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Landing App  │    │  WordPress       │    │   Docker        │
│  localhost:3000│    │  factory.lvh.me  │    │   Stack         │
│                 │    │                  │    │                 │
│ ┌─────────────┐ │    │ ┌──────────────┐ │    │ ┌─────────────┐ │
│ │ Popup Form  │ │    │ │ Site Factory │ │    │ │   Nginx     │ │
│ │             │ │    │ │   Plugin     │ │    │ │   Port 80   │ │
│ └─────────────┘ │    │ └──────────────┘ │    │ └─────────────┘ │
│                 │    │                  │    │                 │
│ ┌─────────────┐ │    │ ┌──────────────┐ │    │ ┌─────────────┐ │
│ │/api/create- │ │───▶│ │ REST Endpoint│ │    │ │   PHP-FPM   │ │
│ │   site      │ │    │ │ /wp-json/... │ │    │ │   Port 9000 │ │
│ └─────────────┘ │    │ └──────────────┘ │    │ └─────────────┘ │
└─────────────────┘    └──────────────────┘    │                 │
                                               │ ┌─────────────┐ │
                                               │ │  MariaDB    │ │
                                               │ │  Port 3306  │ │
                                               │ └─────────────┘ │
                                               │                 │
                                               │ ┌─────────────┐ │
                                               │ │   Redis     │ │
                                               │ │   Port 6379 │ │
                                               │ └─────────────┘ │
                                               │                 │
                                               │ ┌─────────────┐ │
                                               │ │  MailHog    │ │
                                               │ │ Port 8025   │ │
                                               │ └─────────────┘ │
                                               └─────────────────┘
```

## Ports & Services
- **80**: Nginx (WordPress + subdomains)
- **3000**: Next.js Landing app
- **8025**: MailHog (email testing)
- **3306**: MariaDB
- **6379**: Redis
- **9000**: PHP-FPM (internal)

## Acceptance Criteria
- [ ] PHASE 1: Docker stack up, WordPress Multisite working
- [ ] PHASE 2: Site Factory plugin creates sub-sites via REST
- [ ] PHASE 3: Landing app form → API → new sub-site
- [ ] PHASE 4: Domain mapping simulation works
- [ ] PHASE 5: Security controls and rate limiting
- [ ] PHASE 6: Performance verification and backup drills

## Phase Progress

### PHASE 0 — Bootstrap & Plan ✅
- [x] Project structure created
- [x] Initial documentation
- [x] Architecture diagram
- [x] Phase breakdown
- [x] Docker infrastructure files created
- [x] Site Factory plugin implemented
- [x] Next.js Landing app created
- [x] Phase scripts created

### PHASE 1 — Local Multisite up 🔄
**Objectives**: Bring up Docker stack, install WordPress Multisite
**Status**: 🔄 Ready to run
**Files Created**:
- `infrastructure/docker-compose.yml` - Complete Docker stack
- `infrastructure/nginx/site.conf` - Nginx configuration
- `infrastructure/php/Dockerfile` - PHP-FPM 8.3 with extensions
- `infrastructure/php/php.ini` - PHP configuration
- `infrastructure/wp-cli/config.yml` - WP-CLI configuration
- `scripts/phase-1-setup.sh` - Phase 1 automation script

**Next Steps**: Run `./scripts/phase-1-setup.sh`

### PHASE 2 — Site Factory plugin ✅
**Objectives**: Implement plugin with secure REST endpoint
**Status**: ✅ Complete
**Files Created**:
- `wp-plugin/site-factory/site-factory.php` - Main plugin file
- `wp-plugin/site-factory/includes/class-sf-rest.php` - REST API handler
- `wp-plugin/site-factory/includes/class-sf-blueprint.php` - Blueprint system
- `wp-plugin/site-factory/includes/class-sf-users.php` - User management
- `wp-plugin/site-factory/includes/class-sf-logger.php` - Logging system
- `wp-plugin/site-factory/includes/class-sf-security.php` - Security & rate limiting
- `wp-plugin/site-factory/blueprints/cpa-onepage.html` - Sample blueprint
- `scripts/phase-2-plugin-tests.sh` - Plugin testing script

**Features**:
- Secure REST endpoint with X-Auth token
- Blueprint system with token replacement
- User creation and site assignment
- Comprehensive logging
- Rate limiting and security controls

### PHASE 3 — Landing app ✅
**Objectives**: Next.js app with popup form
**Status**: ✅ Complete
**Files Created**:
- `landing/package.json` - Dependencies and scripts
- `landing/next.config.js` - Next.js configuration
- `landing/tsconfig.json` - TypeScript configuration
- `landing/tailwind.config.js` - Tailwind CSS configuration
- `landing/postcss.config.js` - PostCSS configuration
- `landing/app/layout.tsx` - Root layout
- `landing/app/globals.css` - Global styles
- `landing/app/page.tsx` - Landing page with hero and CTA
- `landing/components/Modal.tsx` - Form modal component
- `landing/app/api/create-site/route.ts` - Server-side API proxy

**Features**:
- Beautiful landing page with hero section
- Modal form for business information
- Server-side API proxy (secure token handling)
- Responsive design with Tailwind CSS
- Form validation and error handling

### PHASE 4 — Domain mapping
**Objectives**: Helper for changing sub-site domains
**Status**: ⏳ Pending

### PHASE 5 — Security controls
**Objectives**: Rate limiting, validation, abuse prevention
**Status**: ⏳ Pending

### PHASE 6 — Performance & backup
**Objectives**: Redis verification, backup drills
**Status**: ⏳ Pending

## Quick Start
```bash
# 1. Copy environment
cp env.example .env

# 2. Add to /etc/hosts (if not already done)
echo "127.0.0.1 factory.lvh.me" | sudo tee -a /etc/hosts
echo "127.0.0.1 *.factory.lvh.me" | sudo tee -a /etc/hosts

# 3. Start infrastructure
./scripts/phase-1-setup.sh

# 4. Test the setup
curl -I http://factory.lvh.me
curl -I http://test.factory.lvh.me

# 5. Test plugin functionality
./scripts/phase-2-plugin-tests.sh

# 6. Start landing app
cd landing && npm run dev
```

## Access Points
- **WordPress Network Admin**: http://factory.lvh.me/wp-admin/network/
- **Test Sub-site**: http://test.factory.lvh.me/
- **Landing App**: http://localhost:3000
- **MailHog**: http://localhost:8025

## Current Status
**Phase 0**: ✅ Complete - All infrastructure and application files created
**Phase 1**: 🔄 Ready to run - Docker stack and WordPress setup script ready
**Phase 2**: ✅ Complete - Site Factory plugin fully implemented
**Phase 3**: ✅ Complete - Next.js Landing app fully implemented

## Next Steps
1. **Run Phase 1**: Execute `./scripts/phase-1-setup.sh` to bring up the Docker stack
2. **Test WordPress**: Verify http://factory.lvh.me and http://test.factory.lvh.me are accessible
3. **Test Plugin**: Run `./scripts/phase-2-plugin-tests.sh` to verify Site Factory functionality
4. **Test Landing App**: Start the Next.js app and test the complete flow
5. **Document Results**: Update this document with test results and screenshots

## Implementation Notes

### Docker Infrastructure
- **Services**: Nginx, PHP-FPM 8.3, MariaDB 10.11, Redis 7, MailHog, WP-CLI, Landing App
- **Networks**: Isolated bridge network for security
- **Volumes**: Persistent storage for database and WordPress files
- **Ports**: Exposed only necessary ports (80, 3000, 8025)

### Site Factory Plugin
- **Security**: X-Auth token authentication, rate limiting, IP blocking
- **Blueprints**: HTML-based templates with token replacement
- **Logging**: Comprehensive logging with rotation and compression
- **User Management**: Automatic user creation and site assignment

### Landing App
- **Architecture**: Next.js 14 with App Router
- **Styling**: Tailwind CSS with custom component classes
- **Security**: Server-side API proxy (no client-side token exposure)
- **UX**: Modal form with validation, loading states, and success/error handling

### Environment Variables
- **SITE_FACTORY_TOKEN**: Secure token for API authentication
- **FACTORY_CREATE_ENDPOINT**: WordPress REST endpoint URL
- **Database**: MariaDB credentials and WordPress admin settings
- **SMTP**: MailHog configuration for local email testing

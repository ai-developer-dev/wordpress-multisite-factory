# WordPress Multisite Factory - Railway Deployment Guide

## Overview
This guide will help you deploy the WordPress Multisite Factory to Railway platform. The system includes a WordPress multisite backend with the Site Factory plugin and a Next.js landing page.

## Prerequisites
- Railway account ([railway.app](https://railway.app))
- GitHub repository with your code
- Domain name (optional, Railway provides subdomains)

## Deployment Steps

### 1. Create Railway Project

1. **Login to Railway** and create a new project
2. **Connect your GitHub repository** containing this code
3. **Add MySQL database service**:
   - Click "New" → "Database" → "Add MySQL"
   - Railway will automatically create database credentials

### 2. Configure Environment Variables

#### WordPress Service Environment Variables
Set these in Railway dashboard under your WordPress service:

```bash
# Copy from .env.railway.example and fill in your values
WORDPRESS_DEBUG=false
WORDPRESS_DEBUG_LOG=false
WORDPRESS_TABLE_PREFIX=wp_

# Generate unique security keys at: https://api.wordpress.org/secret-key/1.1/salt/
WORDPRESS_AUTH_KEY=your-unique-auth-key-here
WORDPRESS_SECURE_AUTH_KEY=your-unique-secure-auth-key-here
WORDPRESS_LOGGED_IN_KEY=your-unique-logged-in-key-here
WORDPRESS_NONCE_KEY=your-unique-nonce-key-here
WORDPRESS_AUTH_SALT=your-unique-auth-salt-here
WORDPRESS_SECURE_AUTH_SALT=your-unique-secure-auth-salt-here
WORDPRESS_LOGGED_IN_SALT=your-unique-logged-in-salt-here
WORDPRESS_NONCE_SALT=your-unique-nonce-salt-here

# Site Factory Configuration
SF_API_ENABLED=true
SF_MAX_SITES_PER_REQUEST=1
SF_API_RATE_LIMIT=10
SF_LOG_LEVEL=info

# Generate a secure random token for API access
SF_API_TOKEN=your-secure-api-token-here

RAILWAY_ENVIRONMENT=production
```

#### Landing Page Service Environment Variables
Set these for your Next.js landing page service:

```bash
# Copy from landing/.env.local.example
NEXT_PUBLIC_WORDPRESS_URL=https://your-wordpress-service.railway.app
NEXT_PUBLIC_API_TOKEN=your-secure-api-token-here
```

### 3. Deploy Services

#### Deploy WordPress Service
1. **Create new service** from your repository
2. **Set root directory** to the main directory (where Dockerfile is located)
3. **Railway will automatically detect** the Dockerfile and build
4. **Wait for deployment** to complete

#### Deploy Landing Page Service
1. **Create another service** from the same repository
2. **Set root directory** to `landing/`
3. **Railway will detect** Next.js and deploy automatically
4. **Configure custom domain** if desired

### 4. WordPress Initial Setup

1. **Access your WordPress site** at the Railway-provided URL
2. **Complete WordPress installation**:
   - Choose language
   - Create admin user
   - Complete setup

3. **Enable Multisite**:
   - Go to Tools → Network Setup
   - Choose "Sub-directories" installation type
   - Follow the instructions to update wp-config.php
   - Upload the Railway-optimized wp-config.php we created

4. **Activate Site Factory Plugin**:
   - Go to Network Admin → Plugins
   - Activate "Site Factory" plugin

5. **Configure API Token**:
   - Go to Network Admin → Settings → Site Factory
   - Set the API token (same as SF_API_TOKEN environment variable)

### 5. Connect Landing Page to WordPress

1. **Update environment variables** with actual WordPress URL
2. **Test the connection** by opening the landing page
3. **Fill out the form** to test site creation

## Troubleshooting

### Common Issues

#### Docker Build Fails
- **Check Dockerfile syntax** and package dependencies
- **Ensure all required packages** are installed (libpng-dev, etc.)
- **Review build logs** in Railway dashboard

#### Database Connection Issues
- **Verify DATABASE_URL** is set correctly (Railway sets this automatically)
- **Check wp-config.php** is using the Railway configuration
- **Ensure MySQL service** is running and accessible

#### Site Creation Fails
- **Check API token** matches between WordPress and landing page
- **Verify multisite setup** is complete
- **Review Site Factory plugin logs** in wp-content/uploads/site-factory-logs/

#### Landing Page API Errors
- **Verify CORS settings** in WordPress
- **Check API endpoint URLs** are correct
- **Ensure proper authentication** headers are sent

### Health Checks

#### WordPress Health Check
Visit: `https://your-wordpress-site.railway.app/health.php`

Should return JSON with:
```json
{
  "status": "ok",
  "database": {"status": "connected"},
  "extensions": {...},
  "filesystem": {...}
}
```

#### Landing Page Health Check
The landing page should load without errors and display the business form.

## Security Considerations

1. **Use strong, unique API tokens**
2. **Enable HTTPS** (Railway provides this automatically)
3. **Regularly update WordPress** and plugins
4. **Monitor site creation logs** for suspicious activity
5. **Set up proper rate limiting**
6. **Use strong database passwords** (Railway handles this)

## Performance Optimization

1. **Enable Redis caching** (Railway add-on available)
2. **Use a CDN** (CloudFlare recommended)
3. **Optimize images** and assets
4. **Enable OpCache** (included in our PHP configuration)
5. **Monitor resource usage** in Railway dashboard

## Backup Strategy

1. **Database backups**: Railway provides automated MySQL backups
2. **File backups**: Use Railway volumes for persistent storage
3. **Code backups**: Maintain in Git repository
4. **Regular exports**: Export site data periodically

## Support

For issues:
1. Check Railway logs in the dashboard
2. Review WordPress debug logs (if enabled)
3. Check Site Factory plugin logs
4. Test API endpoints manually
5. Verify environment variables are set correctly

## Next Steps

After successful deployment:
1. **Configure custom domain** in Railway
2. **Set up email delivery** (SMTP configuration)
3. **Add business templates** to the Site Factory
4. **Customize landing page** design
5. **Set up analytics** and monitoring
6. **Add automated backups**

## Cost Optimization

Railway pricing is based on usage:
- **Starter plan**: $5/month for basic sites
- **Pro plan**: $20/month for higher traffic
- **Database**: Separate pricing for MySQL
- **Monitor usage** in Railway dashboard to optimize costs
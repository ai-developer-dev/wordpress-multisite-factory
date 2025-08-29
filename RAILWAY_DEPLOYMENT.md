# Railway Deployment Guide

## Quick Start

1. **Push to GitHub** - Railway will auto-deploy from your repository
2. **Add Database** - Railway provides MySQL/PostgreSQL
3. **Configure Environment** - Set production variables
4. **Access your site** - Get a Railway subdomain or custom domain

## Step-by-Step Deployment

### 1. GitHub Setup
```bash
# Initialize git if not already done
git init
git add .
git commit -m "Initial commit for Railway deployment"
git branch -M main

# Create GitHub repository and push
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

### 2. Railway Setup
1. Go to [railway.app](https://railway.app)
2. Sign in with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose your repository
6. Railway will auto-deploy

### 3. Add Database
1. In your Railway project, click "New"
2. Select "Database" → "MySQL" (or PostgreSQL)
3. Railway will provide connection details
4. Copy the connection URL

### 4. Configure Environment Variables
In Railway dashboard, go to your WordPress service → Variables:

```bash
# Database (Railway provides these automatically)
DATABASE_URL=mysql://user:pass@host:port/database
DATABASE_NAME=your_database_name
DATABASE_USER=your_database_user
DATABASE_PASSWORD=your_database_password

# Site Factory
SITE_FACTORY_TOKEN=your_secure_token_here
SITE_FACTORY_RATE_LIMIT=10
SITE_FACTORY_RATE_WINDOW=3600

# WordPress
WORDPRESS_DEBUG=false
WORDPRESS_CONFIG_EXTRA="define('WP_MEMORY_LIMIT', '256M');"
```

### 5. Deploy
Railway will automatically redeploy when you push to GitHub!

## Access Your Site

- **Railway URL**: `https://your-project.railway.app`
- **Admin**: `https://your-project.railway.app/wp-admin/network/`

## Custom Domain (Optional)

1. In Railway, go to your service → Settings → Domains
2. Add your custom domain
3. Update DNS records as instructed
4. Railway provides SSL automatically

## Monitoring

- **Logs**: View in Railway dashboard
- **Metrics**: CPU, memory, requests
- **Deployments**: Automatic from GitHub

## Troubleshooting

### Common Issues
- **Build fails**: Check Dockerfile syntax
- **Database connection**: Verify environment variables
- **WordPress not loading**: Check logs in Railway dashboard

### Support
- Railway documentation: [docs.railway.app](https://docs.railway.app)
- Railway Discord: [discord.gg/railway](https://discord.gg/railway)

## Next Steps

After deployment:
1. **Test the Site Factory API**
2. **Deploy the Next.js Landing app** (can use Vercel)
3. **Configure custom domain**
4. **Set up monitoring**

## Cost

- **Free tier**: $5/month credit
- **WordPress + Database**: ~$2-3/month
- **Custom domain**: Free SSL included

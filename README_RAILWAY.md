# 🚀 WordPress Multisite Factory - Railway Deployment

**Deploy your WordPress Multisite Factory to Railway in minutes!**

## 🎯 What This Deploys

- ✅ **WordPress Multisite** with subdomain support
- ✅ **Site Factory Plugin** for creating new sites via API
- ✅ **Production-ready** with SSL and security
- ✅ **Auto-scaling** infrastructure

## 🚀 Quick Deploy

### Option 1: One-Click Deploy
[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/new/template?template=https://github.com/YOUR_USERNAME/YOUR_REPO)

### Option 2: Manual Deploy
1. **Fork this repository** to your GitHub account
2. **Go to [Railway](https://railway.app)** and sign in with GitHub
3. **Click "New Project"** → "Deploy from GitHub repo"
4. **Select your forked repository**
5. **Add MySQL database** in Railway
6. **Configure environment variables**
7. **Deploy!**

## 📋 What You Need

- GitHub account
- Railway account (free)
- 5 minutes of your time

## 🔧 Configuration

After deployment, set these environment variables in Railway:

```bash
# Site Factory Security Token (generate a random one)
SITE_FACTORY_TOKEN=your_secure_token_here

# Rate Limiting
SITE_FACTORY_RATE_LIMIT=10
SITE_FACTORY_RATE_WINDOW=3600
```

## 🌐 Access Your Site

- **Main Site**: `https://your-project.railway.app`
- **Network Admin**: `https://your-project.railway.app/wp-admin/network/`
- **API Endpoint**: `https://your-project.railway.app/wp-json/site-factory/v1/create`

## 🎉 What You Get

- **WordPress Multisite** running on Railway
- **Site Factory API** for creating new sites
- **Subdomain support** (site1.yourdomain.com, site2.yourdomain.com)
- **SSL certificates** automatically
- **Auto-scaling** infrastructure
- **Git-based deployments**

## 📚 Next Steps

1. **Test the Site Factory API**
2. **Deploy the Next.js Landing app** (use Vercel)
3. **Connect them together**
4. **Start creating sites!**

## 💰 Cost

- **Free tier**: $5/month credit
- **WordPress + Database**: ~$2-3/month
- **Custom domain**: Free SSL included

## 🆘 Need Help?

- 📖 [Railway Documentation](https://docs.railway.app)
- 💬 [Railway Discord](https://discord.gg/railway)
- 🐛 [GitHub Issues](https://github.com/YOUR_USERNAME/YOUR_REPO/issues)

---

**Ready to deploy?** Just fork this repo and deploy to Railway! 🚀

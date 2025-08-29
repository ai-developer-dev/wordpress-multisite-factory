#!/bin/bash

# Railway Deployment Script
echo "🚀 Deploying to Railway..."

# Check if git is initialized
if [ ! -d ".git" ]; then
    echo "❌ Git not initialized. Please run:"
    echo "   git init"
    echo "   git add ."
    echo "   git commit -m 'Initial commit'"
    exit 1
fi

# Check if remote origin is set
if ! git remote get-url origin >/dev/null 2>&1; then
    echo "❌ Git remote not set. Please run:"
    echo "   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git"
    exit 1
fi

# Add all files
echo "📁 Adding files to git..."
git add .

# Commit changes
echo "💾 Committing changes..."
git commit -m "Deploy to Railway - $(date)"

# Push to GitHub
echo "🚀 Pushing to GitHub..."
git push origin main

echo "✅ Deployment initiated!"
echo ""
echo "📋 Next steps:"
echo "1. Go to [railway.app](https://railway.app)"
echo "2. Create new project from GitHub repo"
echo "3. Add MySQL database"
echo "4. Configure environment variables"
echo "5. Deploy!"
echo ""
echo "📖 See RAILWAY_DEPLOYMENT.md for detailed instructions"

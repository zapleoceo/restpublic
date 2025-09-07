#!/bin/bash

# North Republic PHP Deployment Script
# Deploys PHP frontend and Node.js backend to server

set -e

echo "🚀 Starting North Republic PHP deployment..."

# Server configuration
SERVER="nr"
SERVER_PATH="/var/www/northrepubli_usr/data/www/northrepublic.me"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📋 Deployment Steps:${NC}"
echo "1. Pull latest code from Git"
echo "2. Install backend dependencies"
echo "3. Check file structure (files already in root)"
echo "4. Set proper permissions"
echo "5. Restart services"

echo -e "\n${YELLOW}🔄 Step 1: Pulling latest code...${NC}"
ssh $SERVER "cd $SERVER_PATH && git pull origin main"

echo -e "\n${YELLOW}📦 Step 2: Installing backend dependencies...${NC}"
ssh $SERVER "cd $SERVER_PATH/backend && npm install"

echo -e "\n${YELLOW}📁 Step 3: Checking file structure...${NC}"
# Check that files are already in root (after cleanup)
ssh $SERVER "cd $SERVER_PATH && if [ ! -f 'index.php' ] || [ ! -f 'menu.php' ] || [ ! -d 'components' ] || [ ! -d 'classes' ]; then echo 'Error: Files not found in root'; exit 1; fi"

echo -e "\n${YELLOW}🔧 Step 4: Setting permissions...${NC}"
ssh $SERVER "cd $SERVER_PATH && chmod -R 755 . && chmod 644 *.php"

echo -e "\n${YELLOW}🔄 Step 5: Restarting Node.js backend...${NC}"
ssh $SERVER "cd $SERVER_PATH/backend && pm2 restart northrepublic-backend || pm2 start server.js --name northrepublic-backend"

echo -e "\n${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🌐 Site: https://northrepublic.me${NC}"
echo -e "${GREEN}📱 API: https://northrepublic.me:3001${NC}"

echo -e "\n${YELLOW}📊 Checking services status...${NC}"
ssh $SERVER "pm2 status"

echo -e "\n${GREEN}🎉 North Republic PHP is now live!${NC}"

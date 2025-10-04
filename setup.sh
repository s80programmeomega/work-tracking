#!/bin/bash

echo "================================="
echo "Work Tracking - Setup Script"
echo "================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if MySQL is running
echo -e "${YELLOW}Checking MySQL connection...${NC}"
if php artisan db:show 2>/dev/null; then
    echo -e "${GREEN}✓ MySQL is running${NC}"
else
    echo -e "${RED}✗ MySQL is not running or not accessible${NC}"
    echo -e "${YELLOW}Please start MySQL and create the database 'Work_Tracking'${NC}"
    echo ""
    echo "Commands to create database:"
    echo "  mysql -u root -p"
    echo "  CREATE DATABASE Work_Tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "  EXIT;"
    echo ""
    exit 1
fi

echo ""
echo -e "${YELLOW}Running migrations...${NC}"
if php artisan migrate; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Seeding roles and permissions...${NC}"
if php artisan db:seed --class=RolePermissionSeeder; then
    echo -e "${GREEN}✓ Database seeded successfully${NC}"
else
    echo -e "${RED}✗ Seeding failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=================================${NC}"
echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${GREEN}=================================${NC}"
echo ""
echo "Test users created:"
echo "  - admin@worktracking.com (password: password) - Super Admin"
echo "  - manager@worktracking.com (password: password) - Manager"
echo "  - cadre@worktracking.com (password: password) - Cadre"
echo ""
echo "To start the application:"
echo "  1. Backend: php artisan serve"
echo "  2. Frontend: npm run dev"
echo ""
echo "Then visit: http://localhost:5173"
echo ""

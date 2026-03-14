#!/bin/bash

# Ensure we are operating in the dashboard directory
if [ ! -d "views" ]; then
    echo "Please run this script from inside the dashboard/ directory."
    exit 1
fi

echo "1/4 Creating the new, simplified directory structure..."
mkdir -p views/layouts views/auth views/pages

echo "2/4 Consolidating Auth and Admin into a single auth folder..."
# Move existing Auth files
mv views/Auth/* views/auth/ 2>/dev/null

# Move Admin files, but safely rename the duplicate reset_password.php first
if [ -f "views/Admin/reset_password.php" ]; then
    mv views/Admin/reset_password.php views/auth/admin_reset_password.php
    echo "  -> Renamed Admin's reset_password.php to admin_reset_password.php to prevent overwriting."
fi
mv views/Admin/* views/auth/ 2>/dev/null

echo "3/4 Moving Pages to lowercase directory and prepping Layouts..."
mv views/Pages/* views/pages/ 2>/dev/null

# Create the empty layout files so they are ready for our manual copy-paste
touch views/layouts/header.php
touch views/layouts/footer.php

echo "4/4 Cleaning up old directories..."
rmdir views/Auth views/Admin views/Pages 2>/dev/null

echo "✅ Done! Your folder structure is now flat and simplified."
#!/bin/bash

# Ensure we are operating in the dashboard directory
if [ ! -f "config.php" ]; then
    echo "Please run this script from inside the dashboard/ directory."
    exit 1
fi

echo "1/4 Creating new directory structure..."
mkdir -p config controllers/Api controllers/Actions views/Auth views/Pages views/Admin views/Legal assets/css assets/js assets/img utils vendor

echo "2/4 Moving files to their new modular homes..."
# Config & Utils
mv config.php config/ 2>/dev/null
mv hash.php utils/ 2>/dev/null

# Vendor
mv PHPMailer vendor/ 2>/dev/null

# Assets
mv style.css assets/css/ 2>/dev/null
mv script.js assets/js/ 2>/dev/null
mv ../img/* assets/img/ 2>/dev/null
mv img/* assets/img/ 2>/dev/null

# Controllers
mv *_api.php controllers/Api/ 2>/dev/null
mv get_*.php controllers/Api/ 2>/dev/null
mv search_bins.php controllers/Api/ 2>/dev/null

mv add_*.php controllers/Actions/ 2>/dev/null
mv edit_*.php controllers/Actions/ 2>/dev/null
mv delete_*.php controllers/Actions/ 2>/dev/null
mv update_*.php controllers/Actions/ 2>/dev/null
mv mark_*.php controllers/Actions/ 2>/dev/null
mv verify_*.php controllers/Actions/ 2>/dev/null

# Views
mv login.php register.php logout.php reset_password.php views/Auth/ 2>/dev/null
mv Admin/* views/Admin/ 2>/dev/null
mv Legal/* views/Legal/ 2>/dev/null
mv index.php bin.php history.php notifications.php schedules.php user.php user_status.php views/Pages/ 2>/dev/null

# Cleanup
rmdir Admin Legal img ../img 2>/dev/null

echo "3/4 Automating Code Replacements (Imports, Links, Scripts, Forms)..."

# A. Standardize Database Config Imports
# This catches include, require, require_once, and __DIR__ variations and points them 2 levels up.
find views controllers -type f -name "*.php" -exec perl -pi -e "s/(require|include)(_once)?\s*\(?.*?config\.php['\"]?\)?\s*;/require_once __DIR__ \. '\/..\/..\/config\/config.php';/g" {} +

# B. Update CSS and JS Links
# Finds href="style.css" or href="./style.css" and points them to the assets folder
find views -type f -name "*.php" -exec perl -pi -e 's/href="[^"]*style\.css"/href="..\/..\/assets\/css\/style.css"/g' {} +
find views -type f -name "*.php" -exec perl -pi -e 's/src="[^"]*script\.js"/src="..\/..\/assets\/js\/script.js"/g' {} +

# C. Fix Image Paths
# Catches messy paths like src="../img/pdm logo.jfif" or src="img/people.png"
find views -type f -name "*.php" -exec perl -pi -e 's/src="\.\.\/img\//src="..\/..\/assets\/img\//g' {} +
find views -type f -name "*.php" -exec perl -pi -e 's/src="img\//src="..\/..\/assets\/img\//g' {} +

# D. Update Form Actions & AJAX endpoints
# Routes *_api.php actions to the Api controller folder
find views assets/js -type f \( -name "*.php" -o -name "*.js" \) -exec perl -pi -e 's/(action|url|fetch)\s*=\s*"([^"]*_api\.php)"/$1="..\/..\/controllers\/Api\/$2"/g' {} +
# Routes operational scripts (add, edit, delete, update) to the Actions controller folder
find views assets/js -type f \( -name "*.php" -o -name "*.js" \) -exec perl -pi -e 's/(action|url)\s*=\s*"((add|edit|delete|update|mark|verify)_[^"]*\.php)"/$1="..\/..\/controllers\/Actions\/$2"/g' {} +

echo "4/4 Refactoring complete! Your project is now structurally organized."

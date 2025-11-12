# 📦 Installing CubeWikiPackage Locally

## Method 1: Local Path Repository (Recommended for Development)

### Step 1: Update composer.json in your project

Add this to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/Users/danielterpstra/Herd/CubeWikiPackage",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "terpdev/cubewikipackage": "@dev"
    }
}
```

### Step 2: Install the package

```bash
cd /Users/danielterpstra/Herd/Filament
composer require terpdev/cubewikipackage:@dev
```

The `@dev` tells Composer to accept the development version.

---

## Method 2: Direct Install Commands

Run these commands in your project:

```bash
cd /Users/danielterpstra/Herd/Filament

# Add the local repository
composer config repositories.cubewikipackage path /Users/danielterpstra/Herd/CubeWikiPackage

# Require with dev stability
composer require terpdev/cubewikipackage:@dev
```

---

## Step 3: Register the Plugin in Filament

After installation, register the plugin in your Filament panel:

```php
// app/Providers/Filament/AdminPanelProvider.php (or your panel provider)

use TerpDev\CubeWikiPackage\CubeWikiPackagePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        // ... other config ...
        ->plugins([
            CubeWikiPackagePlugin::make(),
        ]);
}
```

---

## Step 4: Publish Config (Optional)

```bash
php artisan vendor:publish --tag="cubewikipackage-config"
```

Then update your `.env`:

```env
WIKICUBE_API_URL=http://wikicube.test
WIKICUBE_CACHE_DURATION=5
```

---

## Step 5: Clear Cache and Test

```bash
php artisan config:clear
php artisan view:clear
php artisan Filament:cache-components
```

Now visit your Filament admin panel and look for "Knowledge Base" in the navigation!

---

## 🌐 Publishing to Packagist (For Public Distribution)

If you want others to install your package via Composer:

### 1. Push to GitHub

```bash
cd /Users/danielterpstra/Herd/CubeWikiPackage
git add .
git commit -m "Initial release"
git tag v1.0.0
git push origin main
git push --tags
```

### 2. Submit to Packagist

1. Go to https://packagist.org
2. Click "Submit"
3. Enter your GitHub repo URL: `https://github.com/terpdev/cubewikipackage`
4. Click "Check"
5. Packagist will auto-register your package

### 3. After Publishing

Users can then install with:

```bash
composer require terpdev/cubewikipackage
```

No `@dev` needed! But you need to tag releases:

```bash
# For each new version
git tag v1.0.1
git push --tags
```

---

## 🐛 Troubleshooting

### Error: "Could not find package"

```bash
# Make sure the package path is correct
ls -la /Users/danielterpstra/Herd/CubeWikiPackage/composer.json

# Verify package name matches
cat /Users/danielterpstra/Herd/CubeWikiPackage/composer.json | grep '"name"'
```

### Error: "minimum-stability"

Add to your project's `composer.json`:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### Error: Filament version conflict

If you see: "conflicts with your root composer.json require (^4.0)"

The package composer.json needs to support both Filament v3 and v4:

```bash
# Update the package's composer.json
cp /Users/danielterpstra/Herd/wikicube/PACKAGE_FILES/composer.json /Users/danielterpstra/Herd/CubeWikiPackage/composer.json

# Then try installing again
cd /Users/danielterpstra/Herd/Filament
composer require terpdev/cubewikipackage:@dev
```

### Package not showing in Filament

```bash
# Clear all caches
php artisan optimize:clear
php artisan Filament:cache-components

# Check if plugin is registered
php artisan Filament:list
```

---

## 📝 Quick Start Script

Save this as `install-cubewiki.sh`:

```bash
#!/bin/bash

echo "🚀 Installing CubeWikiPackage..."

cd /Users/danielterpstra/Herd/Filament

# Add repository
composer config repositories.cubewikipackage path /Users/danielterpstra/Herd/CubeWikiPackage

# Install package
composer require terpdev/cubewikipackage:@dev

# Clear caches
php artisan config:clear
php artisan view:clear
php artisan Filament:cache-components

echo "✅ Installation complete!"
echo ""
echo "Next steps:"
echo "1. Register the plugin in your AdminPanelProvider"
echo "2. Add this to app/Providers/Filament/AdminPanelProvider.php:"
echo ""
echo "   ->plugins(["
echo "       \\TerpDev\\CubeWikiPackage\\CubeWikiPackagePlugin::make(),"
echo "   ])"
echo ""
echo "3. Visit your Filament admin panel"
echo "4. Click 'Knowledge Base' in the navigation"
echo "5. Enter token: g2yxUxa3T0HUQP5185IjI4uqAiqHh40lved67tUF728e557c"
```

Make it executable and run:

```bash
chmod +x install-cubewiki.sh
./install-cubewiki.sh
```


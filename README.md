# Collectable Toys Management System

A Laravel-based application to manage your collectable toys collection.

## Features

- ✅ Add, edit, and delete toys from your collection
- ✅ Track toy details including name, brand, condition, purchase date, and value
- ✅ Search and filter your collection by name, brand, category, and condition
- ✅ Upload images for each toy
- ✅ **eBay Integration**: Search eBay for current listings and average prices
- ✅ Beautiful, modern UI built with Tailwind CSS
- ✅ Responsive design that works on all devices

## Requirements

- PHP 8.1 or higher
- Composer
- Node.js and npm
- SQLite (default) or MySQL/PostgreSQL

## Installation

1. **Install PHP dependencies:**
```bash
composer install
```

2. **Install Node.js dependencies:**
```bash
npm install
```

3. **Copy environment file:**
```bash
cp .env.example .env
```

4. **Generate application key:**
```bash
php artisan key:generate
```

5. **Create database (SQLite by default):**
```bash
touch database/database.sqlite
```

6. **Run migrations and seed sample data:**
```bash
php artisan migrate --seed
```

7. **Create storage link for images:**
```bash
php artisan storage:link
```

8. **Start the development server:**
```bash
php artisan serve
```

9. **Build assets (in another terminal):**
```bash
npm run dev
```

Visit `http://localhost:8000` to view the application.

## Database

The application uses SQLite by default. To use MySQL or PostgreSQL, update the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Usage

- **View Collection**: Navigate to the home page to see all your toys
- **Add Toy**: Click "Add New Toy" to add a new collectable to your collection
- **Search**: Use the search bar to find toys by name, brand, or description
- **Filter**: Filter by category or condition using the dropdown menus
- **Edit/Delete**: Click "View" on any toy to see details, edit, or delete it
- **eBay Search**: Click "Search eBay" on any toy's detail page to find current listings and average prices

## eBay Integration

The application includes eBay search functionality to help you track current market prices for your collectables.

### Setting Up eBay API

1. **Get an eBay App ID**:
   - Go to [eBay Developers Program](https://developer.ebay.com/)
   - Sign in with your eBay account
   - Navigate to "My Account" → "Keys"
   - Create a new app or use an existing one
   - Copy your **App ID (Client ID)**

2. **Add to .env file**:
   ```env
   EBAY_APP_ID=YourAppIdHere
   EBAY_SANDBOX=true  # Set to true if using sandbox credentials, false for production
   ```
   
   **Note**: If your App ID contains "SBX" (sandbox), set `EBAY_SANDBOX=true`. For production App IDs, set `EBAY_SANDBOX=false` or omit it.

3. **Search eBay**:
   - Navigate to any toy's detail page
   - Click the "Search eBay" button
   - The system will search for similar items and display:
     - Number of listings found
     - Average current price
     - Last search timestamp

**Note**: eBay API has rate limits. Be mindful of how frequently you search.

## Project Structure

- `app/Models/Toy.php` - Toy model
- `app/Http/Controllers/ToyController.php` - Main controller for CRUD operations
- `app/Services/EbayService.php` - eBay API integration service
- `database/migrations/` - Database migrations
- `resources/views/toys/` - Blade templates for toy views
- `routes/web.php` - Application routes


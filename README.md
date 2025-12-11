# Limit-Order Exchange Mini Engine

A full-stack cryptocurrency exchange application built with Laravel and Vue.js, featuring real-time order matching, limit orders, and live balance updates via Pusher.
![](https://raw.githubusercontent.com/Alkiyogoma/limit-order-exchange/refs/heads/main/public/dashboard.png)

![](https://raw.githubusercontent.com/Alkiyogoma/limit-order-exchange/refs/heads/main/public/order.png)

## Project Overview

This application implements a simplified cryptocurrency exchange with the following features:

- **Limit Order Trading**: Buy and sell BTC and ETH with limit orders
- **Order Matching Engine**: Automatic full-match order execution
- **Real-Time Updates**: Live balance and order updates via Pusher broadcasting
- **Commission System**: 1.5% commission on all trades
- **Atomic Transactions**: Race-condition-safe balance and asset management
- **Modern UI**: Vue.js 3 Composition API with Tailwind CSS

---

## Technology Stack

### Backend
- **Laravel 12.x** - PHP framework
- **MySQL/PostgreSQL** - Database
- **Laravel Broadcasting** - Real-time events
- **Pusher** - WebSocket service

### Frontend
- **Vue.js 3** - JavaScript framework (Composition API)
- **Inertia.js** - SPA without building an API
- **Tailwind CSS** - Utility-first CSS framework
- **Laravel Echo** - WebSocket client
- **Axios** - HTTP client

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2
- **Composer** >= 2.6
- **Node.js** >= 18.x
- **npm** >= 9.x
- **MySQL** >= 8.0 or **PostgreSQL** >= 14
- **Git**

---

## Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/Alkiyogoma/limit-order-exchange.git
cd limit-order-exchange
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install JavaScript Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Configure Database

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exchange_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 6. Configure Pusher

Create a free account at [pusher.com](https://pusher.com) and add credentials to `.env`:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 7. Run Database Migrations

```bash
php artisan migrate
```

### 8. Seed Database (Optional)

```bash
php artisan db:seed
```

This will create:
- Test users with initial USD balance
- Sample BTC and ETH assets
- Demo orders for testing

### 9. Build Frontend Assets

**For development:**
```bash
npm run dev
```

**For production:**
```bash
npm run build
```

### 10. Start the Application

**Option A: Using Laravel's built-in server**
```bash
php artisan serve
```

**Option B: Using Laravel Valet (Mac/Linux)**
```bash
valet link
valet secure exchange
```

**Option C: Using Docker (if configured)**
```bash
./vendor/bin/sail up
```

### 11. Start Queue Worker (Important for Broadcasting)

In a separate terminal:

```bash
php artisan queue:work
```

Or for development with auto-reload:

```bash
php artisan queue:listen
```

### 12. Access the Application

Open your browser and navigate to:
- **Local Server**: http://localhost:8000

---

## Default Test Accounts

After seeding, you can use these test accounts:

**User 1:**
- Email: `trader1@example.com`
- Password: `password`
- Balance: $50,000
- Assets: 2 BTC, 50 ETH

**User 2:**
- Email: `trader2@example.com`
- Password: `password`
- Balance: $50,000
- Assets: 3 BTC, 100 ETH

---

## How to Use

### 1. Register/Login
- Create a new account or use test accounts
- Initial balance: $49,629 USD (after seeding)

### 2. View Wallet & Orders
- Dashboard shows USD balance and crypto assets
- View orderbook for BTC/ETH
- See all your orders (open, filled, cancelled)

### 3. Place a Limit Order

**Via Modal (Recommended):**
1. Click "NEW ORDER" button on dashboard
2. Select symbol (BTC/ETH)
3. Choose side (Buy/Sell)
4. Enter price in USD
5. Enter amount
6. Review total cost and 1.5% commission
7. Click "Place Buy/Sell Order"

**Order Requirements:**
- **Buy Order**: Requires sufficient USD balance
- **Sell Order**: Requires sufficient crypto assets
- Commission: 1.5% deducted from seller's proceeds

### 4. Order Matching

Orders are matched automatically when:
- **Buy order** finds a sell order where `sell_price <= buy_price`
- **Sell order** finds a buy order where `buy_price >= sell_price`
- **Amount must match exactly** (full match only, no partial fills)

### 5. Real-Time Updates

When a trade executes:
- ✅ Both users receive instant notifications
- ✅ Balances update automatically
- ✅ Order status changes to "Filled"
- ✅ Assets transfer to buyer

### 6. Cancel Orders

- Click "Cancel" on any open order
- Locked funds/assets are released immediately
- Order status changes to "Cancelled"

---

## 📁 Project Structure

```
limit-order-exchange/
├── app/
│   ├── Events/
│   │   └── OrderMatched.php          # Broadcast event for trades
│   ├── Exceptions/
│   │   ├── InsufficientBalanceException.php
│   │   └── InsufficientAssetException.php
│   ├── Http/
│   │   ├── Controllers/Api
│   │   │   ├── OrderController.php   # Order API endpoints
│   │   │   └── ProfileController.php # User profile
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php                  # User with balance
│   │   ├── Asset.php                 # User assets (BTC/ETH)
│   │   ├── Order.php                 # Buy/sell orders
│   │   └── Trade.php                 # Executed trades
│   └── Services/
│       └── OrderService.php          # Core business logic
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_assets_table.php
│   │   ├── create_orders_table.php
│   │   └── create_trades_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   └── OrderFormModal.vue    # Order placement modal
│   │   ├── Pages/
│   │   │   └── Dashboard.vue         # Main dashboard
│   │   └── app.js
│   └── views/
├── routes/
│   ├── api.php                       # API routes
│   └── web.php                       # Web routes
└── tests/
    ├── Feature/
    └── Unit/
```

## Troubleshooting

### Issue: Real-time updates not working

**Solution:**
1. Verify Pusher credentials in `.env`
2. Ensure queue worker is running: `php artisan queue:work`
3. Check browser console for WebSocket errors
4. Verify `BROADCAST_DRIVER=pusher` in `.env`

### Issue: Orders not matching

**Solution:**
1. Ensure amounts match exactly (full match only)
2. Verify price conditions are met
3. Check order status (must be open)
4. Review `storage/logs/laravel.log` for errors

### Issue: "Insufficient balance" error

**Solution:**
1. Check user balance: `SELECT * FROM users WHERE id = X`
2. Verify locked amount calculation
3. Ensure previous orders were cancelled/filled properly

### Issue: Database connection error

**Solution:**
1. Verify database is running
2. Check `.env` database credentials
3. Run `php artisan config:clear`
4. Test connection: `php artisan db:show`

---

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User name
- `email` - Unique email
- `password` - Hashed password
- `balance` - USD balance (decimal 20,8)

### Assets Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `symbol` - Asset symbol (BTC/ETH)
- `amount` - Available amount (decimal 20,8)
- `locked_amount` - Locked for orders (decimal 20,8)

### Orders Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `symbol` - Trading pair symbol
- `side` - 'buy' or 'sell'
- `price` - Limit price (decimal 20,8)
- `amount` - Order amount (decimal 20,8)
- `status` - 1=open, 2=filled, 3=cancelled

### Trades Table (Optional)
- `id` - Primary key
- `buy_order_id` - Foreign key to orders
- `sell_order_id` - Foreign key to orders
- `buyer_id` - Foreign key to users
- `seller_id` - Foreign key to users
- `symbol` - Traded symbol
- `price` - Execution price (decimal 20,8)
- `amount` - Traded amount (decimal 20,8)
- `volume` - Total value (decimal 20,8)
- `commission` - Fee charged (decimal 20,8)

---

## Security Features

- ✅ CSRF protection on all forms
- ✅ SQL injection prevention via Eloquent ORM
- ✅ XSS protection in Vue templates
- ✅ Password hashing with bcrypt
- ✅ Database transaction atomicity
- ✅ Input validation on all API endpoints
- ✅ Authorization checks for order cancellation
- ✅ Private channel authentication for WebSockets

---

## Deployment Notes

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure production database
- [ ] Set up Redis for queue driver
- [ ] Configure supervisor for queue workers
- [ ] Enable HTTPS
- [ ] Set up daily backups
- [ ] Configure error monitoring (Sentry, Bugsnag)
- [ ] Optimize autoloader: `composer install --optimize-autoloader --no-dev`
- [ ] Build production assets: `npm run build`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`

---

## API Documentation

### Endpoints

#### GET `/api/profile`
Returns authenticated user's profile with balance and assets.

**Response:**
```json
{
  "id": 1,
  "name": "Bob Buyer",
  "email": "trader2@example.com",
  "balance": "49629.00000000",
  "assets": [
    {
      "symbol": "BTC",
      "amount": "4.00000000",
      "locked_amount": "1.00000000"
    }
  ]
}
```

#### GET `/api/orders?symbol=BTC`
Returns orderbook for specified symbol.

**Response:**
```json
{
  "symbol": "BTC",
  "buys": [...],
  "sells": [...]
}
```

#### POST `/api/orders`
Creates a new limit order.

**Request:**
```json
{
  "symbol": "BTC",
  "side": "buy",
  "price": "23000.00",
  "amount": "0.5"
}
```

#### POST `/api/orders/{id}/cancel`
Cancels an open order.

**Response:**
```json
{
  "message": "Order cancelled successfully"
}
```

---

## Development Team

- **Developer**: [Albogast Kiyogoma]
- **Assessment Date**: December 2025
- **Completion Time**: 48 hours

---

## License

This project is developed as part of a technical assessment and is for evaluation purposes only.

---

## Acknowledgments

- Laravel Documentation
- Vue.js Documentation
- Pusher Documentation
- Tailwind CSS
- The Laravel and Vue.js communities
- VirgoSoft Team

---

## 📧 Support

For questions or issues regarding this submission, please contact:
- **Email**: [albogasty@gmail.com]
- **GitHub**: [Alkiyogoma]

---

**Note**: This application is built for educational and assessment purposes. It should not be used in production without proper security audits, extensive testing, and compliance with financial regulations.

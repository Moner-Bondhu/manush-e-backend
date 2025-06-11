# Manush-E Backend

This is the backend API service built with Laravel for Manush-E. It powers the core logic and data operations for the application.

## 🚀 Requirements

-   PHP >= 8.1
-   Composer
-   MySQL or PostgreSQL
-   Laravel 10+

## 🛠 Installation

1. **Clone the Repository**

    ```bash
    git clone git@github.com:Moner-Bondhu/manush-e-backend.git
    cd manush-e-backend
    ```

2. **Install PHP Dependencies**

    ```bash
    composer install
    ```

3. **Copy Environment File**

    ```bash
    cp .env.example .env
    ```

4. **Set your DB credentials and other settings in the .env file.**

5. **Generate App Key**

    ```bash
    php artisan key:generate
    ```

6. **Run Migrations**

    ```bash
    php artisan migrate
    ```

## ▶️ Running the App

1. **Start the local development server:**

    ```bash
    php artisan serve
    ```

2. **Visit http://localhost:8000**

## ✅ Running Tests

Laravel uses PHPUnit by default. To run tests:

    ```bash
    php artisan test
    ```

You can also run with:

    ```bash
    vendor/bin/phpunit
    ```

## 🧪 Test Coverage (Optional)

To view test coverage (requires xdebug or phpdbg):

    ```bash
    phpdbg -qrr ./vendor/bin/phpunit --coverage-html coverage
    ```

## 📄 License

[GPL V3](COPYING)

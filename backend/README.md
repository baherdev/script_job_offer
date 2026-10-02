# Job offer backend

Symfony 7.4 application. PHP dependencies are installed with Composer.

## Requirements

- PHP 8.2 or newer, with the `ctype`, `iconv`, and `pdo_mysql` extensions
- [Composer](https://getcomposer.org/) 2
- Docker, for the MySQL 8 database

## Install

From the `backend` directory:

```bash
composer install
```

`composer install` reads `composer.lock` and installs the packages under `vendor/`. The Flex plugins allowed by this project (`symfony/flex` and `symfony/runtime`) run as part of that install.

`bin/` is gitignored, so a fresh checkout has no `bin/console`. Symfony's install scripts call that file. If `composer install` stops because `bin/console` is missing, create it and run the scripts again:

```bash
mkdir -p bin
curl -fsSL https://raw.githubusercontent.com/symfony/recipes/main/symfony/console/5.3/bin/console -o bin/console
chmod +x bin/console
composer run-script auto-scripts
```

## Environment

`.env`, `.env.local`, and `.env.test` are gitignored. Symfony still requires a `.env` file. Create one in `backend`:

```dotenv
APP_ENV=dev
APP_SECRET=
DATABASE_URL="mysql://root:password@127.0.0.1:3306/main?serverVersion=8&charset=utf8mb4"
```

Generate a secret and put the real database URL in `.env.local`, which overrides `.env` and is not committed:

```bash
php -r "echo 'APP_SECRET='.bin2hex(random_bytes(16)).PHP_EOL;" >> .env.local
```

```dotenv
DATABASE_URL="mysql://root:password@127.0.0.1:3306/main?serverVersion=8&charset=utf8mb4"
```

The URL above matches `docker-compose.yaml`: user `root`, password `password`, database `main`, port `3306`.

For tests, copy the same `DATABASE_URL` into `.env.test`. The test environment appends `_test` to the database name, so PHPUnit uses `main_test` and does not write to `main`.

## Database

Start MySQL, then apply the migrations:

```bash
docker compose up -d
php bin/console doctrine:migrations:migrate
```

`docker compose start` only restarts containers that already exist. Use `docker compose up -d` the first time.

## Run

```bash
php -S 127.0.0.1:8000 -t public
```

The API is served from that address. Examples: `/api/contacts`, `/api/entreprises`, `/api/file-references`, and `/api/job-offers`.

Run the integration tests with:

```bash
php bin/phpunit
```

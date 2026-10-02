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

The API is served from that address. Send `Content-Type: application/json` on `POST` and `PUT`. A missing or blank required field returns `422`.

| Status | Meaning |
|---|---|
| `200` | List, read, or update succeeded |
| `201` | Create succeeded |
| `204` | Delete succeeded, with an empty body |
| `404` | The id does not exist |
| `409` | Delete refused because a job offer still references the entreprise |
| `422` | The JSON body is invalid |

### Contacts

`/api/contacts`

| Method | Path | Action |
|---|---|---|
| `GET` | `/api/contacts` | List every contact |
| `GET` | `/api/contacts/{id}` | Read one contact |
| `POST` | `/api/contacts` | Create a contact |
| `PUT` | `/api/contacts/{id}` | Replace the contact fields |
| `DELETE` | `/api/contacts/{id}` | Delete the contact and detach it from its job offers |

```json
{
  "name": "Ada Lovelace",
  "address": "1 Main Street",
  "email": "ada@example.com"
}
```

`name`, `address`, and `email` are required. A stored contact adds `id`:

```json
{
  "id": 1,
  "name": "Ada Lovelace",
  "address": "1 Main Street",
  "email": "ada@example.com"
}
```

### Entreprises

`/api/entreprises`

| Method | Path | Action |
|---|---|---|
| `GET` | `/api/entreprises` | List every entreprise |
| `GET` | `/api/entreprises/{id}` | Read one entreprise |
| `POST` | `/api/entreprises` | Create an entreprise |
| `PUT` | `/api/entreprises/{id}` | Replace the entreprise fields |
| `DELETE` | `/api/entreprises/{id}` | Delete the entreprise |

```json
{
  "label": "Acme",
  "address": "1 Main Street",
  "website": "https://acme.example",
  "isBlacklisted": false
}
```

`label`, `address`, and `website` are required. `label` and `website` are limited to 255 characters. `isBlacklisted` defaults to `false` when it is omitted, including on update. Delete returns `409` while any job offer still references the entreprise.

```json
{
  "id": 1,
  "label": "Acme",
  "address": "1 Main Street",
  "website": "https://acme.example",
  "isBlacklisted": false
}
```

### File references

`/api/file-references`

| Method | Path | Action |
|---|---|---|
| `GET` | `/api/file-references` | List every file reference |
| `GET` | `/api/file-references/{id}` | Read one file reference |
| `POST` | `/api/file-references` | Create a file reference |
| `PUT` | `/api/file-references/{id}` | Replace the file reference fields |
| `DELETE` | `/api/file-references/{id}` | Delete the file reference and detach it from its job offers |

```json
{
  "fileType": 1,
  "fileName": "cv.pdf",
  "fileVersion": "1",
  "filePath": "/files/cv.pdf",
  "isActif": true
}
```

`fileType`, `fileName`, `fileVersion`, and `filePath` are required. `isActif` defaults to `true` when it is omitted, including on update. The server sets `creationDate`.

```json
{
  "id": 1,
  "fileType": 1,
  "fileName": "cv.pdf",
  "fileVersion": "1",
  "filePath": "/files/cv.pdf",
  "creationDate": "2026-10-02T20:14:00+02:00",
  "isActif": true
}
```

### Job offers

`/api/job-offers`

| Method | Path | Action |
|---|---|---|
| `GET` | `/api/job-offers` | List every job offer |
| `GET` | `/api/job-offers/{id}` | Read one job offer |
| `POST` | `/api/job-offers` | Create a job offer |
| `PUT` | `/api/job-offers/{id}` | Replace the job offer fields |
| `DELETE` | `/api/job-offers/{id}` | Delete the job offer and detach its contacts and file references |

A job offer must reference an existing entreprise. An unknown `entrepriseId` returns `404`. Dates use `YYYY-MM-DD`. Omitted optional fields are stored as empty, including on update.

```json
{
  "entrepriseId": 1,
  "sourceId": 42,
  "originalLink": "https://example.com/jobs/42",
  "publicationDate": "2026-10-02",
  "minimumSalary": 45000,
  "maximumSalary": 55000,
  "location": "Lyon",
  "presenceMode": 1,
  "positionTitle": "Backend developer",
  "description": "Symfony API work",
  "applicationStatus": 0,
  "applicationDate": null,
  "rejectionDate": null,
  "interviewDate1": null,
  "interviewDate2": null,
  "interviewDate3": null,
  "applicationValidationDate": null,
  "cancellationDate": null,
  "coverLetter": null,
  "desiredSalary": null,
  "availabilityDate": null
}
```

Required fields are `entrepriseId`, `sourceId`, `originalLink`, `publicationDate`, `minimumSalary`, `maximumSalary`, `location`, `presenceMode`, `positionTitle`, `description`, and `applicationStatus`. The response returns those fields plus `id`. The entreprise itself is not deleted with the offer.

## Tests

Run the integration tests with:

```bash
php bin/phpunit
```

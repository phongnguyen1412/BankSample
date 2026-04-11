# Bank Import Project

This project is a banking transaction import built with Laravel, MariaDB, and RabbitMQ.  

## Key Libraries and Frameworks

- `Laravel`: the main PHP framework for routing, validation, database access, and API responses.

- `Laravel Sanctum`: plugin authentication for both admin and customer APIs.

- `vladimir-yuldashev/laravel-queue-rabbitmq`: connects RabbitMQ to the normal Laravel queue system.

- `MariaDB`: stores data.

- `Laravel Queue`: background service.


## Principles, Patterns
- This project uses the following patterns:
1. It uses Laravel MVC structure. Controllers receive requests, and models handle database data.
2. Single responsibility: each class only do one job.  
3. Repository pattern: Each class in Repository mainly handles insert logic, duplicate checking, and getting data.
4. Mapper pattern: converts raw data from CSV file into correct application data before import
5. Queue-based processing: the API Import only validates and stores the file. The actual import process to the database is handled later by the Laravel queue worker.
6. Chunk processing: since the file can be more than millions of records, it will read one by one and process in chunks. This helps the system handle large data without loading the whole file into memory. 
7. Authentication: the import API is protected by Laravel Sanctum using Bearer token authentication. So it will prevent unauthorized users from importing invalid data

## Infrastructure Diagram
![Infrastructure](docs/component-infrastructure-diagram.jpg)

This project has two backend services:

- `Admin Import API Service` : handles admin login, CSV upload, file validation, local file storage, queue status, and dispatching import jobs to RabbitMQ.

- `Laravel Queue Worker Service`: handles background import processing from RabbitMQ, reads CSV files, and writes transaction data into the database.

Inter-service communication:
- The admin import API service dispatches import jobs to RabbitMQ.
- The Laravel queue worker service consumes the jobs from RabbitMQ and processes the import data.

## Folder structure
![Folder Structure](docs/folder-structure.jpg)


1. `app/Http/Controllers/Api/`:
- `Admin` contains the API controller. For the Admin folder, it includes controller `AuthController` to get token and `Customer\TransactionController` to get customer transaction for admin.
- `Customer` contains `LoginController` to get token, `RegisterController` to create customer data, and `TransactionController` to get current customer transaction
2. `app/Http/Requests/`
-   `CurrentCustomerTransactionRequest` : validates required input for api `api/customer/transaction/`
-   `CustomerLoginRequest` : validates required input for api `api/customer/login/`
-   `CustomerRegisterRequest` : validates required input for api `api/customer/register/`
-   `CustomerTransactionRequest` : validates required input for api `api/admin/customer-transactions/`
3. `app/Models/`
-  These are models that hold project tables such as customer, transaction record, queue status, import error, and user.
4. `app/Repositories/`
-  `CustomerRepository` manages logic for customer
- `TransactionRecordRepository` manages logic for transaction data.
5. `app/Services/`
- `CsvImportService`: handles transaction import from CSV file.
- `TransactionRecordCsvDataMapper`: is used to map data between CSV and DB data
6. `app/Jobs/`
- `ImportCsvJob`: Laravel queue job to process the imported CSV file in RabbitMQ worker
7. `app/Support/`: helper classes for import result data.
8. `config/`
- project config such as import settings, auth setting, and queue settings.
9. `database/migrations/`
- migration files to create the tables.
10. `routes/`
- `api`: the API route definitions.
11. `tests/Unit`: unit test file

## Import Flow

![API Import Flow](docs/import-flow.jpg)

## Without Docker

Before starting without Docker, make sure these are already installed:

- PHP 8.4
- Composer
- MariaDB
- RabbitMQ

Then run:

```bash
cp .env.example .env
composer install
php artisan key:generate --force
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
php artisan rabbitmq:queue-declare csv-imports rabbitmq
```

Open another terminal for the queue worker:

```bash
php artisan queue:work rabbitmq --queue=csv-imports --tries=1
```

Run unit tests:

```bash
php artisan test
```

## Docker Guide

If you want to run with Docker, use:

```bash
cp .env.example .env
docker compose run --rm composer install
docker compose up -d
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
```

Main values to check in `.env.example`:

- `APP_URL=`

Admin account:
- `ADMIN_EMAIL=`
- `ADMIN_PASSWORD=`

DB:
- `DB_HOST=`
- `DB_PORT=`
- `DB_DATABASE=`
- `DB_USERNAME=`
- `DB_PASSWORD=`

Queue:
- `QUEUE_CONNECTION=`
- `CACHE_STORE=`

RabbitMQ:
- `RABBITMQ_HOST=`
- `RABBITMQ_PORT=`
- `RABBITMQ_USER=`
- `RABBITMQ_PASSWORD=`
- `RABBITMQ_QUEUE=`
- `RABBITMQ_WORKER=`

## Test Data Instruction

Before importing CSV, make sure the `customer` table already has customer records with `email` and `name`.  
For the sample test accounts, the customer password is `123456`.

You can create sample data with:

```bash
php artisan testing:create-users
```

If using Docker:

```bash
docker compose exec app php artisan testing:create-users
```

Run unit tests:

```bash
docker compose exec app php artisan test
```


This command creates these sample customer emails:

- `customer@example.com`
- `customer1@example.com`
- `customer2@example.com`
- ...
- `customer10@example.com`

## Docker Test URLs

After Docker is up, you can open these URLs:

- App: `http://127.0.0.1:8000`
- Swagger UI: `http://127.0.0.1:8000/swagger-ui.html`
- Swagger YAML: `http://127.0.0.1:8000/swagger.yaml`
- Admin Login API: `http://127.0.0.1:8000/api/admin/login`
- Admin Import API: `http://127.0.0.1:8000/api/admin/imports`
- Admin Customer Transactions API: `http://127.0.0.1:8000/api/admin/customer-transactions`
- Customer Register API: `http://127.0.0.1:8000/api/customer/register`
- Customer Login API: `http://127.0.0.1:8000/api/customer/login`
- Customer Transaction API: `http://127.0.0.1:8000/api/customer/transaction`
- Adminer: `http://127.0.0.1:8081`
- RabbitMQ UI: `http://127.0.0.1:15672`

Default values:

- API email: `admin@example.com`
- API password: `admin123`
- RabbitMQ username: `guest`
- RabbitMQ password: `guest`
- Adminer system: `MariaDB`
- Adminer server: `mariadb`
- Adminer username: `root`
- Adminer password: `123456`
- Adminer database: `bank_import`


## Test With Curl

Step 1: admin login and get Bearer token:

```bash
curl -X 'POST' \
  'https://your-domain.com/api/admin/login' \
  -H 'accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "email": "admin@example.com",
  "password": "admin123"
}'
```

Step 2: use the admin token to call import API:

```bash
curl -X 'POST' \
  'https://your-domain.com/api/admin/imports' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -F 'file=@YOUR_FILE_PATH'
```

Step 3: use the admin token to get transactions for any customer:

```bash
curl -X 'GET' \
  'https://your-domain.com/api/admin/customer-transactions?email=customer1@example.com&page=1&per_page=10' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE'
```

Step 4: customer register:

```bash
curl -X 'POST' \
  'https://your-domain.com/api/customer/register' \
  -H 'accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "name": "Customer Demo",
  "email": "customer@example.com",
  "password": "123456"
}'
```

Step 5: customer login:

```bash
curl -X 'POST' \
  'https://your-domain.com/api/customer/login' \
  -H 'accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "email": "customer@example.com",
  "password": "123456"
}'
```

Step 6: customer gets only current customer transactions:

```bash
curl -X 'GET' \
  'https://your-domain.com/api/customer/transaction?page=1&per_page=10' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_CUSTOMER_TOKEN_HERE'
```

## Demo Link

- Demo link: [https://banksample.duckdns.org/](https://banksample.duckdns.org/)
- Swagger UI: [https://banksample.duckdns.org/swagger-ui.html](https://banksample.duckdns.org/swagger-ui.html)
- Adminer: [https://banksample.duckdns.org/adminer](https://banksample.duckdns.org/adminer)

Adminer login:

- System: `MariaDB`
- Server: `mariadb`
- Username: `root`
- Password: `123456`
- Database: `bank_import`

## Demo Test Commands

Login:

```bash
curl -X 'POST' \
  'https://banksample.duckdns.org/api/admin/login' \
  -H 'accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "email": "admin@example.com",
  "password": "admin123"
}'
```

Import CSV:

```bash
curl -X 'POST' \
  'https://banksample.duckdns.org/api/admin/imports' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -F 'file=@YOUR_FILE_PATH'
```

Admin customer transactions:

```bash
curl -X 'GET' \
  'https://banksample.duckdns.org/api/admin/customer-transactions?email=customer1@example.com&page=1&per_page=10' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE'
```

Customer login:

```bash
curl -X 'POST' \
  'https://banksample.duckdns.org/api/customer/login' \
  -H 'accept: */*' \
  -H 'Content-Type: application/json' \
  -d '{
  "email": "customer1@example.com",
  "password": "123456"
}'
```

Customer get transactions:

```bash
curl -X 'GET' \
  'https://banksample.duckdns.org/api/customer/transaction?page=1&per_page=10' \
  -H 'accept: */*' \
  -H 'Authorization: Bearer YOUR_CUSTOMER_TOKEN_HERE'
```

## Sample Data

- [Sample Data](docs/sample_data.csv)

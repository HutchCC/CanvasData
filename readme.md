## Synopsis

This is a tool to synchronize and load Canvas Data into a database. It is written with the Laravel framework.

---

## Installation

**Pre Reqs**
This is based on Laravel 5.2, so you'll need to install the pre-requisites.

[Laravel 5.2 Installation](https://laravel.com/docs/5.2/installation)

- PHP >= 5.5.9
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension

Composer needs to be installed.

[Composer installation](https://getcomposer.org/download/)

To install globally, use the following snippet.

`curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer`

**Clone**

`git clone https://github.com/HutchCC/CanvasData.git`

**Composer**

`composer install`

**Configuration**

`cp .env.example .env`

1. Edit .env file
2. Change the following lines to match your environment
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Canvas_Data
DB_USERNAME=username
DB_PASSWORD=password

Use the Key & Secret provided by Canvas Data
API_KEY=
API_SECRET=
API_BASEURL=https://portal.inshosteddata.com

3. Create Database schema
All that's been tested is MySQl `utf8mb4 - default collation`

---

## Command Reference

1. Download all of the canvas data files for your account.
  * `php artisan canvasdata:sync`

2. Unpack the gzipped files into a single txt file per table.
  * **Default** all
  * `php artisan canvasdata:unpack [ all | table(s) ]`
  * The requests table is typically very large, so if you don't need or want that table you can pass a single table or an array of tables separated by commas.
  * e.g. `php artisan canvasdata:unpack account_dim,assignment_dim,assignment_fact`

3. Create the schema. The columns and data sets change from time to time.

  * **Note:** this will drop the tables so that they can be re-created. The philsophsy behind this is that the Canvas Data is the authoritative source.
  * `php artisan canvasdata:create_schema`

4. Load the unpacked text files into the table(s). Again, if you want to exclude the requests file, pass the tables you want loaded separated by commas.

  * **Default** all
  * `php artisan canvasdata:load_table [ all | table(s) ]`


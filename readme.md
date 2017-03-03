## Synopsis

This is a tool to synchronize and load Canvas Data into a database. It is written with the Laravel framework.


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

```
cd CanvasData
composer install
```

**Configuration**

1. Copy the .env.example file to .env 
  
  `cp .env.example .env`

2. Edit .env file

  `nano .env`

3. Change the following lines to match your environment 
  ```  
  DB_CONNECTION=mysql  
  DB_HOST=127.0.0.1  
  DB_PORT=3306  
  DB_DATABASE=Canvas_Data  
  DB_USERNAME=username  
  DB_PASSWORD=password  
    
  # Use the Key & Secret provided by Canvas Data  
  API_KEY=  
  API_SECRET=  
  API_BASEURL=https://portal.inshosteddata.com  
  ```  
4. Generate unique artisan key for Laravel

  `php artisan key:generate`

5. Set permissions

  ```  
  chmod -R 777 bootstrap/cache  
  chmod -R 777 storage  
  ```  

6. Create Database schema
  * If you have an existing schema, it is best to drop it and re-create it so that it gets the correct collation & character set.
  * All that's been tested is MySQl `utf8mb4 - default collation`
  * Be sure to name the schema the same as what is specified in the .env file for `DB_DATABASE=Canvas_Data`
  ```  
  CREATE SCHEMA `Canvas_Data` DEFAULT CHARACTER SET utf8mb4 ;  
  ```  


## Command Reference

1. **Sync** all of the canvas data files for your account. This can take several hours for the first sync.
  * `php artisan canvasdata:sync`

2. **Unpack** the gzipped files into a single txt file per table.
  * **Default** all
  * `php artisan canvasdata:unpack [ all | table(s) ]`
  * The requests table is typically very large, so if you don't need or want that table you can pass a single table or an array of tables separated by commas.
  * e.g. `php artisan canvasdata:unpack account_dim,assignment_dim,assignment_fact`

3. **Create_Schema** The columns and data sets change from time to time, this command drops and then rebuilds the tables.

  * **Note:** this will drop the tables so that they can be re-created. The philsophsy behind this is that the Canvas Data is the authoritative source.
  * `php artisan canvasdata:create_schema`

4. **Load_Tables** Pass the tables you want loaded separated by commas. Specify all to load all of the tables.

  * **Default** all
  * `php artisan canvasdata:load_table [ all | table(s) ]`


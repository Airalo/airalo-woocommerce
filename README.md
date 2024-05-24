# Description

This plugin allows users to easily sync Airalo products to their WooCommerce Stores.
By setting up the credentials on the settings page, a job will run every hour and sync the products.
The users have the ability to manually sync the products on the settings page.

A hook will check a user's purchase, filter out non-Airalo products, and send the product ids
to the Airalo backend.

# Installation

1. Clone the Wordpress Repo https://github.com/Airalo/woocommerce
2. run ```cd plugins```
3. Clone this repo in the plugins folder
4. run ```cd airalo-woocommerce```
5. run ```composer install```

# Running the plugin

1. ```docker compose up ``` in the root folder
2. Access the website at ```0.0.0.0:8019/wp-admin```
3. Set up your local Wordpress installation
4. Navigate to the Plugins page
5. Activate WooCommerce plugin
6. Activate Airalo plugin
7. At the bottom of the side menu you will have the Airalo settings page

# Accessing Local DB

To access DB locally, all the credentials are found in the docker-compose.yml file.
```
Host: 0.0.0.0
Port: 33019
```

# Accessing Logs

1. run ```docker ps```
2. copy the id of the Image called 'wordpress'.
3. run ```docker logs -f <id>```

# Structure

## Main file

The main file of this plugin is 'airalo.php'

The comment in this file contains the data that will be shown on the Plugins page as well as data about the plugin.

## Settings Page

The code for the settings page is found in the file 'admin.php'

## Job

The job is scheduled in the file 'schedule.php'

## Syncer

The main file for product syncing is 'ProductSyncer.php'

This file calls the api through the sdk, fetches the products, and creates or updates the entry.

It also uploads the operator's image. The operator's image is uploaded and saved using **terms** and **taxonomies**.

**Taxonomies**: are saved in cache, they do not persist. They are needed to fetch terms.
**Terms**: are saved in DB, and they have meta attributes that are also saved in db.

By creating a taxonomy with a prefix and an operator id, we are able to always fetch a term from db.

The term's metadata includes the image id that was uploaded, allowing us to upload one image per operator.
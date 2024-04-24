<?php

require_once __DIR__ . '/vendor/autoload.php';

add_filter('plugin_action_links_airalo', 'airalo_add_settings_link');

function airalo_add_settings_link($Links) {
    $settings_link = '<a href="admin.php?page=airalo-settings">Settings</a>';
    if (! $Links) {
        $links = [];
    }

    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_menu', 'airalo_menu');

function airalo_menu () {
    add_menu_page(
        'Airalo Plugin Settings',
        'Airalo',
        'manage_options',
        'airalo-settings',
        'airalo_settings_page'
    );
}

function airalo_settings_page () {
    ?>
    <div>
        <h2> Airalo Plugin</h2>
        <form method="post" action="options.php">
            <?php settings_fields('airalo-settings-group'); ?>
            <?php do_settings_sections('airalo-settings-group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Sync Products</th>
                    <td><input type="submit" name="sync_products" value="Sync Now" class="button button-primary"/></td>
                </tr>
            </table>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'airalo_register_settings');

function airalo_register_settings () {
    register_setting('airalo-settings-group', 'airalo_settings');

    add_settings_section(
        'airalo_main_section',
        'Main Settings',
        'airalo_main_section_db',
        'airalo'
    );

    add_settings_field(
        'airalo_setting_field',
        'Example Setting',
        'airalo_settings_field_cb',
        'airalo',
        'my_custom_plugin_main_section',
    );

    if ( isset( $_POST['sync_products'] ) ) {
        do_action('sync_products');
    }
}

function airalo_main_section_db() {
    echo '<p>Settings</p>';
}

function airalo_settings_field_cb() {
    $options = get_options('airalo-options');
    echo '<p>yeet</p>';
}

add_action('sync_products', 'sync_products_function', 10, 2);

function sync_products_function() {
    $json = '{
  "data": [
    {
      "slug": "united-states",
      "country_code": "US",
      "title": "United States",
      "image": {
        "width": 132,
        "height": 99,
        "url": "https://cdn.airalo.com/images/16291958-0de3-4142-b1ba-d2bb0aeb689c.png"
      },
      "operators": [
        {
          "id": 569,
          "style": "light",
          "gradient_start": "#0f1b3f",
          "gradient_end": "#194281",
          "type": "local",
          "is_prepaid": false,
          "title": "Change",
          "esim_type": "Prepaid",
          "warning": null,
          "apn_type": "automatic",
          "apn_value": "wbdata",
          "is_roaming": true,
          "info": [
            "Data-only eSIM.",
            "Rechargeable online with no expiry.",
            "Operates on T-Mobile(5G) and AT&T(LTE) networks in the United States of America."
          ],
          "image": {
            "width": 1035,
            "height": 653,
            "url": "https://cdn.airalo.com/images/feb9ef43-b097-440b-bcf5-08df9e8ff823.png"
          },
          "plan_type": "data",
          "activation_policy": "first-usage",
          "is_kyc_verify": false,
          "rechargeability": true,
          "other_info": "This eSIM is for travelers to the United States. The coverage applies to all 50 states of the United States, and Puerto Rico.",
          "coverages": [
            {
              "name": "US",
              "networks": [
                {
                  "name": "AT&T",
                  "types": [
                    "LTE"
                  ]
                },
                {
                  "name": "T-Mobile",
                  "types": [
                    "5G"
                  ]
                }
              ]
            }
          ],
          "packages": [
            {
              "id": "change-7days-1gb",
              "type": "sim",
              "price": 4.5,
              "amount": 1024,
              "day": 7,
              "is_unlimited": false,
              "title": "1 GB - 7 Days",
              "data": "1 GB",
              "short_info": "This eSIM doesnt come with a phone number.",
              "voice": 100,
              "text": 100
            },
            {
              "id": "change-30days-3gb",
              "type": "sim",
              "price": 12,
              "amount": 3072,
              "day": 30,
              "is_unlimited": false,
              "title": "3 GB - 30 Days",
              "data": "3 GB",
              "short_info": "This eSIM doesnt come with a phone number.",
              "voice": 100,
              "text": 100
            },
            {
              "id": "change-30days-5gb",
              "type": "sim",
              "price": 16,
              "amount": 5120,
              "day": 30,
              "is_unlimited": false,
              "title": "5 GB - 30 Days",
              "data": "5 GB",
              "short_info": "This eSIM doesnt come with a phone number.",
              "voice": 100,
              "text": 100
            },
            {
              "id": "change-30days-10gb",
              "type": "sim",
              "price": 26,
              "amount": 10240,
              "day": 30,
              "is_unlimited": false,
              "title": "10 GB - 30 Days",
              "data": "10 GB",
              "short_info": "This eSIM doesnt come with a phone number.",
              "voice": 100,
              "text": 100
            }
          ],
          "countries": [
            {
              "country_code": "US",
              "title": "United States",
              "image": {
                "width": 132,
                "height": 99,
                "url": "https://cdn.airalo.com/images/16291958-0de3-4142-b1ba-d2bb0aeb689c.png"
              }
            }
          ]
        }
      ]
    },
    {
      "slug": "canada",
      "country_code": "CA",
      "title": "Canada",
      "image": {
        "width": 132,
        "height": 99,
        "url": "https://cdn.airalo.com/images/79413147-5257-4baa-b166-0ab1322ab291.png"
      },
      "operators": [
        {
          "id": 446,
          "style": "light",
          "gradient_start": "#f51200",
          "gradient_end": "#912123",
          "type": "local",
          "is_prepaid": false,
          "title": "Tuque Mobile",
          "esim_type": "Prepaid",
          "warning": null,
          "apn_type": "automatic",
          "apn_value": "fastaccess",
          "is_roaming": true,
          "info": [
            "4G Data-only eSIM.",
            "Rechargeable online with no expiry.",
            "Operates on the Rogers network in Canada."
          ],
          "image": {
            "width": 1035,
            "height": 653,
            "url": "https://cdn.airalo.com/images/d23b7313-1f16-4cfe-8878-9858bf55dd84.png"
          },
          "plan_type": "data",
          "activation_policy": "first-usage",
          "is_kyc_verify": false,
          "rechargeability": true,
          "other_info": null,
          "coverages": [
            {
              "name": "CA",
              "networks": [
                {
                  "name": "Rogers",
                  "types": [
                    "4G"
                  ]
                }
              ]
            }
          ],
          "packages": [
            {
              "id": "tuque-mobile-7days-1gb",
              "type": "sim",
              "price": 7.5,
              "amount": 1,
              "day": 7,
              "is_unlimited": false,
              "title": "1 GB - 7 Days",
              "data": "1 GB",
              "short_info": "This eSIM doesnt come with a number."
            },
            {
              "id": "tuque-mobile-30days-3gb",
              "type": "sim",
              "price": 17.5,
              "amount": 3072,
              "day": 30,
              "is_unlimited": false,
              "title": "3 GB - 30 Days",
              "data": "3 GB",
              "short_info": "This eSIM doesnt come with a number."
            },
            {
              "id": "tuque-mobile-30days-5gb",
              "type": "sim",
              "price": 25,
              "amount": 5120,
              "day": 30,
              "is_unlimited": false,
              "title": "5 GB - 30 Days",
              "data": "5 GB",
              "short_info": "This eSIM doesnt come with a number."
            },
            {
              "id": "tuque-mobile-30days-10gb",
              "type": "sim",
              "price": 36,
              "amount": 10240,
              "day": 30,
              "is_unlimited": false,
              "title": "10 GB - 30 Days",
              "data": "10 GB",
              "short_info": "This eSIM doesnt come with a number."
            }
          ],
          "countries": [
            {
              "country_code": "CA",
              "title": "Canada",
              "image": {
                "width": 132,
                "height": 99,
                "url": "https://cdn.airalo.com/images/79413147-5257-4baa-b166-0ab1322ab291.png"
              }
            }
          ]
        }
      ]
    }
  ],
  "links": {
    "first": "https://partners-api.airalo.com/v1/packages?page=1",
    "last": "https://partners-api.airalo.com/v1/packages?page=8",
    "prev": null,
    "next": "https://partners-api.airalo.com/v1/packages?page=2"
  },
  "meta": {
    "message": "success",
    "current_page": 1,
    "from": 1,
    "last_page": 8,
    "path": "https://partners-api.airalo.com/v1/packages",
    "per_page": "25",
    "to": 25,
    "total": 195
  }
}';

    $productSyncer = new \Airalo\Admin\Syncers\ProductSyncer($json);
    $productSyncer->handle();
}
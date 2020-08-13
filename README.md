# WooCommerce Stock Synchronization

The WooCommerce Stock Synchronization Plugin allows you to synchronize the 
stock values of the products with the same SKU across multiple WooCommerce websites.

Please show support for this plugin if you plan on using it, by buying it 
from Pronamic: 
[https://www.pronamic.eu/plugins/woocommerce-stock-synchronization/](https://www.pronamic.eu/plugins/woocommerce-stock-synchronization/)


## Requirements

*	WooCommerce version 2.1.12 or greater
*	WordPress version 4.7 or greater


## How does the plugin work?

The plugin will do HTTP POST requests to the websites you want to synchronize.

```
http://www.example.com/?wc_stock_sync=1&source=http://www.example.org/&password=secret
```

### Parameters

#### wc_stock_sync

Type: `boolean`  
Default: `1`

#### source

Type: `source`  
Default: `siteurl( '/' )`

#### password

Type: `password`


### Post Data

Type: `JSON`  
Example:

```json
{
	"SKU1": 1,
	"SKU2": 2,
	"SKU3": 4
}
```


## Documentation and Usage Instructions

[https://www.pronamic.eu/support/woocommerce-stock-synchronization/](https://www.pronamic.eu/support/woocommerce-stock-synchronization/)


## Credits

[Pronamic](https://www.pronamic.nl/) [@pronamic](https://twitter.com/pronamic)

[Remco Tolsma](https://www.remcotolsma.nl/) [@remcotolsma](https://twitter.com/remcotolsma)

# WooCommerce Stock Synchronization

The WooCommerce Stock Synchronization Plugin allows you to synchronize the 
stock values of the same products across multiple WooCommerce websites.

Please show support for this plugin if you plan on using it, by buying it 
from HappyWP: 
[http://www.happywp.com/plugins/woocommerce-stock-synchronization/](www.happywp.com/plugins/woocommerce-stock-synchronization/)


## Requirements

*	WooCommerce version 2.1.12 or greater
*	WordPress version 3.8 or greater


## How does the plugin work?

The plugin will do HTTP POST requests to the websites you want to syncrhonize.

```
http://www.example.com/?wc_stock_sync=1&source=http://www.example.org/&password=secret
```

### Parameters

#### wc_stock_sync

Type: `boolean`  
Default: `1`

#### wc_stock_sync

Type: `source`  
Default: `siteurl( '/' )`

#### wc_stock_sync

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

[http://www.happywp.com/manuals/woocommerce-stock-synchronization/](http://www.happywp.com/manuals/woocommerce-stock-synchronization/)


## Credits

[Pronamic](http://www.pronamic.nl/) [@pronamic](http://twitter.com/pronamic)

[Remco Tolsma](http://www.remcotolsma.nl/) [@remcotolsma](http://twitter.com/remcotolsma)

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/pronamic/wp-woocommerce-stock-synchronization/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

# DEPRECATED REPOSITORY. See https://github.com/vladdnepr/yii2-ycm

# Yii 2 YCM Utilities

YCM Utils - Yii 2 Content Management module Utilities

- [Github Project](https://github.com/vladdnepr/yii2-ycm-utils)

## Module is in initial development.

Anything may change at any time.

## Using

Add to web.php in `modules`

    'ycm-utils' =>  [
        'class' => '\vladdnepr\ycm\utils\Module'
    ],

Note! `ycm` module must be added in `modules`

Add `YcmModelUtilTrait` trait to all models.

In model `gridViewColumns` use `editableColumn` method for all editable columns. For example

    public function gridViewColumns()
    {
        return [
            'id',
            $this->editableColumn('name'),
            $this->editableColumn('title'),
            'description:html',
            'link:url'
        ];
    }

## Installation

If you do not have [Composer](http://getcomposer.org/), you may install it by following the instructions
at [getcomposer.org](http://getcomposer.org/doc/00-intro.md#installation-nix).

You can then install this package using the following command:

```php
php composer.phar require "vladdnepr/yii2-ycm-utils" "*"
```
or add

```json
"vladdnepr/yii2-ycm-utils": "*"
```

to the require section of your application's `composer.json` file.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Vladislav Lyshenko](https://github.com/vladdnepr)
- [All Contributors](../../contributors)

## License

Public domain. Please see [License File](LICENSE.md) for more information.

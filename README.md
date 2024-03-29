# Contao DC_Multilingual Utils Bundle

This bundle offers functionality concerning [terminal42/contao-DC_Multilingual](https://github.com/terminal42/contao-DC_Multilingual) for the Contao CMS.

## Features

- adds multilanguage support for tl_content
- adds shortcut functions for rapidly activating DC_Multilingual support for a given DCA
- adds multilanguage support for [madeyourday/contao-rocksolid-custom-elements](https://github.com/madeyourday/contao-rocksolid-custom-elements)
- adds insert tags for generating multilingual event, news or faq urls
- **caution**: overrides the id handling of DC_Multilingual v4+ to the v3 behavior (the record with the joined translation has its id set to the id of the translation record)

## Installation

1. Install via composer: `composer require heimrichhannot/contao-dc-multilingual-utils-bundle`.
2. Activate `DC_Multilingual` support for `tl_content` (not optional):

```php
/**
 * @Hook("loadDataContainer")
 */
class LoadDataContainerListener
{
    private DcMultilingualUtil $multilingualUtil;

    public function __invoke(string $table): void
    {
        if ('tl_content' === $table) {
            $this->multilingualUtil->addDcMultilingualSupport(
                $table, ['de', 'en', 'pl'], 'de', [ 'text', 'rsce_data' ] 
            );
        }
    }
}
```

## Technical details

### Insert tags

The following new tags are available:

Name | Example
-----|--------
`{{dcmu_news_url::<id>::<language>}}` | `{{dcmu_news_url::1::de}}`
`{{dcmu_event_url::<id>::<language>}}` | `{{dcmu_event_url::5::es}}`
`{{dcmu_faq_url::<id>::<language>}}` | `{{dcmu_faq_url::8::en}}`

### Activate DC_Multilingual rapidly using the shortcut functions

Simply call the following code:

```php
/**
 * @Hook("loadDataContainer")
 */
class LoadDataContainerListener
{
    private DcMultilingualUtil $multilingualUtil;

    public function __invoke(string $table): void
    {
        if ('tl_content' === $table) {
            $this->multilingualUtil->addDcMultilingualSupport(
                $table, // Table name
                ['de', 'en', 'pl'], // Supported languages
                'de', // Fallback language
                [ // the translatable fields
                    'text', // add fields here...
                    'rsce_data' // add this if you have rocksolid custom elements (tl_content only)
                ],
                [ // options
                    'langColumnName', // the language field in the dca's records (you have a record for every language and this column holds which one it is)
                    'langPid', // this field holds the parent record of every translated record
                ]
            );
            break;
        }
    }
}
```

### Notes on overridden classes

For generating content elements which respect the multilanguage records created using DC_Multilingual in the backend,
it's necessary to override the `ContentModel`, because Contao calls the core's ContentModel directly in `Controller`:

`$objRow = ContentModel::findByPk($intId);`

But for getting Contao to output the *translated* Elements, we need to use our own `ContentModel` inheriting from the class
`Terminal42\DcMultilingualBundle\Model\Multilingual/Multilingual`.

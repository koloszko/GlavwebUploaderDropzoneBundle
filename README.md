Installation
============

### Get the bundle using composer

Add GlavwebDropzoneBundle by running this command from the terminal at the root of
your Symfony project:

```bash
php composer.phar require glavweb/uploader-dropzone-bundle
```


### Enable the bundle

To start using the bundle, register the bundle in your application's kernel class:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Glavweb\UploaderDropzoneBundle\GlavwebUploaderDropzoneBundle(),
        // ...
    );
}
```

To add resources to a twig confinuration.

```
twig:
    ...
    form:
        resources:
            ...
            - 'GlavwebUploaderDropzoneBundle:Form:fields.html.twig'

```


### Execute "assets:install".

```
php app/console assets:install
```

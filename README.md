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

To add resources to a twig layout 

```

{% block javascripts %}

    ...
    
    <script src="{{ asset('bundles/glavwebuploaderdropzone/js/jquery.plainmodal.min.js') }}"></script>
    <script src="{{ asset('bundles/glavwebuploaderdropzone/js/dropzone.js') }}"></script>
    <script src="{{ asset('bundles/glavwebuploaderdropzone/js/glavwebUploaderDropzone.js') }}"></script>
    
    ...
    
{% endblock %}
 
 
{% block stylesheets %}

    ...
    
    <link rel="stylesheet" href="{{ asset('bundles/glavwebuploaderdropzone/css/dropzone.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/glavwebuploaderdropzone/css/style.css') }}">
    
    ...
    
{% endblock %}

```



### Execute "assets:install".

```
php app/console assets:install
```

for Symfony3:

```
php bin/console assets:install
```
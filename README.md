# GoogleCloudTranslationsBundle
A Symfony bundle to translate your application using Google cloud Translations API

#Install
<pre><code>
composer require stev/google-cloud-translations-bundle --dev
</code></pre>

Initialize the bundle for dev env.
<pre><code>$bundles[] = new \Stev\GoogleCloudTranslateBundle\StevGoogleCloudTranslateBundle();</code></pre>

#Configuration

Get your Google Cloud Key (https://cloud.google.com/translate/docs/quickstarts) and save it in your project in "app/data/key.json" or anywhere you want. Just make sure it's not a public folder.

Add the following configuration to config_dev.yml
<pre><code>
stev_google_cloud_translate:
    keyFilePath: '%kernel.root_dir%/../data/cp-google-cloud-key.json'
</code></pre>

You must have JMS Translations installed (https://jmsyst.com/bundles/JMSTranslationBundle). If not already present in your composer it will be automatically installed.
Here's a sample configuration
<pre><code>
jms_translation:
    locales: [en,fr,de]
    configs:
        app:
            dirs: ["%kernel.root_dir%/../src/AppBundle"]
            output_dir: "%kernel.root_dir%/Resources/AppBundle/translations"
            ignored_domains: [routes]
            excluded_names: ["*TestCase.php", "*Test.php"]
            excluded_dirs: [cache, data, logs]
            keep: true
            output_format: xliff
</code></pre>

#Usage

Let's suppose your default language is English.
1. Extract your translations using JMS command
   <pre><code>php app/console translation:extract --config=app</code></pre>
2. Translate the first set of messages in your default language (english). All default language translations must be provided by you/developers. You can use the web UI provided by JMS for that https://jmsyst.com/bundles/JMSTranslationBundle/master/webinterface.
3. Use the following command to translate all messages from your default language to any other language supported by Google Translations:
   <pre><code>php app/console stev:google_cloud_translate --config=app --sourceLocale=en --destinationLocale=fr</code></pre>

Now if you check your French translations files you should find all of your messages translated into French.

How to run the tests
====================

Prerequisites:
--------------

Download composer and update dev dependencies.
::

    php composer.phar update

Pre required before tests:
--------------------------

    Please update the config file to update the constants needed

Run the recommended tests:
--------------------------

It is recommended to launch these tests at least once to ensure this library will work properly on your configuration.
::

    vendor/phpunit/phpunit/phpunit --group recommended --exclude-group ignore --bootstrap config.php tests

Run a specific test:
--------------------

You can run a specific test adding a filter to the previous command.
::

    vendor/phpunit/phpunit/phpunit --filter PayplugTest --group unit --exclude-group ignore --bootstrap config.php tests

Run specific groups of test
---------------------------

You can filter tests by groups:
::

    # Run unit tests
    vendor/phpunit/phpunit/phpunit --group unit tests

    # Run all tests
    vendor/phpunit/phpunit/phpunit tests

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



# Code coverage

  PHPUnit's code coverage functionality provides an insight into what parts of the production code are executed when the tests are run. It makes use of the xdebug component, which can help in assessing the test performance and quality aspects of any software.

  ### Prerequisites

  To enable code coverage, Xdebug should be installed and properly configured.
  If you use the docker_module, you don't need to do this since it is automatically configured during the install of your docker environnement inside of the dockerfile.

  ### How to configue a config xml file

  To run code coverage, a **whitelist** is required for configurations. The example of phpunit.xml is something like below.
  <details>
    <summary>View xml file content</summary>

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="./vendor/autoload.php" colors="true">
  <testsuites>
      <testsuite name="Test Suite">
          <directory>./tests</directory>
      </testsuite>
  </testsuites>
  <filter>
      <whitelist processUncoveredFilesFromWhitelist="true">
          <directory suffix=".php">./src</directory>
      </whitelist>
  </filter>
</phpunit>

```
</details><br>

***If whitelist is not added in phpunit.xml, phpunit produces errors.***

```
Error: Incorrect whitelist config, no code coverage will be generated.
```

### Running the Tests in CLI With Text Only Coverage

  To run code coverage for the whole src directory (optional: --configuration yourConf.xml):

``` bash
vendor/phpunit/phpunit/phpunit --coverage-text
```

To run code coverage for a specific file:

``` bash
vendor/phpunit/phpunit/phpunit /path/to/my/test.php --coverage-text
```

  ### Some Code Coverage Options:

``` bash
--coverage-html <dir>     To generates a code coverage report in HTML format run the following line command:
--coverage-text=<file>    Generate code coverage report in text format.
--filter <pattern>        Filter which tests to run.
--debug                   Display debugging information during test execution.
--fail-on-warning         Treat tests with warnings as failures.
--fail-on-risky           Treat risky tests as failures.
--stop-on-error           Stop execution upon first error.
--stop-on-failure         Stop execution upon first error or failure.
--stop-on-warning         Stop execution upon first warning.
```

  ### Generating HTML Coverage Output


By just using the **--coverage-html** option, we can generate nice HTML output.

``` bash
vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --coverage-html html
```

  The contents of html directory is like below.

``` bash
html
├── ParentClass.php.html
├── dashboard.html
└── index.html
```


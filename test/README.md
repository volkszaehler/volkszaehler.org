# Unit tests for Volkszaehler.org

## Open test manager

To run the unit tests open your installation folder and navigate to the *test* directory:

    [http://localhost/volkszaehler/test](http://localhost/volkszaehler/test)

## Run individual tests

Choose which test to run from the file selector and select "Run".

*NOTE* Abstract base classes (Middleware.php and DataContext.php at the time of writing) must not be selected as test cases as they lead to PHPUnit error messages.

## Run all tests

Choose the XML configuration file provided and select "Run".

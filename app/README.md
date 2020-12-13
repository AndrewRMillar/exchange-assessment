# Currency Exchange Plan

Create a system that will exchange one currency for an other
according to the most recent exchange rates.

## Outline

The functionality requires a number of elements:

- There should be a form with two select inputs with all the possible currency options, and a number input for the amount
    - The form should have an action that points to an uri that should target a route that sends the request to a
      controller: `/exchenge/exchange`
- When a new request to calculate a currency amount against a different currency is received:
    - The system that receives the request should get the current exchange rates from the [website](https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml)
    - This system should check the date of the data from the website and compare it against the date of the newest
      data in the database
    - If this data is newer than the data in the database; add it to the database

## Steps

- Create a controller that does the currency conversion
    - `bin/controller make:controller ExchangeController`
- Create a route that targets the controller
    - Symfony puts the routes in the doc-block `/* @Route("path/to", name="name-it") */`
- Create a view for the form
    - The make:controller command also created a twig, put a form in the twig (not the "symfony way")
    - Set the action to a route and add the route to a controller method doc-block
    - Get the form values from the request
- Create an Entity for the exchange rates
    - Migrate the migration for that entity
- Create the business logic for the controller,
    - Method that retrieves the data from the website (XML)
    - Method that puts the data from the site in the db
    - Method that compares the time of the data from the db and site
    - Method that receives the form post and performs the calculation 

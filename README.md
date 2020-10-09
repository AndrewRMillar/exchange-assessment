# PHP assessment MarketPhase

This is a template for a Symfony 5 application running inside Docker containers.

If you do not have a Docker installation, please go to https://docs.docker.com/compose/install/ and follow the 
instructions to install the docker toolbox!

To get started, fork this repositories to your own Github account. Then, download the repository. Navigate in to the 
folder and launch the docker containers using the following commands.

```
cd path/to/php-assessment

cd docker

docker-compose up
```

### Seeding the database

To load the seed data in our database, in another terminal navigate to the docker folder and run the following command:

```
cd php-assessment/docker/

docker-compose run php-fpm bin/console doctrine:fixtures:load
```

If you visit localhost you should now see a table with currency codes!


### Using composer

If you want to use composer to install packages you can run it with the following command (also from within the docker directory).

```
docker-compose run php-fpm composer 
```




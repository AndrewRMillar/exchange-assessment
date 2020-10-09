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

### Installing dependencies

Run composer install inside the docker container with the following command to install all the project dependencies.

```
docker-compose run php-fpm composer 
```

### Seeding the database

To load the seed data in our database run the following command:

```
docker-compose run php-fpm bin/console doctrine:fixtures:load
```




# Symfony 4 app

This is a proof of concept app that is able to consume data from a 3rd party API, send the results to an exchange on a RabbitMQ instance where
they are filtered and finally consume the filtered results from a queue and store them in a database

The app is built using PHP 7.1 and Symfony 4.4. Doctrine is also utilized for the MySQL database management.

## Install

Please use git clone to get a copy of the repo:

    git clone https://github.com/orestislampridis/assignment-n2g

## Instructions

After making sure you have PHP 7.1, Symfony 4.4 and composer installed in your machine, you need to  serve the app:

    symfony serve

To test the functionality of the app, at least one request needs to be made to: `GET http://127.0.0.1:8000/subscribe`. 
Everytime this request gets called, it consumes data from the API provided and posts them to the RabbitMQ instance
according to the specifications.

This creates the exchange and the queue with the appropriate routing keys that can be then consumed by using the 
request: `GET http://127.0.0.1:8000/receive`. 

Afterwards, the messages taken from the queue are saved into the database provided.
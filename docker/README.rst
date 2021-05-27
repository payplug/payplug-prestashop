Hot to run two dockers simultaneously
=====================================

First instance  (Prestashop 1.7.7 / php 7.3) to use on localhost:8001
::
  docker-compose up

Second instance (Prestashop 1.6.1.24 / php 5.6) to use on localhost:8011
::
  docker-compose -f docker-compose-2.yml up

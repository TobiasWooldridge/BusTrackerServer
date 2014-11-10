Loop Bus Tracker Backend
========================

This repo contains the code for the Flinders Uni Loop Bus Tracker backend.

It's responsibilities are storing the Loop Bus(ses) movements, the locations of bus stops, and predicting where the loop bus will be.

I don't use any framework because I want the learning curve for maintaining this project to be minimal/zero, and I don't want to introduce dependencies that will need to be set up/updated.

Setup
-----

The dependencies for this are PHP, pgsql, and the PHP pgsql PDO driver.

Put this in a folder. Make /web web accessible. Configure the DSN string in db.php and initialize your pgsql database with db.sql

Credits
=======
Bus/bus stop icon from http://www.freepik.com/free-icon/bus-stop-geolocalization_695616.htm

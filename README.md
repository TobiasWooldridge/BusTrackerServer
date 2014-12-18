Loop Bus Tracker Backend
========================

This repo contains the code for the Flinders Uni Loop Bus Tracker backend.

It's responsibilities are storing the Loop Bus(ses) movements, the locations of
bus stops, and predicting where the loop bus will be.

I don't use any framework because one of my design goals for this project was
that the technical complexity for maintaining/picking up this project would be
near zero, and I did not want to introduce dependencies that would need
to be installed/updated.

Setup
-----
The dependencies for this are PHP, pgsql, and the PHP pgsql PDO driver.

### High level instructions

Put this in a folder. Make /web web accessible. Configure the DSN string in
db.php and initialize your pgsql database with db.sql


### Detailed instructions

The following instructions assume Ubuntu 12.04 or newer

1. `git clone git@github.com:TobiasWooldridge/BusTrackerServer.git`
2. Add the following repository to /etc/apt/sources.list `deb http://apt.postgresql.org/pub/repos/apt/ precise-pgdg main`
2. `sudo apt-get update`
2. Install postgresql
	`sudo apt-get install postgresql-9.1`
3. Create and configure your postgresql database
	`sudo su postgres`
  1. `psql`
  	1. `CREATE USER tracker WITH PASSWORD 'tracker';`
  	2. `CREATE DATABASE tracker WITH OWNER tracker;`
  	3. `\connect tracker`
4. cd BusTrackerServer
5. `cat db.sql | psql -U tracker -h localhost`
6. `sudo apt-get install php5-cli php5-common php5-fpm php5-pgsql nginx`
7. Add the site configuration to nginx. Update root directory path as approriate.

    `sudo vim /etc/nginx/sites-enabled/loopbus`
	```
	server {
	  server_name loopb.us;

	  root /var/www/BusTrackerServer/web/;

	  location ~ \.php {
	    include fastcgi_params;
	    fastcgi_pass unix:/var/run/php5-fpm.sock;
	  }
	}```
8. Restart nginx.
	`sudo /etc/init.d/nginx restart`
9. Modify php-fpm's socket to a unix socket and configure its permissions
	`sudo vim /etc/php5/fpm/pool.d/www.conf`

	Replace `listen = 127.0.0.1:9000` with `listen = /var/run/php5-fpm.sock`

	Uncomment the following three lines or similar
	```
	listen.owner = www-data
	listen.group = www-data
	listen.mode = 0660
	```

10. Restart php-fpm
	`sudo /etc/init.d/php5-fpm restart`

11. Watch the error log for errors
	`sudo tail -f /var/log/nginx/error.log`


Prediction Algorithm
--------------------

1. Look up when the bus was at a similar position in the past
2. Get the 5-min history of the bus shortly before those times
3. Compare those 5-min histories to the last 5-mins of what the bus has been
doing
4. Discard all dissimilar histories
5. Weight each 5-min history based on similarities to the current history e.g.
time of day
6. Look up what the bus did after those 5 minute histories (i.e. how long the
bus took to get to each bus stop from where the bus is now)
7. Use these times, and the similarity weights, to calculate a weighted average
arrival time for each stop
8. Send these weighted average arrival times to the user

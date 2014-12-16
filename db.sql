-- Enable Topology
CREATE EXTENSION postgis_topology;
-- fuzzy matching needed for Tiger
CREATE EXTENSION fuzzystrmatch;
-- Enable US Tiger Geocoder
CREATE EXTENSION postgis_tiger_geocoder;

DROP TABLE blip;
DROP TABLE stop;
DROP TABLE bus;

CREATE TABLE bus (
	id SERIAL,
	secret_key VARCHAR(64) NOT NULL,
	name VARCHAR(128) NOT NULL
);

CREATE TABLE blip (
	id SERIAL,
	bus_id INTEGER NOT NULL,
	at TIMESTAMP NOT NULL,
	location GEOGRAPHY NOT NULL,
	speed DOUBLE PRECISION NOT NULL,
	altitude DOUBLE PRECISION NOT NULL,
	bearing DOUBLE PRECISION NOT NULL,
	at_stop INTEGER DEFAULT NULL
);

CREATE TABLE stop (
	id SERIAL,
	location GEOGRAPHY NOT NULL,
	altitude DOUBLE PRECISION NOT NULL,
	bearing DOUBLE PRECISION NOT NULL,
	name VARCHAR(128) NOT NULL DEFAULT 'Unnamed Stop',
	note VARCHAR(1024) NOT NULL DEFAULT ''
);




-- UPDATE blip SET at_stop = (SELECT id FROM stop WHERE ST_DWithin(blip.location, stop.location, 20) LIMIT 1);


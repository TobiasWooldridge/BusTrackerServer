-- http://www.movable-type.co.uk/scripts/latlong-db.html
-- Maths taken from above page. Licenced under cc-attribution. Originally by Chris Veness
CREATE OR REPLACE FUNCTION
	-- LBT_Distance(lon lat lon lat)
	-- Returns result in meters
	LBT_Distance(DOUBLE PRECISION, DOUBLE PRECISION, DOUBLE PRECISION, DOUBLE PRECISION) RETURNS DOUBLE PRECISION
	AS $$ SELECT acos(sin(radians($2))*sin(radians($4)) + cos(radians($2))*cos(radians($4))*cos(radians($3 - $1))) * 6371000.0; $$
	LANGUAGE SQL;

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
	longitude DOUBLE PRECISION NOT NULL,
	latitude DOUBLE PRECISION NOT NULL,
	speed DOUBLE PRECISION NOT NULL,
	altitude DOUBLE PRECISION NOT NULL,
	bearing DOUBLE PRECISION NOT NULL,
	at_stop INTEGER DEFAULT NULL
);

CREATE TABLE stop (
	id SERIAL,
	longitude DOUBLE PRECISION NOT NULL,
	latitude DOUBLE PRECISION NOT NULL,
	altitude DOUBLE PRECISION NOT NULL,
	bearing DOUBLE PRECISION NOT NULL,
	name VARCHAR(128) NOT NULL DEFAULT 'Unnamed Stop',
	note VARCHAR(1024) NOT NULL DEFAULT ''
);

CREATE INDEX bus_id ON bus (id);

CREATE INDEX blip_id ON blip (id);
CREATE INDEX blip_time ON blip (at DESC);
CREATE INDEX blip_location ON blip (longitude, latitude);
CREATE INDEX blip_location_reversed ON blip (latitude, longitude);

CREATE INDEX stop_id ON blip (id);
CREATE INDEX stop_location ON blip (longitude, latitude);
CREATE INDEX stop_location_reversed ON blip (latitude, longitude);


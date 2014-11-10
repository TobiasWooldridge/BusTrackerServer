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

Put this in a folder. Make /web web accessible. Configure the DSN string in
db.php and initialize your pgsql database with db.sql


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

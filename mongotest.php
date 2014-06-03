<?php

try {
	// open connection to MongoDB server
	//$conn = new Mongo('localhost');
	$username = 'mongoEarthquakes';
	$password = '8kYb8r78o2piGgAH34WY8ZB9imKMgHs6';
	$server = 'localhost';
	$databaseName = 'everyEarthquake';
	$collectionName = 'earthquakes';
	$connection = new MongoClient("mongodb://$username:$password@$server/$databaseName");

	// access database and collection
	$database = $connection->$databaseName;
	$collection = $database->$collectionName;

	// execute query
	// retrieve all documents
	$earthquakes = $collection->find();

	// iterate through the result set
	// print each document
	echo '<br />Retrieving all earthquakes: ' . $earthquakes->count() . ' found:<br/>';
	foreach ($earthquakes as $earthquake) {
		$earthquakeProperties = $earthquake['properties'];
		$earthquakeGeometry = $earthquake['geometry'];
		$geometryCoordinates = $earthquakeGeometry['coordinates'];
		echo 'Id: ' . $earthquake['id'] . '<br />';
		echo 'Type: ' . $earthquake['type'] . '<br/>';
		echo 'Mag: ' . $earthquakeProperties['mag'] . '<br/>';
		echo 'Place: ' . $earthquakeProperties['place'] . '<br/>';
		echo 'Time: ' . date('n/j/y @ G:i:s', substr($earthquakeProperties['time'], 0, 10)) . ' Local<br/>';
		echo 'URL: ' . $earthquakeProperties['url'] . '<br/>';
		echo 'Lat: ' . $geometryCoordinates['0'] . '<br/>';
		echo 'Long: ' . $geometryCoordinates['1'] . '<br/>';
		echo '<br/>';
	}

} catch (MongoConnectionException $e) {
	die('Error connecting to MongoDB server: ' . $e->getMessage());
} catch (MongoException $e) {
	die('Error: ' . $e->getMessage());
}

<?php

$dbPath = getenv('DATABASE_PATH') ?: __DIR__ . '/tweets.db';
$dbDir = dirname($dbPath);

// Create database directory if it doesn't exist
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Create and connect to the database
$db = new SQLite3($dbPath);

// Read and execute the schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$db->exec($schema);

// Add some initial tweets if the table is empty
$result = $db->query('SELECT COUNT(*) as count FROM tweets');
$count = $result->fetchArray(SQLITE3_ASSOC)['count'];

if ($count == 0) {
    $db->exec("INSERT INTO tweets (content) VALUES ('Welcome to Friend of a Global Economy! This is a collection of Bill Ackman''s insightful tweets.')");
}

echo "Database initialized successfully!\n"; 
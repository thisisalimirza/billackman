#!/bin/bash

# Initialize the database
php /var/www/html/database/init.php

# Start Apache in foreground
exec apache2-foreground 
# Friend of a Global Economy

A modern website showcasing Bill Ackman's insightful tweets signed as "from a friend of the global economy".

## Features

- Clean, modern UI with dark mode support
- Responsive design
- SQLite database for tweet storage
- Easy to maintain and extend

## Requirements

- PHP 8.1 or higher
- SQLite3
- Composer

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create the database:
   ```bash
   mkdir -p database
   sqlite3 database/tweets.db < database/init.sql
   ```
4. Configure your web server to point to the `public` directory

## Development

The website uses:
- PHP for backend
- SQLite for database
- TailwindCSS for styling
- Alpine.js for interactivity

## Adding New Tweets

To add new tweets, you can use the SQLite command line or create a simple admin interface (to be implemented).

## License

MIT License 
# Laravel Project Setup

To set up the project locally, follow the commands below:

# Clone the repository
git clone project
cd project

# Install PHP dependencies
composer install

# Copy the environment file and generate the app key
cp .env.example .env
php artisan key:generate

# Configure your .env file with database credentials
# (Open .env and update the following lines)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Run database migrations
php artisan migrate

# Seed the database for authors
php artisan db:seed

# Start the Laravel development server
php artisan serve

# The application should now be accessible at:
http://localhost:8000

# The postman collection is in extra_docs/Collection directory.
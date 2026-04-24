# Use the official PHP Apache base image
FROM php:8.2-apache

# Install MariaDB (MySQL drop-in replacement) and PHP extensions
RUN apt-get update && apt-get install -y mariadb-server \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Copy the entire project into the Apache document root
COPY . /var/www/html/

# Ensure appropriate permissions for the web root
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80 (Standard for Render Web Services)
EXPOSE 80

# Create a Startup Script wrapper
# This script will boot MySQL, import your schema automatically, 
# and then launch the Apache web server.
RUN echo '#!/bin/bash\n\
# Start MySQL Service\n\
service mariadb start\n\
\n\
# Wait for MySQL to fully boot up\n\
sleep 3\n\
\n\
# Initialize the database mimicking XAMPP exactly (root with empty password)\n\
mysql -e "CREATE DATABASE IF NOT EXISTS restaurant_db;"\n\
mysql -e "CREATE USER IF NOT EXISTS '\''root'\''@'\''localhost'\'' IDENTIFIED BY '\'''\'';"\n\
mysql -e "GRANT ALL PRIVILEGES ON restaurant_db.* TO '\''root'\''@'\''localhost'\'';"\n\
mysql -e "FLUSH PRIVILEGES;"\n\
\n\
# Import your specific XAMPP schema file\n\
mysql restaurant_db < /var/www/html/schema.sql\n\
\n\
# Start Apache in the foreground (keeps container alive)\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

# Run the wrapper script
CMD ["/start.sh"]

# PHP Email and Add to Database Contact Form
======

An example of some plain PHP code with some PHPUnit tests.

HTML5 page using Bootstrap along with other styles and JS that contains a form. The form is submitted and is validated. An Invalid form will alert the user, a successful submission with email "test@example.com" using PHP's mail function and save the form to the database. A valid submitted alert is shown to the user.


## Deployment
------

To run:

```
cd repo-checkout
composer install
php -S 127.0.0.1:9999 -t public
```

A database and table will programmatically be created in MySQL, just change the `createDBConnection` method variables from the `ContactFormSubmit` class to your MySQL instance.

The database schema is:

```
CREATE DATABASE `example_contact_form`;

CREATE TABLE `contact_form` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`full_name` varchar(255) NOT NULL DEFAULT '',
		`email` varchar(255) NOT NULL,
		`phone` varchar(20) DEFAULT '',
		`message` longtext NOT NULL,
		PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

--

## Running the Tests
----

Testing:

```
cd repo-checkout
phpunit
```

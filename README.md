kPaste 
======
###Free and Open Source Paste Bin Platform Based on ZF2

-----

###Installation Guide

#####1. Clone the repository

Clone the repository into the document root of your web server or virtualhost:
`git clone https://github.com/mnvoh/kpaste.git`


#####2. Install the dependencies

Run `composer install` in the root directory of the project to install the dependencies.


#####3. Database


Create a database for this project in MySQL, import `kpaste.sql` and then
set the appropriate configs in `config/autoload/global.php` and `local.php`.

**Default Master Admin Account:**
username: admin
password: adminadmin


###Notice
sql mode "only_full_group_by" must be turned off. To do so, add this 
line to the end of the "/etc/mysql/my.cnf" config file:

[mysqld]
sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"


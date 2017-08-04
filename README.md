# EasyEngine CRUD operations using wp-cli

This repository contains the code for implementing EasyEngine like CRUD operations using wp-cli.         

# Note : Kindly make sure to set environment variable `WEBROOT_PATH` before trying these commands. This has been done due to the fact that this directory might vary from system to system and environment to environment. Also kindly note that these commands currently does not create actual files, they just create a text file containing all the details.

# Status - Working functionality

1. Create a simple html site:
```
wp ee site create example.com --html 
```

2. Create a php site:
```
wp ee site create example.com --php
wp ee site create example.com --php7
```

3. Create a php + mysql site
```
wp ee site create example.com --mysql
```
4. Create various Wordpress sites:
```
wp ee site create example.com --wp // creates WP site with php5.6, mysql5.6.
wp ee site create example.com --wpredis // creates WP site with redis cache
wp ee site create example.com --wpfc //creates WP site with FasCGI cache
```
5. Delete a website:
```
wp ee site delete example.com
wp ee site delete example.com --files // deletes wesite webrot only
wp ee site delete example.com --db // deletes DB of website only
wp ee site delete example.com --no-prompt // deletes complete site without any prompt
```

6. List all files:
```
wp ee site list // lists all the files present in the sites directory
```
7. Show website:
```
wp ee site show example.com // shows the text file of website present in website webroot
```
8. Update website:
```
wp ee site update example.com --mysql // update website to mysql 5.6 
wp ee site update example.com --wp // updating plain html,php and other sites to WP sites
wp ee site update example.com --wpredis // update other sites with redis cache
wp ee site update exmaple.com --wpfc // update other sites with FastCGI cache
```



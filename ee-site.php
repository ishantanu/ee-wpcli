<?php


if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}


class EE_Site_Create_Command extends WP_CLI_Command {
    public  $site_name  = "";
    public  $site_type  = "";
    public  $cache_type = "";
    public  $mysql_ver  = "";

    /**
     * @param string $arg         Positional Arg
     * @param array $assoc_args   Associative Args
     * @when before_wp_load
     */
    public function __invoke($arg, $assoc_args) {
        if (empty($arg) ) {
            WP_CLI::error('Site name not specified!');
        } else {
            $name = $arg[0];
        }
        $webroot_path = getenv('WEBROOT_PATH');

        $structure    = "$webroot_path/$name";
        $my_file      = "$structure/$name.txt";

        if (!mkdir($structure, 0777, true)) {
            die('Failed to create folders...');
        }

        $handle       = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file
        $filename     = "$structure/$name.txt";

        $previousValue = null;

        print ("Site Creation\n");
        print ("=============\n");

        foreach($assoc_args as $key => $value) {
            if ($value) {
                switch($key) {
                    case ($key == "html"):
                        $site_type  = 'html';
                        $cache_type = 'disabled';
                        $PHP_flag   = 'no';
                        $Mysql_flag = 'no';
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);

                        break;
                    case ($key == "php"):
                        if ($previousValue == "wp") {
                            break;
                        }
                        $site_type  = 'php';
                        $cache_type = 'disabled';
                        $PHP_flag   = '5.6';
                        $Mysql_flag = 'no';
                        //if ($previousValue) {
                        //    echo $previousValue;
                        //}
                        $previousValue = $key;
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case  ($key == "php7"):
                        //echo "Previous value" . $previousValue;
                        if ($previousValue == "wpredis" || $previousValue == "wpfc") {
                            $PHP_flag = '7.0';
                        } else {
                            $site_type  = 'php';

                            $cache_type = 'disabled';
                            $PHP_flag   = '7.0';
                            $Mysql_flag = 'no';
                        }

                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);

                        $previousValue = $key;

                        break;
                    case ($key == "mysql"):
                        $site_type  = 'php + mysql';
                        $cache_type = 'disabled';
                        $Mysql_flag = 'yes';
                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;

                    case ($key == "wp"):
                        $site_type  = 'Wordpress';
                        $cache_type = 'disabled';
                        $Mysql_flag = '5.6';

                        if ($previousValue == "php7") {
                            $PHP_flag  = '7.0';
                        } else {
                            $PHP_flag  = '5.6';
                        }
                        $previousValue = $key;
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case ($key == "wpredis"):
                        $site_type  = 'Wordpress';
                        $cache_type = 'Redis';
                        $Mysql_flag = 'yes';

                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }
                        if ($previousValue == null) {
                            $previousValue = $key;
                        }
                        $previousValue = "wpredis";
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);

                        break;
                    case ($key == "wpfc"):
                        $site_type  = 'Wordpress';
                        $cache_type = 'FastCGI';
                        $Mysql_flag = 'yes';
                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }
                        if ($previousValue == null) {
                            $previousValue = $key;
                        }
                        $this->writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag);
                        $this->writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;

                }
            }
        }

        if ($file = fopen($my_file, 'r')) {
            while (!feof($file)) {
                print(fread($file, filesize($my_file)));
            }
            fclose($file);
            WP_CLI::success( $name . ' created successfully' );

        }

    }

    public function writedb($name,$site_type,$cache_type,$PHP_flag,$Mysql_flag) {
        $site_name = $name;
        $dbdir = getcwd();

        $db        = new SQLite3("$dbdir/ee.db");

        $create    = $db->query("CREATE TABLE IF NOT EXISTS ee (
                                        ID            INT         PRIMARY KEY AUTOINCREMENT,
                                        site_name     CHAR(50)                NOT NULL,
                                        site_type     CHAR(20)                        ,
                                        cache_type    CHAR(20)                        ,
                                        php_flag      CHAR(10)                        ,
                                        mysql_flag    CHAR(10)         )               
                                        ");

        $errcode = $db->lastErrorCode();
        if ($errcode) {
            die();
        }

        $result    = $db->query("INSERT OR REPLACE INTO ee (site_name, site_type, cache_type, php_flag, mysql_flag) VALUES
                               ('$site_name','$site_type','$cache_type','$PHP_flag','$Mysql_flag')");

        $errcode = $db->lastErrorCode();
        if ($errcode) {
            $db->close();
            die();
        }
        $db->close();
    }

    public function writefile($name,$site_type, $cache_type, $PHP_flag, $Mysql_flag) {
        $webroot_path = getenv('WEBROOT_PATH');

        $structure    = "$webroot_path/$name";
        $my_file      = "$structure/$name.txt";

        $handle       = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file
        $filename     = "$structure/$name.txt";
        $filecontent  = file_get_contents($filename);
        $filecontent .= "site-type = $site_type
cache-type = $cache_type
PHP = $PHP_flag
Mysql = $Mysql_flag
";
        file_put_contents($filename, $filecontent);
        fclose($handle);

    }

}

class EE_Site_Delete_Command extends WP_CLI_Command {
    /**
     * Deletes a site
     *
     * Example: wp ee site delete example.com
     *
     * @param $arg         Positional argument.
     * @param array $assoc_args  Associative argument.
     *
     * @when before_wp_load
     */

    public function __invoke( $arg, $assoc_args ) {
        if (empty($arg)) {
            WP_CLI::log( 'Please specify site name!' );
            return;
        } else {

            $site_name = $arg[0];
            echo "This is $site_name";
            // get website directory from env variable
            $webroot_path = getenv('WEBROOT_PATH');
            // webroot dir
            $dirname = "$webroot_path/$site_name";
            if (empty($assoc_args)) {
                WP_CLI::confirm( 'Are you sure you want to delete the site ' . $site_name . '?' );
                $this->deletedb($site_name);

                array_map('unlink', glob("$dirname/*.*"));
                rmdir($dirname) or die('Cannot delete site website webroot:  '.$site_name);
                print 'Deleted webroot and DB successfully';

            }
            foreach($assoc_args as $key => $value) {
                if ($value) {
                    switch ($key) {
                        case ($key == "no-prompt"):
                            $this->deletedb($site_name);
                            array_map('unlink', glob("$dirname/*.*"));
                            rmdir($dirname) or die('Cannot delete site:  '.$site_name);
                            print ('Deleted website successfully!');
                            break;

                        case ($key == "files"):
                            WP_CLI::confirm( 'Are you sure you want to delete the webroot of ' . $site_name . '?' );
                            array_map('unlink', glob("$dirname/*.*"));
                            rmdir($dirname) or die('Cannot delete site website webroot:  '.$site_name);
                            print 'Deleted website webroot successfully!';
                            break;

                        case ($key == "db"):
                             WP_CLI::confirm( 'Are you sure you want to delete DB of ' . $site_name . '?' );
                             $this->deletedb($site_name);

                             print 'Deleted website DB successfully';
                             return;

                             break;

                        default:
                            WP_CLI::confirm( 'Are you sure you want to delete the site ' . $site_name . '?' );
                            $this->deletedb($site_name);

                            array_map('unlink', glob("$dirname/*.*"));
                            rmdir($dirname) or die('Cannot delete site website webroot:  '.$site_name);
                            print 'Deleted webroot and DB successfully';
                            return;
                    }
                }
            }
        }
    }

    public function deletedb($name) {
        $db      = new SQLite3('/Users/shantanudeshpande/test-wpcli/ee.db');
        $result  = $db->query("DELETE from ee where site_name = '$name'");
        $errcode = $db->lastErrorCode();
        if ($errcode) {
            $db->close();
            die();
        }

        $db->close();
    }

}

class EE_Site_Update_Command extends WP_CLI_Command
{

    /**
     * Updates the specified website.
     *
     * Example:
     * wp ee site update example.com --wpredis
     *
     * @param array $arg Positional argument.
     * @param array $assoc_args Associative argument.
     *
     * @when before_wp_load
     */
    public function __invoke($arg, $assoc_args)
    {
        $site_type  = null;
        $cache_type = null;
        $PHP_flag   = null;
        $Mysql_flag = null;

        $name = $arg[0];
        $webroot_path = getenv('WEBROOT_PATH');
        $structure    = "$webroot_path/$name";

        $my_file      = "$structure/$name.txt";
        $handler      = fopen($my_file, 'a+') or die('Cannot open file:  ' . $my_file); //implicitly creates file
        $filename     = "$structure/$name.txt";

        $previousValue = null;
        foreach ($assoc_args as $key => $value) {
            if ($value) {
                switch ($key) {
                    case ($key == "php"):
                        if ($previousValue == "wp") {
                            break;
                        }
                        $dirname    = "$webroot_path/$this->name";
                        fclose($handler);
                        array_map('unlink', glob("$dirname/*.txt"));
                        $handler    = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename   = "$structure/$this->name.txt";

                        $site_type  = 'php';
                        $cache_type = 'disabled';
                        $PHP_flag   = '5.6';
                        $Mysql_flag = 'no';
                        $this->updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case ($key == "php7"):

                        fclose($handler);
                        unlink($filename);

                        $handler        = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename       = "$structure/$name.txt";
                        if ($previousValue == "wpredis" || $previousValue == "wpfc") {
                            $PHP_flag   = '7.0';
                        } else {
                            $site_type  = 'php';
                            $cache_type = 'disabled';
                            $PHP_flag   = '7.0';
                            $Mysql_flag = 'no';
                        }
                        $this->updatedb($name,  $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);

                        $previousValue = $key;
                        break;
                    case ($key == "mysql"):
                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }

                        fclose($handler);
                        unlink($filename);

                        $handler    = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename   = "$structure/$name.txt";

                        $site_type  = 'php + mysql';
                        $cache_type = 'disabled';
                        $Mysql_flag = '5.6';
                        $this->updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case ($key == "wp"):

                        if ($previousValue) {
                            echo "This is previous $previousValue and this is key $key";
                        }
                        if ($previousValue == "php7") {
                            $PHP_flag  = '7.0';
                        } else {
                            $PHP_flag  = '5.6';
                        }
                        $previousValue = $key;

                        fclose($handler);
                        unlink($filename);

                        $handler    = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename   = "$structure/$name.txt";

                        $site_type  = 'WordPress';
                        $cache_type = 'disabled';
                        $Mysql_flag = '5.6';
                        $this->updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case ($key == "wpredis"):
                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }

                        if ($previousValue == null) {
                            $previousValue = $key;
                        }
                        echo "Previous value before fclose $previousValue\n";
                        fclose($handler);
                        unlink($filename);

                        $handler    = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename   = "$structure/$name.txt";
                        $site_type  = 'WordPress';
                        $cache_type = 'Redis';
                        $Mysql_flag = '5.6';
                        $this->updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                    case ($key == "wpfc"):

                        if ($previousValue == "php7") {
                            $PHP_flag = '7.0';
                        } else {
                            $PHP_flag = '5.6';
                        }
                        if ($previousValue) {
                            $previousValue = $key;
                        }

                        fclose($handler);
                        unlink($filename);

                        $handler    = fopen($my_file, 'w') or die('Cannot open file: ' . $my_file);
                        $filename   = "$structure/$name.txt";

                        $site_type  = 'WordPress';
                        $cache_type = 'FastCGI';
                        $Mysql_flag = '5.6';
                        $this->updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        $this->writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag);
                        break;
                }
            }
        }
        if ($file = fopen($my_file, 'r')) {
            while (!feof($file)) {
                print(fread($file, filesize($my_file)));
            }
            fclose($file);
            WP_CLI::success( $name . ' updated successfully' );

        }
    }


    /**
     * Updates the site in database.
     *
     * @param $name
     * @param $site_type
     * @param $cache_type
     * @param $PHP_flag
     * @param $Mysql_flag
     * @when before_wp_load
     */

    public function updatedb($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag) {
        $db = new SQLite3('/Users/shantanudeshpande/test-wpcli/ee.db');
        $site_name = $name;
        $result = $db->exec(
            "UPDATE ee set 
                    site_type  = '$site_type',
                    cache_type = '$cache_type',
                    php_flag   = '$PHP_flag',
                    mysql_flag = '$Mysql_flag'
                    WHERE site_name = '$site_name'");


        $errcode = $db->lastErrorCode();
        if ($errcode) {
            $db->close();
            die();
        }

        $db->close();
    }


    /**
     * Updates the site in database.
     *
     * @param $name
     * @param $site_type
     * @param $cache_type
     * @param $PHP_flag
     * @param $Mysql_flag
     * @when before_wp_load
     */

    public function writefile($name, $site_type, $cache_type, $PHP_flag, $Mysql_flag) {

        $webroot_path = getenv('WEBROOT_PATH');

        $structure    = "$webroot_path/$name";
        $my_file      = "$structure/$name.txt";
        $handle       = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file
        $filename     = "$structure/$name.txt";

        $filecontent  = file_get_contents($filename);
        $filecontent .= "site-type = $site_type
cache-type = $cache_type
PHP = $PHP_flag
Mysql = $Mysql_flag
";
        file_put_contents($filename, $filecontent);
    }
}

class EE_Site_List_Command extends WP_CLI_Command
{

    /**
     * Updates the specified website.
     *
     * Example:
     * wp ee site list
     *
     * @param string $arg Positional argument.
     * @param array $assoc_args Associative argument.
     *
     * @when before_wp_load
     */

    public function __invoke($arg, $assoc_args)
    {
        if (!empty($arg)) {
            WP_CLI::error('Invalid argument!');
        } else {
            if ( $assoc_args == null ) {
                $this->listfiles();
            }
            foreach ($assoc_args as $key => $value) {
                if ($value) {
                    switch ($key) {
                        case ($key == "enabled"):
                            $this->listfiles();
                            break;

                        case ($key == "disabled"):
                            $this->listfiles();
                            break;

                        default:
                            $this->listfiles();

                    }
                }
            }
        }
    }

    public function listfiles()
    {
        $webroot_path = getenv('WEBROOT_PATH');
        $files = preg_grep('/^([^.])/', scandir($webroot_path));
        foreach ($files as $key => $value) {
            //echo "$value\n";
            WP_CLI::line( WP_CLI::colorize( "%c" . $value . "%n " ) );
        }

    }
}

class EE_Site_Show_Command extends WP_CLI_Command {

    /**
     * Shows the specified website.
     *
     * Example:
     * wp ee site show example.com
     *
     * @param string $arg Positional argument.
     *
     * @when before_wp_load
     */

    public function __invoke($arg) {

        if (empty($arg)) {
            WP_CLI::error('Please enter site name!');
        } else {
            $name = $arg[0];

            $webroot_path = getenv('WEBROOT_PATH');
            $out          = file_get_contents("$webroot_path/$name/$name.txt");
            print_r($out);

        }
    }
}

class EE_Site_Info_Command extends WP_CLI_Command {

    /**
     * Shows information about a site
     *
     * Example:
     * wp ee site info example.com
     *
     * @param string $arg Positional argument.
     *
     * @when before_wp_load
     */

    public function __invoke($arg, $assoc_args) {
        if (empty($arg)) {
            WP_CLI::error('Please specify site name');
        } else {
            $site_name = $arg[0];
            $db = new SQLite3('/Users/shantanudeshpande/test-wpcli/ee.db');
            $result = $db->query(
                "SELECT * FROM ee WHERE ee.site_name = '$site_name'");

            $errcode = $db->lastErrorCode();
            if ($errcode) {
                die();
                $db->close();
            }

            $vals[] = $result->fetchArray();
            $fields = [
                'id',
                'site_name',
                'site_type',
                'cache_type',
                'php_flag',
                'mysql_flag'
            ];
            WP_CLI\Utils\format_items( 'table', $vals, $fields );

            $db->close();
        }
    }

}

WP_CLI::add_command('ee site create', 'EE_Site_Create_Command');
WP_CLI::add_command('ee site delete', 'EE_Site_Delete_Command');
WP_CLI::add_command('ee site update', 'EE_Site_Update_Command');
WP_CLI::add_command('ee site list'  , 'EE_Site_List_Command'  );
WP_CLI::add_command('ee site show'  , 'EE_Site_Show_Command'  );
WP_CLI::add_command('ee site info'  , 'EE_Site_Info_Command'  );


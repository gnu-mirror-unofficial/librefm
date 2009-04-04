<?
require_once('MDB2.php');
require_once('version.php');

if(file_exists("config.php")) {
	die("A configuration file already exists. Please delete <i>config.php</i> if you wish to reinstall.");
}

if (isset($_POST['install'])) {

	//Get the database connection string
	$dbms = $_POST['dbms'];
	if($dbms == "sqlite") {
		$filename = $_POST['filename'];
		$connect_string = "sqlite:///" . $filename;
	} else {
		$connect_string = $dbms . "://" . $_POST['username'] . ":" . $_POST['password'] . "@" . $_POST['hostname'] . ":" . $_POST['port'] . "/" . $_POST['dbname'];
	}

	$mdb2 =& MDB2::connect($connect_string);
	if (PEAR::isError($mdb2)) {
		die($mdb2->getMessage());
	}

	//Create tables
	$mdb2->query("CREATE TABLE Users (username VARCHAR(64) PRIMARY KEY,
		password VARCHAR(32) NOT NULL,
		email VARCHAR(255),
		fullname VARCHAR(255),
		bio TEXT,
		homepage VARCHAR(255),
		location VARCHAR(255),
		created TIMESTAMP NOT NULL,
		modified TIMESTAMP)");

	$res = $mdb2->query("CREATE TABLE Auth (token VARCHAR(32) PRIMARY KEY,
		sk VARCHAR(32),
		expires TIMESTAMP,
		username VARCHAR(255) REFERENCES Users(username))");

	$mdb2->query("CREATE TABLE Artist(
		name VARCHAR(255) PRIMARY KEY,
		mbid VARCHAR(36),
		streamable int,
		bio_published TIMESTAMP,
		bio_content TEXT)");

	$mdb2->query("CREATE TABLE Album(
		id int PRIMARY KEY,
		name VARCHAR(255),
		artist_name VARCHAR(255) REFERENCES Artist(name) ,
		mbid VARCHAR(36) ,
		releasedate DATE,
		listeners int,
		playcount int)");

	// Table for registering similar artists
	$mdb2->query("CREATE TABLE Similar_Artist(
		name_a VARCHAR(255) REFERENCES Artist(name),
		name_b VARCHAR(255) REFERENCES Artist(name),
		PRIMARY KEY(name_a, name_b))");

	// Test user configuration
	$res = $mdb2->query("INSERT INTO Users
		(username, password, created)
		VALUES
		('testuser', '" . md5('password') . "', " . time() . ")");
	
	$mdb2->disconnect();

	$submissions_server = $_POST['submissions'];

	//Write out the configuration
	$config = "<? \$config_version = " . $version .";\n \$connect_string = '" . $connect_string . "';\n \$submissions_server = '" . $submissions_server . "'; ?>";

	$conf_file = fopen("config.php", "w");
	$result = fwrite($conf_file, $config);
	fclose($conf_file);

	if(!$result) {
		$print_config = str_replace("<", "&lt;", $config);
		die("Unable to write to file '<i>config.php</i>'. Please create this file and copy the following in to it: <br /><pre>" . $print_config . "</pre>");
	}	

	die("Configuration completed successfully!");
}

?>
<html>
	<head>
		<title>Gobbler Installer</title>
		<script type='text/javascript'>
			function showSqlite() {
				document.getElementById("sqlite").style.visibility = "visible";
				document.getElementById("networkdbms").style.visibility = "hidden";
			}

			function showNetworkDBMS() {
				document.getElementById("sqlite").style.visibility = "hidden";
				document.getElementById("networkdbms").style.visibility = "visible";
			}
		</script>
	</head>

	<body onload="showSqlite()">
		<h1>Gobbler Installer</h1>
		<form method="post">
			<h2>Database</h2>
			Database Management System: <br />
			<input type="radio" name="dbms" value="sqlite" onclick='showSqlite()' checked>SQLite (use an absolute path)</input><br />
			<input type="radio" name="dbms" value="mysql" onclick='showNetworkDBMS()'>MySQL</input><br />
			<input type="radio" name="dbms" value="pgsql" onclick='showNetworkDBMS()'>PostgreSQL</input><br />
			<br />
			<div id="sqlite">
				Filename: <input type="text" name="filename" /><br />
			</div>
			<div id="networkdbms">
				Hostname: <input type="text" name="hostname" /><br />
				Port: <input type="text" name="port" /><br />
				Database: <input type="text" name="dbname" /><br />
				Username: <input type="text" name="username" /><br />
				Password: <input type="password" name="password" /><br />
			</div>
			<br />
			<h2>Servers</h2>
			Submissions Server URL: <input type="text" name="submissions" value="http://localhost/" /><br />
			<br />
			<input type="submit" value="Install" name="install" />
		</form>
	</body>
</html>



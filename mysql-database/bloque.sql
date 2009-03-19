CREATE TABLE IF NOT EXISTS `ficheros` (
	`fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(512) NOT NULL,
	`tam` INT UNSIGNED NOT NULL,
	`remitente` VARCHAR(200) NOT NULL,
	`ip` VARCHAR(20) NOT NULL,
	`fechaenvio` INT NOT NULL,
	`fechaexp` INT,
	`listar` INT(1) NOT NULL,
	`mostrar_autor` INT(1) NOT NULL,
	`tipoacceso` INT(1) NOT NULL,
	`password` VARCHAR(50),
	`descripcion` TEXT,

	PRIMARY KEY(fid));

CREATE TABLE IF NOT EXISTS `config` (
	`var` VARCHAR(40) NOT NULL,
	`valor` VARCHAR(255) NOT NULL,

	PRIMARY KEY(var));

CREATE TABLE IF NOT EXISTS `mimetypes` (
	`mid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`mimetype` VARCHAR(100) NOT NULL,
	`extension` VARCHAR(255),
	`icono` VARCHAR(100),

	PRIMARY KEY(mid));

CREATE TABLE IF NOT EXISTS  `ci_sessions` (
	session_id varchar(40) DEFAULT '0' NOT NULL,
	ip_address varchar(16) DEFAULT '0' NOT NULL,
	user_agent varchar(50) NOT NULL,
	last_activity int(10) unsigned DEFAULT 0 NOT NULL,
	user_data text NOT NULL,
	PRIMARY KEY (session_id)
);

CREATE TABLE IF NOT EXISTS `cacheldap` (
	`dn` VARCHAR(200) NOT NULL,
	`nombre` VARCHAR(255) NOT NULL,
	`relaciones` VARCHAR(255) NOT NULL,
	`mail` VARCHAR(255) NOT NULL,
	`timestamp` INT UNSIGNED NOT NULL,

	PRIMARY KEY(dn));

CREATE TABLE IF NOT EXISTS  `ci_sessions` (
	session_id varchar(40) DEFAULT '0' NOT NULL,
	ip_address varchar(16) DEFAULT '0' NOT NULL,
	user_agent varchar(50) NOT NULL,
	last_activity int(10) unsigned DEFAULT 0 NOT NULL,
	user_data text NOT NULL,
	PRIMARY KEY (session_id)
);

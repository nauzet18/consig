CREATE TABLE IF NOT EXISTS `ficheros` (
	`fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(512) NOT NULL,
	`mid` INT UNSIGNED NOT NULL,,
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

	PRIMARY KEY(fid)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `misc` (
	`nombre` VARCHAR(40) NOT NULL,
	`valor` VARCHAR(255) NOT NULL,

	PRIMARY KEY(nombre)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO misc VALUES ('versionbd', '1');

CREATE TABLE IF NOT EXISTS `mimetypes` (
	`mid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`mimetype` VARCHAR(100) NOT NULL,
	`extension` VARCHAR(255),
	`icono` VARCHAR(100),

	PRIMARY KEY(mid)) DEFAULT CHARSET=utf8;

CREATE INDEX idx_mimetype_extension
ON mimetypes(extension);


CREATE TABLE IF NOT EXISTS `usercache` (
	`id` VARCHAR(200) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`mail` VARCHAR(255) NOT NULL,
	`timestamp` INT UNSIGNED NOT NULL,

	PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ci_sessions` (
session_id varchar(40) DEFAULT '0' NOT NULL,
session_start int(10) unsigned DEFAULT 0 NOT NULL,
session_last_activity int(10) unsigned DEFAULT 0 NOT NULL,
session_ip_address varchar(16) DEFAULT '0' NOT NULL,
session_user_agent varchar(50) NOT NULL,
session_data text default '' NOT NULL,
PRIMARY KEY (session_id)
) DEFAULT CHARSET=utf8; 

CREATE INDEX idx_session_last_activity
ON ci_sessions(session_last_activity);


/*
 * Antivirus
 */

CREATE TABLE IF NOT EXISTS `antivirus` (
	`fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`estado` VARCHAR(100) NOT NULL,
	`extra` VARCHAR(255),
	`timestamp` INT NOT NULL,

	FOREIGN KEY(fid) REFERENCES ficheros(fid)
	 ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*
 * Descargas pormenorizadas
 */

CREATE TABLE IF NOT EXISTS `historicodescargas` (
	`fid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`identidad` VARCHAR(100) NOT NULL,
	`ip` VARCHAR(255),
	`timestamp` INT NOT NULL,

	FOREIGN KEY(fid) REFERENCES ficheros(fid)
	 ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8;

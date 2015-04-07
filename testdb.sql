DROP TABLE IF EXISTS car;
DROP TABLE IF EXISTS color;

CREATE TABLE color ( 
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(45) NOT NULL,
  code VARCHAR(6) NOT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE car ( 
  id INT NOT NULL AUTO_INCREMENT COMMENT '{"hidden":true}',
  name VARCHAR(45) NOT NULL DEFAULT 'VW',
  description VARCHAR(45) COMMENT '{
    "placeholder": "Bitte geben Sie eine Beschreibung ein",
    "label": "Beschreibung"
  }',
  color INT NOT NULL COMMENT '{
    "label": "Farbe",
    "format": "#{code}: {name}",
    "placeholder": "Bitte wählen Sie eine Farbe aus"
  }',
  getriebe SET('5-Gang','6-Gang') NOT NULL DEFAULT '5-Gang',
  preis FLOAT NOT NULL DEFAULT 250,
  datum DATE NOT NULL DEFAULT '2015-10-09',
  unikat bit(1),
  licenceAccepted bit(1) NOT NULL COMMENT '{
    "label": "Ich erkläre mich mit den AGB einverstanden"
  }',
  FOREIGN KEY(color) REFERENCES color(id),
  PRIMARY KEY(id)
);

INSERT INTO color (name,code) VALUES ('red','FF0000');
INSERT INTO color (name,code) VALUES ('green','00FF00');
INSERT INTO color (name,code) VALUES ('blue','0000FF');


/*
  SELECT
    c.COLUMN_TYPE AS type,
    c.COLUMN_NAME AS name,
    c.IS_NULLABLE AS nullable,
    c.COLUMN_DEFAULT AS `default`,
    r.REFERENCED_TABLE_NAME AS reftbl,
    r.REFERENCED_COLUMN_NAME AS refcol,
    c.COLUMN_COMMENT AS comment
  FROM
    INFORMATION_SCHEMA.COLUMNS AS c
  LEFT JOIN
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS r
  ON
        c.TABLE_NAME = r.TABLE_NAME
    AND c.TABLE_SCHEMA = r.TABLE_SCHEMA
    AND c.COLUMN_NAME = r.COLUMN_NAME
  WHERE
        c.TABLE_SCHEMA = 'fg_testdb'
    AND c.TABLE_NAME = 'car'
  ;
*/


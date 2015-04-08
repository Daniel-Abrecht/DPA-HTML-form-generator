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
  colorInside INT NOT NULL COMMENT '{
    "label": "Farbe innen",
    "format": "#{code}: {name}",
    "placeholder": "Bitte wählen Sie eine Farbe aus"
  }',
  colorOutside INT NOT NULL DEFAULT 2 COMMENT '{
    "label": "Farbe aussen",
    "format": "#{code}: {name}",
    "placeholder": "Bitte wählen Sie eine Farbe aus",
    "type": "radio"
  }',
  wheelColor VARCHAR(7) NOT NULL DEFAULT '#000000' COMMENT '{
    "label": "Farbe der Räder",
    "type": "color"
  }',
  getriebe SET('5-Gang','6-Gang') NOT NULL DEFAULT '5-Gang',
  tankGroese SET('10-liter','20-liter') NOT NULL COMMENT '{
    "label": "Tank grösse",
    "type":"select"
  }',
  preis FLOAT NOT NULL DEFAULT 250,
  datum DATE NOT NULL DEFAULT '2015-10-09',
  sonstiges VARCHAR(256) COMMENT '{
    "type": "textarea",
    "placeholder": "Geben sie hier weitere Anmerkungen zum Auto ein"
  }',
  unikat bit(1) DEFAULT 1,
  licenceAccepted bit(1) NOT NULL COMMENT '{
    "label": "Ich erkläre mich mit den AGB einverstanden"
  }',
  FOREIGN KEY(colorInside) REFERENCES color(id),
  FOREIGN KEY(colorOutside) REFERENCES color(id),
  PRIMARY KEY(id)
);

INSERT INTO color (name,code) VALUES ('red','FF0000');
INSERT INTO color (name,code) VALUES ('green','00FF00');
INSERT INTO color (name,code) VALUES ('blue','0000FF');



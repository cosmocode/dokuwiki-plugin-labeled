CREATE TABLE labeled ( id, label, PRIMARY KEY(id, label) );
CREATE TABLE labels ( name, color, namespace, PRIMARY KEY(name) );
CREATE INDEX idx_id ON labeled(id);
CREATE INDEX idx_name ON labels(name);
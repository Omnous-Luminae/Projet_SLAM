USE Project_HAP;

ALTER TABLE Biens
ADD COLUMN is_hidden BOOLEAN DEFAULT FALSE AFTER id_type_biens;

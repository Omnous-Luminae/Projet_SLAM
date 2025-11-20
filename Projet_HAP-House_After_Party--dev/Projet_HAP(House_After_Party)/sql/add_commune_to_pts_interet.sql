ALTER TABLE Pts_Interet
ADD COLUMN id_commune INT NULL AFTER id_type_points_interet,
ADD CONSTRAINT fk_pts_interet_commune FOREIGN KEY (id_commune) REFERENCES Commune(id_commune);

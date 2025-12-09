ALTER TABLE Animateur
ADD COLUMN telephone_animateur VARCHAR(15) NULL AFTER email_animateur,
ADD COLUMN date_naissance_animateur DATE NULL AFTER telephone_animateur;

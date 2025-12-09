-- Migration: Add total_cost field to Reservation table
-- Run this on your MySQL server (test on a copy first)

ALTER TABLE Reservation ADD COLUMN total_cost DECIMAL(10,2) NULL AFTER id_Tarif;

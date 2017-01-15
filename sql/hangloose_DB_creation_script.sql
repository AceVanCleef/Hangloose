-------------------------------------
-- hangloose DB creation script    --
-------------------------------------
-- script aimed at MariaDB         --
-------------------------------------
-------------------------------------


-- Funktioniert nicht. Syntaxfehler?
--CREATE DATABASE hangloose
--	CHARACTER SET = 'utf8mb4'
--	COLLATE = 'utf8mb4_unicode_520_ci';
	-- Source of decision: 
	-- http://stackoverflow.com/questions/2344118/utf-8-general-bin-unicode


-------------------------------------------------------------------------------------------
-- 										Anleitung:  		  					         --
-------------------------------------------------------------------------------------------
-- 1) DB hangloose manuell in phpMyAdmin anlegen mit Kollation 'utf8mb4_unicode_520_ci'. --
-- 2) DB auswählen -> auf SQL klicken und Skript unten ausführen.				         --
-------------------------------------------------------------------------------------------
-------------------------------------------------------------------------------------------


CREATE TABLE location (
	LOC_ID 				INT,
	LOC_LAT 			DOUBLE,
	LOC_LNG				DOUBLE,
	CONSTRAINT locID_pk PRIMARY KEY (LOC_ID) 
);

CREATE TABLE rating (
	RAT_ID 				INT,
	RAT_COMMENT 		LONGTEXT,
	RAT_POINTS 			TINYINT,
	RAT_TITLE 			VARCHAR(21.844),
	RAT_PICTURE_PATH 	VARCHAR(21.844),
	RAT_LOCATION_ID 	INT,
	CONSTRAINT ratingID_pk PRIMARY KEY (RAT_ID),
	CONSTRAINT ratLocationID_fk FOREIGN KEY (RAT_LOCATION_ID) REFERENCES location(LOC_ID)
);
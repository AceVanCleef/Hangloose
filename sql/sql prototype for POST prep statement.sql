-----------------------------------------------------
-- Under Development: sql query for hangloose POST --
-----------------------------------------------------

-- some guidance: 
-- http://stackoverflow.com/questions/12475850/how-can-an-sql-query-return-data-from-multiple-tables

SELECT * 
FROM `rating` 
INNER JOIN location 
WHERE rating.RAT_LOCATION_ID = location.LOC_ID 
	AND location.LOC_LAT = 4.444 
	AND location.LOC_LNG = 8.888;



-----------------------------------------------------
-- Planning (what to do)
-----------------------------------------------------

-- Receiving: json data
-- 	 {
--    	lat         : 4.444,
--     	lng         : 8.888,
--    	ratPoints   : 4,
--     	ratTitle    : abc,
--     	ratText     : 123,
--     	imgPath     : url
--   }

-- Need to do
-- 1) check wheter location alredy exists
-- 1.a) if yes, store the rating with the foreign key to that location
-- 1.b) if no, store the rating AND the location, 
--		then save the LOC_ID as FK in RAT_LOCATION_ID


-- when I want to check wheter a location already exists within the DB:
SELECT *
FROM location
WHERE LOC_LNG = 8.888 AND LOC_LAT = 4.444;
-- status: verified.
-- remark: don't forget to replace numeric values with variables (-> Binding)

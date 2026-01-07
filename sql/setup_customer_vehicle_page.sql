-- Setup script for combined Customer & Vehicle page
-- Run this script to add the new page and hide the old separate pages

-- 1. Add the new combined Customer & Vehicle page
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active) 
VALUES ('Customer & Vehicle', 'views/CustomerVehicle/', 'Customer & Vehicle', 'Manage customers and their vehicles in one place', 'fas fa-users-cog', 1, 1)
ON DUPLICATE KEY UPDATE page_name = VALUES(page_name);

-- 2. Get the IDs of the pages we need to modify
SET @new_page_id = (SELECT id FROM pages WHERE page_route = 'views/CustomerVehicle/' LIMIT 1);
SET @customers_page_id = (SELECT id FROM pages WHERE page_route = 'views/Customer/' LIMIT 1);
SET @vehicles_page_id = (SELECT id FROM pages WHERE page_route = 'views/Vehicle/' LIMIT 1);

-- 3. For each company, hide old pages and show new page
-- First, make the new page visible for all companies
INSERT INTO sidebar_modules (company_id, page_id, is_visible)
SELECT DISTINCT company_id, @new_page_id, 1 
FROM sidebar_modules 
WHERE @new_page_id IS NOT NULL
ON DUPLICATE KEY UPDATE is_visible = 1;

-- 4. Hide the old Customers page for all companies
UPDATE sidebar_modules SET is_visible = 0 WHERE page_id = @customers_page_id;

-- 5. Hide the old Vehicles page for all companies  
UPDATE sidebar_modules SET is_visible = 0 WHERE page_id = @vehicles_page_id;

-- Verify the changes
SELECT p.page_name, p.page_route, sm.is_visible, sm.company_id
FROM pages p
LEFT JOIN sidebar_modules sm ON p.id = sm.page_id
WHERE p.page_route IN ('views/CustomerVehicle/', 'views/Customer/', 'views/Vehicle/')
ORDER BY p.page_route, sm.company_id;

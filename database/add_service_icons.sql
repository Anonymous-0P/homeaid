-- HomeAid Database Migration: Add Icon Support to Services
-- Run this SQL to add icon functionality

-- 1. Add icon_key column to services table
ALTER TABLE services ADD COLUMN icon_key VARCHAR(50) DEFAULT 'toolbox';

-- 2. Update existing services with appropriate default icons
UPDATE services SET icon_key = 'wrench' WHERE name LIKE '%plumb%';
UPDATE services SET icon_key = 'electrical' WHERE name LIKE '%electric%';
UPDATE services SET icon_key = 'cleaning' WHERE name LIKE '%clean%';
UPDATE services SET icon_key = 'garden' WHERE name LIKE '%garden%';
UPDATE services SET icon_key = 'pest' WHERE name LIKE '%pest%';
UPDATE services SET icon_key = 'paint' WHERE name LIKE '%paint%';
UPDATE services SET icon_key = 'appliance' WHERE name LIKE '%appliance%';
UPDATE services SET icon_key = 'security' WHERE name LIKE '%security%';
UPDATE services SET icon_key = 'roof' WHERE name LIKE '%roof%';
UPDATE services SET icon_key = 'saw' WHERE name LIKE '%carpentry%';
UPDATE services SET icon_key = 'snowflake' WHERE name LIKE '%hvac%';
UPDATE services SET icon_key = 'hammer' WHERE name LIKE '%repair%';

-- 3. Insert any missing core services with icons
INSERT IGNORE INTO services (name, description, icon_key) VALUES
('plumbing', 'Expert plumbers for leak repairs, pipe installation, and water heater services', 'wrench'),
('electrical', 'Licensed electricians for wiring, repairs, and smart home installations', 'electrical'),
('cleaning', 'Professional deep cleaning for homes, offices, and post-construction sites', 'cleaning'),
('gardening', 'Landscaping, lawn care, and garden maintenance services', 'garden'),
('pest control', 'Eco-friendly pest elimination and preventive protection for your home', 'pest'),
('painting', 'Interior & exterior painting with professional surface prep and finish', 'paint'),
('appliance repair', 'Fast diagnostics and repairs for all major household appliances', 'appliance'),
('home security', 'Smart surveillance, alarms, and secure access installations', 'security'),
('roofing', 'Roof installation, leak repair, and weatherproof maintenance', 'roof'),
('carpentry', 'Custom woodwork, repairs, and installations for interiors and outdoors', 'saw'),
('hvac services', 'AC repair, installation, and maintenance for year-round comfort', 'snowflake'),
('home repair', 'Skilled handymen for painting, carpentry, and general maintenance', 'hammer');
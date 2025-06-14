-- Step 8: Create Asset Management Tables

-- Create Asset Type Master table
CREATE TABLE IF NOT EXISTS asset_type_master (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL UNIQUE,
    keyword VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert asset types
INSERT INTO asset_type_master (type, keyword) VALUES
('Laptop', 'LAP'),
('Desktop', 'DSK'),
('Mouse', 'MOU'),
('Keyboard', 'KEY'),
('Mobile', 'MOB')
ON CONFLICT (type) DO NOTHING;

-- Create Location Master table
CREATE TABLE IF NOT EXISTS location_master (
    id SERIAL PRIMARY KEY,
    unique_location VARCHAR(255) NOT NULL UNIQUE,
    total_assets INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample locations
INSERT INTO location_master (unique_location, total_assets) VALUES
('Office Floor 1', 0),
('Office Floor 2', 0),
('Office Floor 3', 0),
('Remote Work', 0),
('Conference Room A', 0),
('Conference Room B', 0),
('Storage Room', 0),
('IT Department', 0)
ON CONFLICT (unique_location) DO NOTHING;

-- Create ownership enum type
DO $$ BEGIN
    CREATE TYPE ownership_type AS ENUM ('SGPL', 'Rental', 'BYOD');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create warranty enum type
DO $$ BEGIN
    CREATE TYPE warranty_type AS ENUM ('Under Warranty', 'NA', 'Out of Warranty');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create asset status enum type
DO $$ BEGIN
    CREATE TYPE asset_status_type AS ENUM ('active', 'inactive', 'decommissioned');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create allocation status enum type
DO $$ BEGIN
    CREATE TYPE allocation_status_type AS ENUM ('active', 'inactive');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Create Asset Master table
CREATE TABLE IF NOT EXISTS asset_master (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    ownership ownership_type NOT NULL,
    warranty warranty_type NOT NULL,
    warranty_start DATE NULL,
    warranty_end DATE NULL,
    serial_number VARCHAR(30) NOT NULL UNIQUE,
    tag VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status asset_status_type DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type) REFERENCES asset_type_master(type) ON UPDATE CASCADE,
    FOREIGN KEY (location) REFERENCES location_master(unique_location) ON UPDATE CASCADE
);

-- Create Allocated Asset Master table
CREATE TABLE IF NOT EXISTS allocated_asset_master (
    id SERIAL PRIMARY KEY,
    asset_tag VARCHAR(20) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    assign_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status allocation_status_type DEFAULT 'active',
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_tag) REFERENCES asset_master(tag) ON UPDATE CASCADE ON DELETE CASCADE
);

-- Insert sample assets
INSERT INTO asset_master (type, ownership, warranty, warranty_start, warranty_end, serial_number, tag, model, location, status) VALUES
('Laptop', 'SGPL', 'Under Warranty', '2024-01-01', '2026-01-01', 'DL123456789', 'FINLAP00001', 'Dell Latitude 5520', 'Office Floor 1', 'inactive'),
('Laptop', 'SGPL', 'Under Warranty', '2024-02-01', '2026-02-01', 'HP987654321', 'FINLAP00002', 'HP EliteBook 840', 'Office Floor 2', 'active'),
('Desktop', 'SGPL', 'Under Warranty', '2024-01-15', '2026-01-15', 'DP111222333', 'FINDSK00001', 'Dell OptiPlex 7090', 'Office Floor 1', 'inactive'),
('Mobile', 'Rental', 'NA', NULL, NULL, 'IP555666777', 'EXTMOB00001', 'iPhone 14 Pro', 'Remote Work', 'active'),
('Mouse', 'SGPL', 'Out of Warranty', NULL, NULL, 'LM888999000', 'FINMOU00001', 'Logitech MX Master 3', 'Office Floor 1', 'inactive'),
('Keyboard', 'SGPL', 'Under Warranty', '2024-03-01', '2025-03-01', 'LK111333555', 'FINKEY00001', 'Logitech K380', 'Office Floor 2', 'inactive'),
('Laptop', 'BYOD', 'NA', NULL, NULL, 'MB777888999', 'EXTLAP00001', 'MacBook Pro M2', 'Remote Work', 'active'),
('Desktop', 'SGPL', 'Under Warranty', '2024-04-01', '2026-04-01', 'DP444555666', 'FINDSK00002', 'HP EliteDesk 800', 'IT Department', 'inactive')
ON CONFLICT (serial_number) DO NOTHING;

-- Insert sample allocations
INSERT INTO allocated_asset_master (asset_tag, user_email, assign_on, status, end_date) VALUES
('FINLAP00002', 'john.doe@company.com', '2024-01-15 09:00:00', 'active', NULL),
('EXTMOB00001', 'jane.smith@company.com', '2024-02-01 10:30:00', 'active', NULL),
('EXTLAP00001', 'mike.wilson@company.com', '2024-03-01 11:00:00', 'active', NULL)
ON CONFLICT DO NOTHING;

-- Update asset status for allocated items
UPDATE asset_master SET status = 'active' WHERE tag IN ('FINLAP00002', 'EXTMOB00001', 'EXTLAP00001');

-- Drop old tables if they exist
DROP TABLE IF EXISTS all_asset_table CASCADE;
DROP TABLE IF EXISTS asset_requests CASCADE;

-- Step 10: Check results
SELECT 'Groups Table:' as result_type;
SELECT * FROM groups WHERE name IN ('IT team', 'FinFinity IT', 'SGPLDPT_IT', 'SGPL ALL USERS');

SELECT 'Access Control Table:' as result_type;
SELECT microsoft_group_name, link_name, link_url, replaces_link, is_active
FROM group_personalized_links 
ORDER BY microsoft_group_name; 

SELECT 'Asset Statistics:' as result_type;
SELECT 
    COUNT(*) as total_assets,
    COUNT(CASE WHEN status = 'Active' AND commissioned_to IS NOT NULL THEN 1 END) as in_use,
    COUNT(CASE WHEN status = 'Inactive' THEN 1 END) as available,
    COUNT(CASE WHEN status = 'Maintenance' THEN 1 END) as maintenance,
    COUNT(CASE WHEN status = 'Decommissioned' THEN 1 END) as decommissioned
FROM all_asset_table;

SELECT 'Sample Assets:' as result_type;
SELECT asset_id, asset_name, asset_type, commissioned_to, status 
FROM all_asset_table 
ORDER BY asset_type, asset_id; 
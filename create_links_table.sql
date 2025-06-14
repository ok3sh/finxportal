-- Create the missing links table for default links
-- This table stores the base links that all users see (Keka, Zoho, etc.)

CREATE TABLE IF NOT EXISTS links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    logo_path VARCHAR(255) NULL,
    logo_url VARCHAR(255) NULL,
    background_color VARCHAR(10) DEFAULT '#115948',
    sort_order INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert the default links that all users see
INSERT INTO links (name, url, logo_url, background_color, sort_order, is_active) VALUES
('Keka', 'https://keka.com', '/assets/keka.png', '#115948', 1, true),
('Zoho', 'https://zoho.com', '/assets/zoho.png', '#115948', 2, true),
('Outlook', 'https://outlook.office.com', '/assets/outlook.png', '#115948', 3, true);

-- Verify the data was inserted
SELECT 'Default Links Created:' as status;
SELECT id, name, url, logo_url, background_color, sort_order, is_active
FROM links 
ORDER BY sort_order; 
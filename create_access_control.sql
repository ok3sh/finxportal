-- Create access control table for group-based personalized links
CREATE TABLE group_personalized_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    microsoft_group_name VARCHAR(255) NOT NULL,
    link_name VARCHAR(255) NOT NULL,
    link_url VARCHAR(500) NOT NULL,
    link_logo VARCHAR(255) NULL,
    background_color VARCHAR(10) DEFAULT '#115948',
    sort_order INT DEFAULT 1,
    replaces_link VARCHAR(255) NULL COMMENT 'Which default link this replaces (e.g., "outlook")',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_replaces (microsoft_group_name, replaces_link)
);

-- Insert sample access control data
INSERT INTO group_personalized_links (microsoft_group_name, link_name, link_url, link_logo, background_color, sort_order, replaces_link) VALUES
('IT Admin', 'Asset Management', 'https://asset-management.company.com', '/assets/asset-management.png', '#2563eb', 1, 'outlook'),
('HR Admin', 'HR Management', 'https://hr-management.company.com', '/assets/hr-management.png', '#059669', 1, 'outlook'),
('Finance Team', 'Financial Dashboard', 'https://finance-dashboard.company.com', '/assets/finance.png', '#dc2626', 1, 'outlook'),
('Marketing Team', 'Campaign Manager', 'https://campaigns.company.com', '/assets/marketing.png', '#7c3aed', 1, 'outlook'),
('Support Team', 'Ticket System', 'https://support-tickets.company.com', '/assets/support.png', '#ea580c', 1, 'outlook');

-- Create additional personalized links (not replacements, but additions)
INSERT INTO group_personalized_links (microsoft_group_name, link_name, link_url, link_logo, background_color, sort_order, replaces_link) VALUES
('IT Admin', 'Server Monitoring', 'https://monitoring.company.com', '/assets/monitoring.png', '#1f2937', 2, NULL),
('IT Admin', 'Network Tools', 'https://network-tools.company.com', '/assets/network.png', '#374151', 3, NULL),
('HR Admin', 'Employee Portal', 'https://employee-portal.company.com', '/assets/employee.png', '#065f46', 2, NULL),
('Finance Team', 'Budget Tracker', 'https://budget.company.com', '/assets/budget.png', '#991b1b', 2, NULL);

-- Check the inserted data
SELECT * FROM group_personalized_links ORDER BY microsoft_group_name, sort_order; 
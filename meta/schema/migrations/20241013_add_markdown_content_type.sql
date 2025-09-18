INSERT INTO content_type (type_name)
SELECT 'Markdown'
WHERE NOT EXISTS (
    SELECT 1 FROM content_type WHERE type_name = 'Markdown'
);

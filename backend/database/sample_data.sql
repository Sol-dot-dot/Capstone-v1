-- Sample data for Book Borrowing System
-- Run this after schema.sql

-- Insert book categories
INSERT INTO categories (name, description, icon, color) VALUES
('Fiction', 'Novels, short stories, and fictional works', 'auto_stories', '#9b59b6'),
('Biographies', 'Life stories and memoirs', 'person', '#e74c3c'),
('Engineering', 'Technical and engineering books', 'engineering', '#3498db'),
('History', 'Historical books and documentaries', 'history_edu', '#f39c12'),
('Science', 'Scientific research and discoveries', 'science', '#2ecc71'),
('Technology', 'Computer science and technology', 'computer', '#34495e'),
('Literature', 'Classic and contemporary literature', 'menu_book', '#8e44ad'),
('Business', 'Business and management books', 'business', '#16a085');

-- Insert authors
INSERT INTO authors (first_name, last_name, biography, nationality) VALUES
('F. Scott', 'Fitzgerald', 'American novelist and short story writer', 'American'),
('George R.R.', 'Martin', 'American novelist and short story writer', 'American'),
('John', 'Green', 'American author and YouTube content creator', 'American'),
('Patrick', 'Ness', 'British-American author', 'British-American'),
('Nirat', 'Bhatnagar', 'Contemporary author', 'Indian'),
('Dale', 'Carnegie', 'American writer and lecturer', 'American'),
('Robert', 'Kiyosaki', 'American businessman and author', 'American'),
('Stephen', 'Hawking', 'British theoretical physicist', 'British');

-- Insert books
INSERT INTO books (isbn, title, subtitle, description, publication_date, publisher, pages, category_id, total_copies, available_copies) VALUES
('978-0-7432-7356-5', 'The Great Gatsby', NULL, 'A classic American novel set in the Jazz Age', '1925-04-10', 'Scribner', 180, 1, 5, 3),
('978-0-553-10354-0', 'A Game of Thrones', 'A Song of Ice and Fire Book 1', 'Epic fantasy novel', '1996-08-01', 'Bantam Spectra', 694, 1, 3, 1),
('978-0-14-242417-9', 'The Fault in Our Stars', NULL, 'Young adult romance novel', '2012-01-10', 'Dutton Books', 313, 1, 4, 2),
('978-1-4063-4971-7', 'More Than This', NULL, 'Young adult science fiction', '2013-09-10', 'Walker Books', 472, 1, 2, 1),
('978-0-143-12345-6', 'Thinking About Water', 'Environmental Perspectives', 'Environmental science and water conservation', '2020-03-15', 'Green Press', 256, 5, 3, 2),
('978-0-671-02111-7', 'How to Win Friends and Influence People', NULL, 'Self-help and interpersonal skills', '1936-10-01', 'Simon & Schuster', 291, 8, 6, 4),
('978-1-61219-909-4', 'Rich Dad Poor Dad', 'What the Rich Teach Their Kids About Money', 'Personal finance education', '1997-04-01', 'Plata Publishing', 336, 8, 4, 2),
('978-0-553-38016-3', 'A Brief History of Time', 'From the Big Bang to Black Holes', 'Popular science book on cosmology', '1988-04-01', 'Bantam Dell', 256, 5, 3, 1);

-- Link books to authors
INSERT INTO book_authors (book_id, author_id) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7), (8, 8);

-- Insert sample borrowings
INSERT INTO borrowings (student_id, book_id, borrowed_date, due_date, status) VALUES
('C22-0044', 1, '2024-01-15 10:30:00', '2024-02-15', 'active'),
('C22-0044', 3, '2024-01-20 14:15:00', '2024-02-20', 'active'),
('C22-0055', 2, '2024-01-10 09:45:00', '2024-02-10', 'returned'),
('C21-0123', 6, '2024-01-25 11:20:00', '2024-02-25', 'active');

-- Insert book reviews
INSERT INTO book_reviews (student_id, book_id, rating, review_text) VALUES
('C22-0055', 2, 5, 'Amazing fantasy world with complex characters!'),
('C21-0123', 6, 4, 'Great advice for personal development and networking.'),
('C22-0044', 1, 4, 'Classic American literature at its finest.');

-- Insert bookmarks
INSERT INTO bookmarks (student_id, book_id) VALUES
('C22-0044', 4),
('C22-0044', 8),
('C22-0055', 5),
('C23-0001', 7);

-- Insert reading preferences (AI learning data)
INSERT INTO reading_preferences (student_id, category_id, preference_score) VALUES
('C22-0044', 1, 0.85), -- Fiction
('C22-0044', 5, 0.70), -- Science
('C22-0055', 1, 0.90), -- Fiction
('C22-0055', 4, 0.65), -- History
('C21-0123', 8, 0.80), -- Business
('C21-0123', 6, 0.75); -- Technology

-- Insert AI recommendations
INSERT INTO recommendations (student_id, book_id, recommendation_score, reason, algorithm_version) VALUES
('C22-0044', 4, 0.88, 'Based on your interest in fiction and science fiction themes', 'v1.0'),
('C22-0044', 8, 0.82, 'Recommended due to your science category preference', 'v1.0'),
('C22-0055', 7, 0.75, 'Popular among students with similar reading patterns', 'v1.0'),
('C21-0123', 5, 0.70, 'Environmental topics align with your technical interests', 'v1.0');

-- Insert notifications
INSERT INTO notifications (student_id, title, message, type) VALUES
('C22-0044', 'Book Due Soon', 'The Great Gatsby is due in 3 days', 'reminder'),
('C22-0044', 'New Recommendation', 'We found a new book you might like: More Than This', 'recommendation'),
('C22-0055', 'Return Confirmed', 'Thank you for returning A Game of Thrones', 'info'),
('C21-0123', 'Overdue Notice', 'How to Win Friends and Influence People is overdue', 'warning');

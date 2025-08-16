<?php
require_once 'config.php';

echo "=== ENHANCING DATABASE FOR ADVANCED FEATURES ===<br><br>";

try {
    // Add reading preferences table for RAG recommendations
    echo "1. Creating reading_preferences table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            category_id INT NOT NULL,
            preference_score DECIMAL(3,2) DEFAULT 0.50,
            interaction_count INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_category (student_id, category_id)
        )
    ");
    echo "✅ Reading preferences table created\n";

    // Add book embeddings table for RAG
    echo "2. Creating book_embeddings table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS book_embeddings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            embedding_vector TEXT,
            content_summary TEXT,
            keywords TEXT,
            genre_tags TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Book embeddings table created\n";

    // Add reading sessions for engagement tracking
    echo "3. Creating reading_sessions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reading_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            session_end TIMESTAMP NULL,
            pages_read INT DEFAULT 0,
            engagement_score DECIMAL(3,2) DEFAULT 0.00,
            notes TEXT,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Reading sessions table created\n";

    // Add recommendation history
    echo "4. Creating recommendation_history table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recommendation_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            book_id INT NOT NULL,
            recommendation_type ENUM('collaborative', 'content_based', 'rag_enhanced', 'trending') DEFAULT 'content_based',
            confidence_score DECIMAL(3,2) DEFAULT 0.50,
            was_borrowed BOOLEAN DEFAULT FALSE,
            was_bookmarked BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Recommendation history table created\n";

    // Add book analytics
    echo "5. Creating book_analytics table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS book_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            total_views INT DEFAULT 0,
            total_borrows INT DEFAULT 0,
            total_bookmarks INT DEFAULT 0,
            avg_rating DECIMAL(3,2) DEFAULT 0.00,
            popularity_score DECIMAL(5,2) DEFAULT 0.00,
            trending_score DECIMAL(5,2) DEFAULT 0.00,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            UNIQUE KEY unique_book (book_id)
        )
    ");
    echo "✅ Book analytics table created\n";

    // Add search history for personalization
    echo "6. Creating search_history table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) NOT NULL,
            search_query VARCHAR(255) NOT NULL,
            search_type ENUM('title', 'author', 'category', 'general') DEFAULT 'general',
            results_count INT DEFAULT 0,
            clicked_book_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (clicked_book_id) REFERENCES books(id) ON DELETE SET NULL
        )
    ");
    echo "✅ Search history table created\n";

    // Insert sample reading preferences
    echo "7. Inserting sample reading preferences...\n";
    $preferences = [
        ['C22-0044', 1, 0.85], // Fiction
        ['C22-0044', 2, 0.70], // Science
        ['C22-0044', 3, 0.60], // Technology
        ['C22-0045', 4, 0.90], // History
        ['C22-0045', 1, 0.75], // Fiction
        ['C22-0046', 2, 0.95], // Science
        ['C22-0046', 5, 0.80], // Mathematics
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO reading_preferences (student_id, category_id, preference_score) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($preferences as $pref) {
        $stmt->execute($pref);
    }
    echo "✅ Sample reading preferences inserted\n";

    // Insert book analytics
    echo "8. Initializing book analytics...\n";
    $pdo->exec("
        INSERT IGNORE INTO book_analytics (book_id, total_views, total_borrows, total_bookmarks, avg_rating, popularity_score)
        SELECT 
            b.id,
            FLOOR(RAND() * 100) + 10 as total_views,
            COALESCE(borrow_count.count, 0) as total_borrows,
            COALESCE(bookmark_count.count, 0) as total_bookmarks,
            COALESCE(b.rating, 0) as avg_rating,
            (COALESCE(borrow_count.count, 0) * 0.4 + 
             COALESCE(bookmark_count.count, 0) * 0.3 + 
             COALESCE(b.rating, 0) * 0.3) as popularity_score
        FROM books b
        LEFT JOIN (
            SELECT book_id, COUNT(*) as count 
            FROM borrowings 
            GROUP BY book_id
        ) borrow_count ON b.id = borrow_count.book_id
        LEFT JOIN (
            SELECT book_id, COUNT(*) as count 
            FROM bookmarks 
            GROUP BY book_id
        ) bookmark_count ON b.id = bookmark_count.book_id
    ");
    echo "✅ Book analytics initialized\n";

    // Insert sample book embeddings (simplified)
    echo "9. Creating sample book embeddings...\n";
    $embeddings = [
        [1, 'Classic literature with themes of social criticism and human nature', 'classic,literature,social,criticism,human nature'],
        [2, 'Epic fantasy adventure with magic and heroic quests', 'fantasy,magic,adventure,quest,epic'],
        [3, 'Science fiction exploring technology and future society', 'science fiction,technology,future,society,AI'],
        [4, 'Historical account of world events and their impact', 'history,world events,war,politics,society'],
        [5, 'Mathematical concepts and problem-solving techniques', 'mathematics,calculus,problem solving,education'],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO book_embeddings (book_id, content_summary, keywords) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($embeddings as $embedding) {
        $stmt->execute($embedding);
    }
    echo "✅ Sample book embeddings created\n";

    // Add more sample notifications for engagement
    echo "10. Adding engagement notifications...\n";
    $notifications = [
        ['C22-0044', 'recommendation', 'Based on your reading history, we recommend "The Great Gatsby"', 0],
        ['C22-0044', 'reading_goal', 'You\'re halfway to your monthly reading goal! Keep it up!', 0],
        ['C22-0045', 'new_arrival', 'New books in History category are now available', 0],
        ['C22-0046', 'achievement', 'Congratulations! You\'ve read 5 books this month', 0],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO notifications (student_id, type, message, is_read) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($notifications as $notif) {
        $stmt->execute($notif);
    }
    echo "✅ Engagement notifications added\n";

    echo "\n🎉 DATABASE ENHANCEMENT COMPLETE!\n";
    echo "Advanced features now available:\n";
    echo "- ✅ Reading preferences tracking\n";
    echo "- ✅ Book embeddings for RAG recommendations\n";
    echo "- ✅ Reading session analytics\n";
    echo "- ✅ Recommendation history\n";
    echo "- ✅ Book popularity analytics\n";
    echo "- ✅ Search personalization\n";
    echo "- ✅ Engagement notifications\n";

} catch (Exception $e) {
    echo "❌ Error enhancing database: " . $e->getMessage() . "\n";
}
?>

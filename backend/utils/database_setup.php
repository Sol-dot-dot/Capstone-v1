<?php
/**
 * Database Setup Utilities
 * Consolidated database setup functions
 */

require_once '../config.php';

function setupCompleteDatabase() {
    require_once '../setup_complete_db.php';
}

function setupProductionDatabase() {
    require_once '../setup_production_db.php';
}

function quickDatabaseSetup() {
    require_once '../quick_db_setup.php';
}

function enhanceDatabase() {
    require_once '../enhance_database.php';
}

// Usage examples:
// setupCompleteDatabase();
// setupProductionDatabase();
// quickDatabaseSetup();
// enhanceDatabase();
?>

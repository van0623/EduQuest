<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/session_handler.php';

session_name("EDUQUESTSESSID");
session_set_save_handler(new EduQuestSessionHandler($conn), true);
session_start();

echo "Login page works";
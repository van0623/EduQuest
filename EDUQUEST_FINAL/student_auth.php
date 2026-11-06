<?php

require_once __DIR__ . '/init.php';

if (
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    !in_array($_SESSION["role"], ["student", "admin"], true)
) {
    header("Location: login.php");
    exit();
}
<?php
// config/database.php - Database connection configuration

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'beautyclick');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to ensure proper handling of Vietnamese characters
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize user inputs
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to execute query and handle errors
function execute_query($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    return $result;
}

// Function to get a single record
function get_record($conn, $sql) {
    $result = execute_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Function to get multiple records
function get_records($conn, $sql) {
    $result = execute_query($conn, $sql);
    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
    return $records;
}

// Function to insert a record and return the ID
function insert_record($conn, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $values = "'" . implode("', '", array_values($data)) . "'";
    
    $sql = "INSERT INTO $table ($columns) VALUES ($values)";
    if (mysqli_query($conn, $sql)) {
        return mysqli_insert_id($conn);
    } else {
        die("Insert failed: " . mysqli_error($conn));
    }
}

// Function to update a record
function update_record($conn, $table, $data, $condition) {
    $set_values = [];
    foreach ($data as $key => $value) {
        $set_values[] = "$key = '$value'";
    }
    $set_clause = implode(", ", $set_values);
    
    $sql = "UPDATE $table SET $set_clause WHERE $condition";
    return mysqli_query($conn, $sql);
}

// Function to delete a record
function delete_record($conn, $table, $condition) {
    $sql = "DELETE FROM $table WHERE $condition";
    return mysqli_query($conn, $sql);
}

// Function to count records
function count_records($conn, $table, $condition = "") {
    $sql = "SELECT COUNT(*) as count FROM $table";
    if (!empty($condition)) {
        $sql .= " WHERE $condition";
    }
    $result = get_record($conn, $sql);
    return $result['count'];
}
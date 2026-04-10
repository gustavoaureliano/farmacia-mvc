<?php

namespace App\DAO;

use PDO;
use PDOException;

class Connection
{
	private static ?PDO $conn = null;

	public static function getConn(): PDO
	{
		if (self::$conn === null) {
			$host =  '127.0.0.1';
			$port =  '3306';
			$name =  'farmacia_db';
			$user =  'farmacia_user';
			$pass = 'TROQUE_POR_UMA_SENHA_FORTE';
			$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

			try {
				self::$conn = new PDO($dsn, $user, $pass, [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				]);
			} catch (PDOException $e) {
				http_response_code(500);
				die('Database connection failed: ' . $e->getMessage());
			}
		}

		return self::$conn;
	}
}

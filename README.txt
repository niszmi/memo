MEMO MANAGEMENT SYSTEM README
Overview
The Memo Management System is part of the HR Digital initiative at FELCRA Berhad, aimed at modernizing HR processes. This system is designed to:
* Digitize memo creation and distribution, reducing reliance on paper.
* Standardize memo formats across departments for consistency.
* Create a centralized platform for easier access and management of memos.
Prerequisites
Ensure the following software is installed on the server:
* PHP 7.4 or higher
* MySQL 5.7 or higher
* Composer (for dependency management)
* GD extension for PHP
Installation and Setup
1. Unzip the Project Folder
Extract the contents of the provided ZIP file into the desired directory on the server.
2. Install Dependencies
This project requires the Dompdf library for generating PDFs and PHPMailer for sending emails. Use Composer to install these dependencies, only if vendor folder does not exist in the directory.
composer install
Ensure the composer.json file in the project directory includes the following dependencies:
{
    "require": {
        "dompdf/dompdf": "^1.0",
        "phpmailer/phpmailer": "^6.5"
    }
}
3. Database Setup
Set up the database by importing the necessary SQL files. Only the users and locations tables need to be populated initially.
mysql -u username -p database_name < path_to_sql_file.sql
4. PHP Configuration
Make the necessary adjustments to your php.ini file:
* Enable the GD extension:
extension=gd
* Set the memory limit:
memory_limit = 512M
5. Database Connection Configuration
Edit the db_connect.php file with your database connection details:
php
Copy code
<?php
$servername = "your_server";
$username = "your_username";
$password = "your_password";
$dbname = "your_dbname";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>
6. Email Configuration
Configure the email settings in the following files:
* email_memo.php
* send_reset_link.php
$mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aumairah271@gmail.com';
            $mail->Password = 'psinjunrchbzkgrz'; // Ensure this is stored securely
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            // Recipients
            $mail->setFrom('aumairah271@gmail.com', 'Password Request');
            $mail->addAddress($email);
Ensure that the mail transfer agent (MTA) on the server is correctly configured to handle outgoing emails.

7. Running the Application
After the setup, you can start the application by running a local PHP server:
php -S localhost:8000
Navigate to http://localhost:8000 in your browser to access the application.


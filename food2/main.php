<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


if (isset($_GET['session_data'])) {
    header('Content-Type: application/json');
    echo json_encode($_SESSION);
    exit;
}

// Logout the user
// clear everything in the session
if (isset($_GET['logout'])) {
    session_start();
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

function createDbConnection()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "food";

    // Create connection without selecting a database
    $conn = new mysqli($servername, $username, $password);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // check if database exists and if not, create it
    if (!mysqli_select_db($conn, $dbname)) {
        $sql = "CREATE DATABASE $dbname";
        if ($conn->query($sql) === TRUE) {
            echo "Database created successfully";
        } else {
            echo "Error creating database: " . $conn->error;
        }
    }

    // Now select the database
    $conn->select_db($dbname);

    // Array of SQL statements to create tables
    $tables = [
        "staff" => "CREATE TABLE IF NOT EXISTS staff (
            staffID int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            staffName varchar(255) DEFAULT NULL,
            staffPhoneNo varchar(15) DEFAULT NULL,
            email varchar(255) NOT NULL,
            password varchar(255) NOT NULL,
            approved tinyint(1) NOT NULL
        )",
        "order_food" => "CREATE TABLE IF NOT EXISTS order_food (
            id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            foodID int(11) DEFAULT NULL,
            custID int(11) DEFAULT NULL,
            quantity int(11) NOT NULL,
            orderDateTime datetime NOT NULL DEFAULT current_timestamp(),
            isPaid tinyint(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (foodID) REFERENCES food(id),
            FOREIGN KEY (custID) REFERENCES customer(custID)
        )",
        "food" => "CREATE TABLE IF NOT EXISTS food (
            id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            title varchar(255) DEFAULT NULL,
            subtitle varchar(255) DEFAULT NULL,
            img_src varchar(255) DEFAULT NULL,
            extra_desc varchar(255) DEFAULT NULL,
            price decimal(10,2) DEFAULT NULL,
            quantity int(11) DEFAULT NULL
        )",
        "customer_feedback" => "CREATE TABLE IF NOT EXISTS customer_feedback (
            feedbackID int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            custID int(11) DEFAULT NULL,
            issueDescription varchar(255) DEFAULT NULL,
            submissionTime datetime DEFAULT current_timestamp(),
            rating int(11) DEFAULT NULL,
            staffID int(11) DEFAULT NULL,
            FOREIGN KEY (custID) REFERENCES customer(custID),
            FOREIGN KEY (staffID) REFERENCES staff(staffID)
        )",
        "customer" => "CREATE TABLE IF NOT EXISTS customer (
            custID int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            custName varchar(255) DEFAULT NULL,
            custPhoneNo varchar(15) DEFAULT NULL,
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL
        )"
    ];

    // iterate over the array and create the tables
    foreach ($tables as $table => $sql) {
        if ($conn->query($sql) === TRUE) {
            // echo "Table $table created successfully<br>";
        } else {
            echo "Error creating table: " . $conn->error . "<br>";
        }
    }

    return $conn;
}


function executeQuery($conn, $query, $params)
{
    $stmt = $conn->prepare($query);
    if (count($params) > 0) {
        $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function loginUser()
{
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = trim($_POST['password']);

    $roles = ['customer', 'staff'];
    foreach ($roles as $role) {
        if (checkUser($email, $password, $role == 'customer' ? 'customer' : 'staff', $role)) {
            // If the login is successful, return the role of the user and the redirect URL
            $redirectUrl = $role == 'customer' ? 'index.html' : 'admin.html';
            header('Content-Type: application/json');
            echo json_encode(['role' => $role, 'redirectUrl' => $redirectUrl]);
            exit;
        }
    }

    // If the checkUser function fails for all roles, return an error message
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid email or password']);
    exit;
}

function checkUser($email, $password, $table, $role)
{
    $conn = createDbConnection();
    $result = executeQuery($conn, "SELECT * FROM $table WHERE email = ?", [$email]);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Use password_verify to check the password
        if (password_verify($password, $user['password'])) {
            // unencrypt the password
            $user['password'] = $password;

            $_SESSION['user'] = $user;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // open the appropriate page
            header("Location: " . ($role == 'customer' ? 'index.html' : 'admin.html'));
            exit;
        }
    }

    // Close the database connection
    $conn->close();

    return false;
}


function checkUserRole()
{
    // Check if the user is logged in and if the role is set
    if (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
        echo $_SESSION['role'];
    } else {
        echo 'not logged in';
    }
}

function registerUser()
{
    $conn = createDbConnection();
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];

    $tables = [
        'regular' => ['table' => 'customer', 'id' => 'custID'],
        'admin' => ['table' => 'staff', 'id' => 'staffID']
    ];
    $table = $tables[$role]['table'];
    $id = $tables[$role]['id'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email already registered";
        exit;
    }

    // Prepare and bind
    $password = $_POST['password'];
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO $table ($id, email, password) VALUES (NULL, ?, ?)");
    $stmt->bind_param("ss", $email, $hashedPassword);

    if ($stmt->execute() === FALSE) {
        echo "Error";
        exit;
    }

    $stmt->close();

    // Redirect to the login page
    echo 'login.html';
    exit;
}


function fetch_data($table)
{
    $conn = createDbConnection();
    $result = executeQuery($conn, "SELECT * FROM $table", []);

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

function fetch_customers()
{
    $conn = createDbConnection();
    $result = executeQuery($conn, 'SELECT custID, custName, custPhoneNo, email FROM customer', []);

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

function fetch_staffs()
{
    $conn = createDbConnection();
    $result = executeQuery($conn, 'SELECT staffID, staffName, staffPhoneNo, email, approved FROM staff', []);

    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

function food()
{
    $conn = createDbConnection();
    $result = executeQuery($conn, 'SELECT id, title, subtitle, img_src FROM Food', []);
    while ($row = $result->fetch_assoc()) {
        echo <<<HTML
        <div class="col-lg-4 col-sm-6 mb-4">
            <div class="portfolio-item">
                <a class="portfolio-link" data-bs-toggle="modal" href="#portfolioModal{$row["id"]}">
                    <div class="portfolio-hover">
                        <div class="portfolio-hover-content"><i class="fas fa-plus fa-3x"></i></div>
                    </div>
                    <img class="img-fluid img-menu" src="{$row["img_src"]}" alt="..." />
                </a>
                <div class="portfolio-caption">
                    <div class="portfolio-caption-heading">{$row["title"]}</div>
                    <div class="portfolio-caption-subheading text-muted">{$row["subtitle"]}</div>
                </div>
            </div>
        </div>
    HTML;
    }

    if ($result->num_rows == 0) {
        echo "0 results";
    }

    header('Content-Type: application/json');
}

$estimatedTime = date("Y-m-d H:i:s", strtotime('+30 minutes')); // Estimated time 30 minutes from now
function addToCart($custID, $foodID, $quantity)
{

    // check if the user is admin, if so just return
    if ($_SESSION['role'] === 'admin') {
        echo "Admin cannot add to cart";
        return;
    }

    // Create a database connection
    $conn = createDbConnection();

    // Check if the customer ID and food ID combination already exists in the cart
    $stmt = $conn->prepare("SELECT quantity FROM order_food WHERE custID = ? AND foodID = ? AND isPaid = 0");
    $stmt->bind_param("ii", $custID, $foodID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the existing quantity
        $row = $result->fetch_assoc();
        $existingQuantity = $row['quantity'];
        $newQuantity = $existingQuantity + $quantity;

        // Update the cart with the new quantity
        $updateStmt = $conn->prepare("UPDATE order_food SET quantity = ? WHERE custID = ? AND foodID = ?");
        $updateStmt->bind_param("iii", $newQuantity, $custID, $foodID);
        if ($updateStmt->execute()) {
            echo "Quantity updated successfully.";
        } else {
            echo "Error updating quantity: " . $updateStmt->error;
        }
    } else {
        // Insert a new row if the combination doesn't exist or if the order is already paid
        $insertStmt = $conn->prepare("INSERT INTO order_food (custID, foodID, quantity, isPaid) VALUES (?, ?, ?, 0)");
        $insertStmt->bind_param("iii", $custID, $foodID, $quantity);
        if ($insertStmt->execute()) {
            echo "Item added to cart successfully.";
        } else {
            echo "Error adding item to cart: " . $insertStmt->error;
        }
    }

    // Close the statements and connection
    $stmt->close();
    $updateStmt->close();
    $insertStmt->close();
    $conn->close();
}


function viewCart()
{
    $conn = createDbConnection();
    $custID = $_SESSION['user']['custID'] ?? 0;

    $defaultSortOrder = 'price';
    $sortOrder = $_POST['sortOrder'] ?? $defaultSortOrder;

    // Modify the SQL query to include the isPaid attribute and check if it's not paid
    $stmt = $conn->prepare('SELECT f.id, f.title, f.price, f.img_src, f.subtitle, of.quantity FROM order_food of JOIN food f ON of.foodID = f.id WHERE of.custID = ? AND of.isPaid = 0');
    $stmt->bind_param('i', $custID);

    $stmt->execute();
    $result = $stmt->get_result();

    $stmt->bind_param('i', $custID);

    $stmt->execute();
    $result = $stmt->get_result();

    $total = 0;
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        // price * quantity
        $total += $row['price'] * $row['quantity'];
    }

    $sortOrder = $_POST['sortOrder'] ?? 'price';

    usort($items, function ($a, $b) use ($sortOrder) {
        if ($sortOrder === 'price') {
            return $a['price'] * $a['quantity'] <=> $b['price'] * $b['quantity'];
        } else {
            return strcasecmp($a['title'], $b['title']);
        }
    });

    $count = count($items);

    echo <<<HTML
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
        <p class="mb-1">Shopping cart</p>
        <p class="mb-0">You have {$count} items in your cart</p>
        </div>
        <div>
        <p class="mb-0"><span class="text-muted">Sort by:</span> <a href="#!" onclick="sortBy()"class="text-body" id="priceAlphabet">{$sortOrder}</a></p>
        </div>
    </div>
    HTML;

    foreach ($items as $row) {
        $totalEach = $row['price'] * $row['quantity'];
        $totalEach = number_format($totalEach, 2);
        echo <<<HTML
        <div id="card-{$row['id']}" class="card mb-3">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex flex-row align-items-center">
                            <div>
                                <img src="{$row['img_src']}" class="img-fluid rounded-3" alt="Shopping item" style="width: 65px;">
                            </div>
                            <div class="ms-3">
                                <h5>{$row['title']}</h5>
                                <p class="small mb-0">{$row['subtitle']}</p>
                            </div>
                        </div>
                        <!-- this is quantity so take from sql -->
                        <div class="d-flex flex-row align-items-center">
                            <div style="width: 50px;">
                                <h5 class="fw-normal mb-0">{$row['quantity']}</h5>
                            </div>
                            <div style="width: 80px;">
                                <h5 class="mb-0">RM {$totalEach}</h5>
                            </div>
                            <div>
                                <button class="btn btn-danger btn-sm" onclick="deleteCartItem({$row['id']})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    $total = number_format($total, 2);

    echo <<<HTML
    <div class="d-flex justify-content-between">
        <p class="mb-0"><strong>Total:</strong></p>
        <p class="mb-0" id="totalPrice"><strong>RM {$total}</strong></p>
    </div>
    HTML;

    header('Content-Type: application/json');
}

function clear_cart()
{
    // Update the customer order in the order_food table
    $conn = createDbConnection();
    $custID = $_SESSION['user']['custID'] ?? 0;
    $stmt = $conn->prepare('UPDATE order_food SET isPaid = 1 WHERE custID = ?');
    $stmt->bind_param('i', $custID);

    if ($stmt->execute()) {
        echo "Payment successful. Cart cleared.";
    } else {
        echo "Error: " . $stmt->error;
    }
}


function total()
{
    $conn = createDbConnection();
    $custID = $_SESSION['user']['custID'] ?? 0;
    $stmt = $conn->prepare('SELECT f.price, of.quantity FROM order_food of JOIN food f ON of.foodID = f.id WHERE of.custID = ?');
    $stmt->bind_param('i', $custID);

    $stmt->execute();
    $result = $stmt->get_result();

    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $total += $row['price'] * $row['quantity'];
    }

    $total = number_format($total, 2);

    echo "RM " . $total;
    header('Content-Type: application/json');
}


function deleteCartItem($foodID)
{
    $conn = createDbConnection();
    $custID = $_SESSION['user']['custID'] ?? 0;
    $stmt = $conn->prepare('DELETE FROM order_food WHERE custID = ? AND foodID = ?');
    $stmt->bind_param('ii', $custID, $foodID);

    if ($stmt->execute()) {
        echo "Item removed from cart successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

function getCurrentTime()
{
    // Set the default timezone to match your laptop's timezone
    date_default_timezone_set('Asia/Kuala_Lumpur'); // Change this to your local timezone

    // Get the current date and time
    $current_time = date('H:i:s A');

    echo $current_time;
}
function getEstimatedTime()
{
    // Set the default timezone to match your laptop's timezone
    date_default_timezone_set('Asia/Kuala_Lumpur'); // Change this to your local timezone

    // Get the current date and time
    $current_time = date('H:i:s A');

    // Add 30 minutes to the current time
    $estimated_time = date('H:i:s A', strtotime(' +30 minutes'));

    echo $estimated_time;
}

// function portfolio()
// {
//     $conn = createDbConnection();
//     $result = executeQuery($conn, 'SELECT id, title, subtitle, img_src, extra_desc, price FROM Food', []);
//     while ($row = $result->fetch_assoc()) {
//         // get the customer ID from the session
//         $custID = $_SESSION['user']['custID'] ?? 0;
//         $foodID = $row['id'];
//         echo <<<HTML
//         <div class="portfolio-modal modal fade" id="portfolioModal{$row["id"]}" tabindex="-1" role="dialog" aria-hidden="true">
//             <div class="modal-dialog">
//                 <div class="modal-content">
//                     <div class="close-modal" data-bs-dismiss="modal"><img src="assets/img/close-icon.svg" alt="Close modal" /></div>
//                     <div class="container">
//                         <div class="row justify-content-center">
//                             <div class="col-lg-8">
//                                 <div class="modal-body">
//                                     <h2 class="text-uppercase">{$row["title"]}</h2>
//                                     <p class="item-intro text-muted">{$row["subtitle"]}</p>
//                                     <img class="img-fluid portfolio-img d-block mx-auto" src="{$row["img_src"]}" alt="..." />
//                                     <p>{$row["extra_desc"]}</p>
//                                     <ul class="list-inline">
//                                         <li><strong>Price:</strong> RM {$row["price"]}</li>
//                                     </ul>
//                                     <!-- quantity -->
//                                     <div class="d-flex justify-content-center">
//                                         <div class="input-group" style="width: 250px;">
//                                             <span class="input-group-text">Quantity</span>
//                                             <button class="btn btn-outline-primary" type="button" onclick="decrementQuantity({$foodID})">
//                                                 <i class="fas fa-minus"></i>
//                                             </button>
//                                             <input type="text" class="form-control text-center" id="quantity-{$foodID}" value="1" readonly>
//                                             <button class="btn btn-outline-primary" type="button" onclick="incrementQuantity({$foodID})">
//                                                 <i class="fas fa-plus"></i>
//                                             </button>
//                                         </div>
//                                     </div>
//                                     <div class="d-flex justify-content-center mt-3">
//                                         <button class="btn btn-primary btn-xl text-uppercase mr-3" data-bs-dismiss="modal" type="button" onclick="addToCart({$custID}, {$foodID}, document.getElementById('quantity-{$foodID}').value)">
//                                             <i class="fas fa-shopping-cart"></i> Add to Cart
//                                         </button>
//                                         <button class="btn btn-danger btn-xl text-uppercase" data-bs-dismiss="modal" type="button">
//                                             <i class="fas fa-xmark me-1"></i> Back to Menu
//                                         </button>
//                                     </div>
//                                 </div>
//                             </div>
//                         </div>
//                     </div>
//                 </div>
//             </div>
//         </div>
//         HTML;
//     }

//     if ($result->num_rows == 0) {
//         echo "0 results";
//     }

//     header('Content-Type: application/json');
// }

function portfolio()
{
    $conn = createDbConnection();
    $result = executeQuery($conn, 'SELECT * FROM Food', []);
    $foods = [];
    while ($row = $result->fetch_assoc()) {
        // get the customer ID from the session
        $row['custID'] = $_SESSION['user']['custID'] ?? 0;
        $foods[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($foods);
}


function send_feedback($data)
{
    $conn = createDbConnection();
    $stmt = $conn->prepare("INSERT INTO customer_feedback (custID, issueDescription, submissionTime, staffID, rating) VALUES (?, ?, ?, ?, ?)");

    $staffID = 0;

    $staffResult = executeQuery($conn, 'SELECT staffID FROM staff', []);
    $staffIDs = [];
    while ($row = $staffResult->fetch_assoc()) {
        $staffIDs[] = $row['staffID'];
    }

    $staffID = $staffIDs[array_rand($staffIDs)];

    // date and time is the current date and time
    $submissionTime = date('Y-m-d H:i:s');

    $custID = $_SESSION['user']['custID'] ?? 0;

    $rating = $data['rating'] ?? 0;

    $stmt->bind_param("issii", $custID, $data['message'], $submissionTime, $staffID, $rating);

    if ($stmt->execute() === TRUE) {
        echo "Feedback sent successfully";
    } else {
        echo "Error sending feedback: " . $conn->error;
    }

    $conn->close();
}

function fetch_feedback()
{
    $conn = createDbConnection();
    $staffID = $_SESSION['user']['staffID'] ?? 0;
    $stmt = $conn->prepare('SELECT cf.custID, c.email, c.custName, cf.issueDescription, cf.rating FROM customer_feedback cf JOIN customer c ON cf.custID = c.custID WHERE cf.staffID = ?');
    $stmt->bind_param('i', $staffID);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = array();
    while ($row = $result->fetch_assoc()) {
        if ($row['rating'] > 0) {
            $rating = '';
            for ($i = 0; $i < $row['rating']; $i++) {
                $rating .= '<i class="fas fa-star text-warning"></i>';
            }
            $row['rating'] = $rating;
        }
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}

// this function is to generate sales report from the database
// it will takes the total sales from the order_food table and show it in the admin page
// by multiplying the quantity with the price of the food and sum it up to get the total sales
function fetch_report()
{
    $conn = createDbConnection();
    $stmt = $conn->prepare('SELECT f.price, of.quantity FROM order_food of JOIN food f ON of.foodID = f.id');
    $stmt->execute();
    $result = $stmt->get_result();

    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $total += $row['price'] * $row['quantity'];
    }

    $total = number_format($total, 2);

    echo "RM " . $total;
    header('Content-Type: application/json');
}

function update_profile($data)
{
    $conn = createDbConnection();
    $role = $_SESSION['role'];
    $table = $role === 'customer' ? 'customer' : 'staff';
    $idColumn = $role === 'customer' ? 'custID' : 'staffID';
    $columns = $role === 'customer' ? ['custName', 'custPhoneNo', 'email', 'password'] : ['staffName', 'staffPhoneNo', 'email', 'password'];

    $data['updatedData'][$columns[0]] = $data['updatedData']['name'];
    $data['updatedData'][$columns[1]] = $data['updatedData']['phone'];

    $sql = "UPDATE $table SET ";
    $params = [];
    foreach ($columns as $column) {
        $sql .= "$column = ?, ";
        $params[] = $data['updatedData'][$column];
    }

    $sql = rtrim($sql, ', ');
    $sql .= " WHERE $idColumn = ?";

    $stmt = $conn->prepare($sql);
    $params[] = $_SESSION['user'][$idColumn];
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);

    if ($stmt->execute() === TRUE) {
        echo "Profile updated successfully";
    } else {
        echo "Error updating profile: " . $conn->error;
    }

    $conn->close();
}

function update_record($currentPage, $data)
{
    header('Content-Type: application/json');

    $conn = createDbConnection();

    switch ($currentPage) {
        case 'update_food':
            $table = 'Food';
            $columns = ['title', 'subtitle', 'img_src', 'extra_desc', 'price', 'quantity'];
            $idColumn = 'id';
            break;
        case 'update_staff':
            $table = 'staff';
            $columns = ['staffName', 'staffPhoneNo', 'email', 'approved'];
            $idColumn = 'staffID';
            break;
        case 'update_cust':
            $table = 'customer';
            $columns = ['custName', 'custPhoneNo', 'email'];
            $idColumn = 'custID';
            break;
        default:
            echo "Invalid page";
            exit;
    }

    $sql = "UPDATE $table SET ";
    $params = [];
    foreach ($columns as $column) {
        $sql .= "$column = ?, ";
        $params[] = $data[$column];
    }
    $sql = rtrim($sql, ', ');
    $sql .= " WHERE $idColumn = ?";

    $stmt = $conn->prepare($sql);
    $params[] = $data[$idColumn];
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);

    if ($stmt->execute() === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $conn->close();
}

function random_dark_color()
{
    $red = rand(0, 127);
    $green = rand(0, 127);
    $blue = rand(0, 127);

    // Convert to hexadecimal
    $red = dechex($red);
    $green = dechex($green);
    $blue = dechex($blue);

    // Ensure 2 digits for each color
    $red = str_pad($red, 2, "0", STR_PAD_LEFT);
    $green = str_pad($green, 2, "0", STR_PAD_LEFT);
    $blue = str_pad($blue, 2, "0", STR_PAD_LEFT);

    return '#' . $red . $green . $blue;
}


// sales report
function sales_report()
{
    $conn = createDbConnection();

    $reportType = $_POST['reportType'];

    // Query the database based on the report type
    switch ($reportType) {
        case 'monthly':
            $sql = "SELECT MONTH(orderDateTime) as month, SUM(order_food.quantity * food.price) as total 
                FROM order_food 
                INNER JOIN food ON order_food.foodId = food.id 
                WHERE order_food.isPaid = 1
                GROUP BY MONTH(orderDateTime)";
            break;
        case 'quarterly':
            $sql = "SELECT QUARTER(orderDateTime) as quarter, SUM(order_food.quantity * food.price) as total 
                FROM order_food 
                INNER JOIN food ON order_food.foodId = food.id 
                WHERE order_food.isPaid = 1
                GROUP BY QUARTER(orderDateTime)";
            break;
        case 'yearly':
            $sql = "SELECT YEAR(orderDateTime) as year, SUM(order_food.quantity * food.price) as total 
                FROM order_food 
                INNER JOIN food ON order_food.foodId = food.id 
                WHERE order_food.isPaid = 1
                GROUP BY YEAR(orderDateTime)";
            break;
    }



    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch the data and format it for the pie chart
        $data = [];
        $data['labels'] = [];
        $data['datasets'] = [];
        $data['datasets'][0] = [];
        $data['datasets'][0]['data'] = [];
        $data['datasets'][0]['backgroundColor'] = [];
        while ($row = $result->fetch_assoc()) {
            $label = '';
            switch ($reportType) {
                case 'monthly':
                    $monthNames = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    $label = $monthNames[intval($row['month'])];
                    break;
                case 'quarterly':
                    $quarterNames = [1 => 'Q1', 'Q2', 'Q3', 'Q4'];
                    $label = $quarterNames[intval($row['quarter'])];
                    break;
                case 'yearly':
                    $label = strval($row['year']);
                    break;
            }
            array_push($data['labels'], $label);
            array_push($data['datasets'][0]['data'], intval($row['total']));
            array_push($data['datasets'][0]['backgroundColor'], random_dark_color());
        }

        // Return the data as JSON
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No data found for the selected period.']);
    }

    $conn->close();
}

function delete_customer($data)
{
    $conn = createDbConnection();
    // delete the info from other table related to the custID
    $stmt = $conn->prepare("DELETE FROM order_food WHERE custID = ?");
    $stmt->bind_param("i", $data['custID']);

    if ($stmt->execute() === TRUE) {
        echo "Order deleted successfully";
    } else {
        echo "Error deleting order: " . $conn->error;
    }

    $stmt = $conn->prepare("DELETE FROM customer_feedback WHERE custID = ?");
    $stmt->bind_param("i", $data['custID']);

    if ($stmt->execute() === TRUE) {
        echo "Feedback deleted successfully";
    } else {
        echo "Error deleting feedback: " . $conn->error;
    }

    // delete the customer
    $stmt = $conn->prepare("DELETE FROM customer WHERE custID = ?");
    $stmt->bind_param("i", $data['custID']);

    if ($stmt->execute() === TRUE) {
        echo "Customer deleted successfully";
    } else {
        echo "Error deleting customer: " . $conn->error;
    }

    $conn->close();
}

function delete_staff($data)
{
    $conn = createDbConnection();


    // find the staffID in the customer_feedback table
    $stmt = $conn->prepare("SELECT * FROM customer_feedback WHERE staffID = ?");
    $stmt->bind_param("i", $data['staffID']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // assign to random staff so that the feedback will not be deleted
        $staffResult = executeQuery($conn, 'SELECT staffID FROM staff', []);
        $staffIDs = [];
        while ($row = $staffResult->fetch_assoc()) {
            $staffIDs[] = $row['staffID'];
        }

        $staffID = $staffIDs[array_rand($staffIDs)];

        $stmt = $conn->prepare("UPDATE customer_feedback SET staffID = ? WHERE staffID = ?");
        $stmt->bind_param("ii", $staffID, $data['staffID']);

        if ($stmt->execute() === TRUE) {
            echo "Feedback reassigned successfully";
        } else {
            echo "Error reassigning feedback: " . $conn->error;
        }

        $stmt->close();
    }

    // delete the staff
    $stmt = $conn->prepare("DELETE FROM staff WHERE staffID = ?");
    $stmt->bind_param("i", $data['staffID']);

    if ($stmt->execute() === TRUE) {
        echo "Staff deleted successfully";
    } else {
        echo "Error deleting staff: " . $conn->error;
    }

    $conn->close();
}

function delete_food($data)
{
    $conn = createDbConnection();

    // ensure that the food is not in the cart
    // if it is in the cart, remove it from the order_food table
    $stmt = $conn->prepare("DELETE FROM order_food WHERE foodID = ?");
    $stmt->bind_param("i", $data['foodID']);

    if ($stmt->execute() === TRUE) {
        echo "Food removed from cart successfully";
    } else {
        echo "Error removing food from cart: " . $conn->error;
    }


    // delete the food
    $stmt = $conn->prepare("DELETE FROM food WHERE id = ?");
    $stmt->bind_param("i", $data['foodID']);

    if ($stmt->execute() === TRUE) {
        echo "Food deleted successfully";
    } else {
        echo "Error deleting food: " . $conn->error;
    }

    $conn->close();
}

function find_customer($data)
{
    // find based on email
    $conn = createDbConnection();
    $stmt = $conn->prepare("SELECT * FROM customer WHERE email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Customer not found']);
    }
}

// Define a variable to determine which code should run
$currentPage = $_POST["action"] ?? '';

// Use conditional logic to include the required page

switch ($currentPage) {
    case 'login':
        loginUser();
        break;
    case 'register':
        registerUser();
        break;
    case 'fetch_customers':
        fetch_customers();
        break;
    case 'fetch_staff':
        fetch_staffs();
        break;
    case 'fetch_food':
        fetch_data("food");
        break;
    case 'fetch_feedback':
        fetch_feedback();
        break;
    case 'food':
        food();
        break;
    case 'portfolio':
        portfolio();
        break;
    case 'check_role':
        checkUserRole();
        break;
    case 'add_to_cart':
        addToCart($_POST['custID'], $_POST['foodID'], $_POST['quantity']);
        break;
    case 'view_cart':
        viewCart();
        break;
    case 'delete_cart_item':
        deleteCartItem($_POST['foodID']);
        break;
        //Hanafi
    case 'time':
        getCurrentTime();
        break;
    case 'estimatedTime':
        getEstimatedTime();
        break;
    case 'total':
        total();
        break;
    case 'clear_cart':
        clear_cart();
        break;
    case 'send_feedback':
        send_feedback($_POST);
        break;
    case 'update_profile':
        update_profile($_POST);
        break;
    case 'sales_report':
        sales_report();
        break;
    case 'find_customer':
        find_customer($_POST);
        break;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'update_food' || $action === 'update_staff' || $action === 'update_cust') {
    // Check if the data is an array before updating
    if (is_array($data)) {
        update_record($action, $data);
    } else {
        echo "Error: Invalid data";
        exit;
    }
}

if ($action === 'delete_customer') {
    delete_customer($data);
}

if ($action === 'delete_staff') {
    delete_staff($data);
}

if ($action === 'delete_food') {
    delete_food($data);
}

ob_end_flush();

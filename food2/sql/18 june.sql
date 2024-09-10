-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2024 at 01:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `custID` int(11) NOT NULL,
  `custName` varchar(255) DEFAULT NULL,
  `custPhoneNo` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`custID`, `custName`, `custPhoneNo`, `email`, `password`) VALUES
(13, 'ammar', '0175071047', 'ammarhariz08@gmail.com', '1234'),
(14, 'hazeeq haikal', '01111495803', '2022676488@student.uitm.edu.my', '1234'),
(15, NULL, NULL, 'chekhairul253@gmail.com', 'khairul');

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `feedbackID` int(11) NOT NULL,
  `custID` int(11) DEFAULT NULL,
  `issueDescription` varchar(255) DEFAULT NULL,
  `submissionTime` datetime DEFAULT current_timestamp(),
  `staffID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_feedback`
--

INSERT INTO `customer_feedback` (`feedbackID`, `custID`, `issueDescription`, `submissionTime`, `staffID`) VALUES
(5, 14, 'the website sucks', '2024-05-25 14:27:52', 1);

-- --------------------------------------------------------

--
-- Table structure for table `food`
--

CREATE TABLE `food` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `img_src` varchar(255) DEFAULT NULL,
  `extra_desc` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food`
--

INSERT INTO `food` (`id`, `title`, `subtitle`, `img_src`, `extra_desc`, `price`) VALUES
(1, 'Nasi Lemak', 'Malaysian Food', 'assets/img/makanan/nasi lemak.jpg', 'Nasi lemak consists of rice cooked in coconut milk, served with spicy.', 10.00),
(2, 'Burger', 'Western food', 'assets/img/makanan/burger.jpg', 'Burger consist of juicy thick meat with salad, tomato and cheese.', 16.90),
(3, 'Chicken Chop', 'Western food', 'assets/img/makanan/chicken chop.jpg', 'Chicken chop contain of chicken with sauce, coleslaw and some fries.', 18.00),
(4, 'Pizza', 'Italian food', 'assets/img/makanan/pizza2.jpg', 'Pizza is served with crusty and chessy bread with some of beef pepperoni and mushroom.', 30.00),
(5, 'Ramen', 'Japanese food', 'assets/img/makanan/ramen.jpg', 'Ramen is served with a few pieces of fried chicken, poached egg, mushrooms and a pinch of vegetables.', 12.90),
(6, 'Spaghetti', 'Western food', 'assets/img/makanan/spaghetti.jpg', 'Spaghetti is served with tomato sauce and meatball.', 25.90);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderId` int(11) NOT NULL,
  `orderDate` date NOT NULL,
  `orderTime` time NOT NULL,
  `custID` int(11) DEFAULT NULL,
  `staffID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_food`
--

CREATE TABLE `order_food` (
  `id` int(11) NOT NULL,
  `foodID` int(11) DEFAULT NULL,
  `custID` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `orderDateTime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffID` int(11) NOT NULL,
  `staffName` varchar(255) DEFAULT NULL,
  `staffPhoneNo` varchar(15) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `approved` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffID`, `staffName`, `staffPhoneNo`, `email`, `password`, `approved`) VALUES
(1, 'MUHAMMAD HAZEEQ HAIKAL BIN ROSLAN', '+601111495803', 'haikalroslan740@gmail.com', 'hazeeq', 1),
(2, 'ammar', '0175071047', 'ammarhariz08@gmail.com', 'ammar', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`custID`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`feedbackID`),
  ADD KEY `custID` (`custID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderId`),
  ADD KEY `custID` (`custID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `order_food`
--
ALTER TABLE `order_food`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`foodID`),
  ADD KEY `custID` (`custID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `custID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedbackID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orderId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_food`
--
ALTER TABLE `order_food`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`),
  ADD CONSTRAINT `customer_feedback_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`);

--
-- Constraints for table `order_food`
--
ALTER TABLE `order_food`
  ADD CONSTRAINT `order_food_ibfk_1` FOREIGN KEY (`foodID`) REFERENCES `food` (`id`),
  ADD CONSTRAINT `order_food_ibfk_2` FOREIGN KEY (`custID`) REFERENCES `customer` (`custID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

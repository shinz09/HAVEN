-- 1. Create the Accounts Table (Handles Users, Admins, and Hotels)
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','hotel') NOT NULL DEFAULT 'user',
  `name` varchar(100) NOT NULL
);

-- 2. Create the Hotels Table
CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `manager_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `michelin_rating` int(1) DEFAULT 0,
  `status` enum('pending','approved','denied') DEFAULT 'approved',
  FOREIGN KEY (`manager_id`) REFERENCES `accounts`(`id`)
);

-- 3. Create the Rooms Table (For the Dynamic Map)
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `hotel_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `status` enum('vacant','occupied') NOT NULL DEFAULT 'vacant',
  `image_url` varchar(255) NOT NULL,
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`)
);

-- 4. Create the Bookings Table (The Checkout Receipt)
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `account_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_date` timestamp DEFAULT CURRENT_TIMESTAMP
);

-- Insert Dummy Accounts so you can test logins later
INSERT INTO `accounts` (`email`, `password`, `role`, `name`) VALUES
('admin@haven.com', 'password123', 'admin', 'Haven God Mode'),
('manager@peninsula.com', 'password123', 'hotel', 'Peninsula Manager'),
('jacob@user.com', 'password123', 'user', 'Jacob Arroyo');

-- Insert a Dummy Hotel for your search engine to find
INSERT INTO `hotels` (`manager_id`, `name`, `location`, `description`, `base_price`, `image_url`, `michelin_rating`) VALUES
(2, 'The Peninsula Manila', 'Manila', 'A luxurious 5-star experience in the heart of Makati.', 250.00, 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800', 5);
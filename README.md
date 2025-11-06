# Patient Management System (Core PHP)

A simple, role-based Patient Management System designed to run on a local XAMPP server. This application provides a web portal for a small clinic to manage patient records, prescriptions, and appointments.

The system is built from scratch using core PHP, session-based authentication, and a MySQL database.


## 📜 Table of Contents

  * About The Project
  * Key Features
  * Built With
  * Getting Started
      * Prerequisites
      * Installation
  * Usage
  * Database Schema
  * Contributing
  * License

-----

## 📖 About The Project

This project is a lightweight, server-side Patient Management System (PMS). It's designed to be a practical, real-world application for a small clinic or as a starting point for a more complex hospital management system. It demonstrates the fundamentals of building a secure, multi-user web application using only core PHP and a MySQL database.

-----

## ✨ Key Features

  * **Role-Based Access Control (RBAC):** The system has four distinct user roles, each with specific permissions.
      * **Admin:** Has full system privileges. Can create, read, update, and delete all user accounts (Doctors, Patients, Pharmacists).
      * **Doctor:** Can view their assigned patients, create new prescriptions (including diagnosis, visit dates, and medications), and manage appointments.
      * **Patient:** Can log in to view their own personal medical history, including past prescriptions and upcoming appointments.
      * **Pharmacist:** Can view a list of all pending prescriptions that need to be filled.
  * **Patient Management:** Admins and Doctors can create and manage patient profiles.
  * **Prescription & Visit Module:** Doctors can create detailed visit records, including vital signs (BP, weight), diagnosis, and a list of prescribed medications.
  * **Appointment Booking:** (Functionality to be expanded) Patients can book appointments, and Doctors can manage their schedules.

-----

## 💻 Built With

This project uses the following technologies:

  * **Backend:** Core PHP (no frameworks)
  * **Database:** MySQL / MariaDB
  * **Web Server:** Apache (via XAMPP)
  * **Frontend:** HTML5, CSS3, JavaScript
  * **Authentication:** PHP Sessions
  * **Admin-LTE Template**

-----

## 🚀 Getting Started

Follow these steps to get a local copy up and running.

### Prerequisites

You must have **XAMPP** (or any other AMP stack like WAMP or MAMP) installed on your computer.

  * [Download XAMPP](https://www.apachefriends.org/index.html)

### Installation

1.  **Clone the repo** (or download and unzip the project) into your XAMPP `htdocs` directory:

    ```bash
    git clone https://github.com/wci-master/pms.git C:\xampp\htdocs\pms
    ```

    *(Replace `your-username` and `pms_db` as needed)*

2.  **Start XAMPP:** Open your XAMPP Control Panel and start the **Apache** and **MySQL** services.

3.  **Import the Database:**

      * Open your browser and go to `http://localhost/phpmyadmin`.
      * Create a new database named `pms_db`.
      * Click on the new `pms_db` database and go to the **Import** tab.
      * Upload and import the `database.sql` file (or the file you used in our previous steps) that contains your table structure.

4.  **Configure Connection:**

      * Open the file `/config/connection.php` in your code editor.
      * Make sure the database credentials match your XAMPP setup (the default is usually correct):
        ```php
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', ''); // Default XAMPP password is empty
        define('DB_NAME', 'pms_db');
        ```

5.  **Run the Application:**

      * You're all set\! Open your browser and navigate to:
        **`http://localhost/patient_system`**

-----

## 🔑 Usage

Once the system is running, you can log in with the following default credentials. The password for all sample users is `123`.

**Admin** 
Username: Admin
Password: admin123

Just login as admin and create other users.

-----

## 🗃️ Database Schema

The database is designed to be simple and relational. The key tables are:

  * **`users`**: Stores login information, email, hashed password, and the user's `role`.
  * **`patients`**: Stores patient-specific demographic data. Linked to a `user_id`.
  * **`doctors`**: Stores doctor-specific data, like `specialty`. Linked to a `user_id`.
  * **`patient_visits`**: This is the core table. It records every patient visit, linking a `patient_id`, `doctor_id`, and storing vitals (`bp`, `weight`), `visit_date`, `next_visit_date`, and the main `diagnosis`.
  * **`patient_medication_history`**: Stores each individual medication prescribed during a visit. It's linked to `patient_visits` by `patient_visit_id`.
  * **`medicines` / `medicine_details`**: Tables that act as a central library of available medicines.

-----

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request.

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

-----

## 📄 License

Distributed under the MIT License. See `LICENSE.md` for more information.

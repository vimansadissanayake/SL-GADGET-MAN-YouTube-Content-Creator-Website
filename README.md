SL Gadget Man Website-

A dynamic, database-driven web application designed to showcase tech review videos, featuring a secure admin dashboard and an automated contact management system.

🚀 Key Features

Automated Video Management: Add YouTube links through the admin panel; the system automatically extracts video IDs, generates high-quality thumbnails, and normalizes URLs.
Secure Admin Dashboard: Protects content management with a session-based login system.
Live Messaging System: Integrated contact form that saves inquiries to the database and provides one-click reply options via Gmail for admins.
Maintenance Utilities: Includes specialized scripts to fix broken thumbnails and clean up duplicate database entries.
Dynamic UI: Real-time view count formatting (e.g., 1.5K, 2.3M) and responsive grid layout.

🛠️ Technical Stack

Backend: PHP

Database: MySQL(managed via phpMyAdmin)

Frontend: HTML5, CSS3

📦 Database Setup (via phpMyAdmin)

Since this project relies on a MySQL database, follow these steps to set it up:

1. Open phpMyAdmin on your local (XAMPP)server.
2. Create a New Database:
Name it `slgadgetman_db`.


3. Import Schema:
Select your new database and click the Import tab.
Choose the provided `slgadgetman_db.sql` file to automatically create the `videos` and `contact_messages` tables.


4. Configuration:
5. If your database username or password differs from the defaults (`root` with no password), update the `$db_user` and `$db_pass` variables in `admin_msg.php`, `index.php`, and `setup.php`.



📂 Project Structure

 `index.php`: The public-facing landing page with video galleries and contact forms.
 `admin_msg.php`: The main administrative hub for managing videos and reading messages.
 `contact.php`: Processes form data and handles both database storage and optional email notifications.
 `get_videos.php`: An API endpoint that returns video data in JSON format.
 `cleanup_duplicates.php` & `fix_thumbs.php`: Critical maintenance tools for database health.

📧 Support
For inquiries or reporting bugs, please reach out to charuniedu@gmail.com

Check out my SLGM UI/UX Design using Figma Tool  : https://www.figma.com/design/WE2knw7Cjtc72cPI2kSDlC/SLGM-UI-UX-DESIGN?node-id=0-1&t=aTbfBONEaVYofoTH-1

### **Refactoring Plan: Enhanced Authentication and UI Separation**

This document outlines the plan to refactor the user authentication system by introducing a dedicated signup and login page, updating the database schema, and refining the backend logic to support a more robust and user-friendly experience.

---

### **Phase 1: Database & Backend Modifications**

**Step 1: Update Database Schema (`setup_database.php`)**
*   The `users` table in `setup_database.php` will be modified to include `firstname`, `lastname`, and a unique `email` field.
*   The `username` column will be retained to serve as a unique internal identifier, which will be generated automatically during signup to prevent conflicts if multiple users share the same name. This username will not be visible in the UI.
*   The final schema will support full user profiles and the existing OTP-based authentication flow.

**New `users` table schema:**
```sql
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    login_token VARCHAR(255) NULL,
    login_token_expires_at DATETIME NULL,
    is_connected BOOLEAN NOT NULL DEFAULT 0,
    last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Step 2: Refactor Backend Services (`AuthService.php` & `Chat.php`)**
*   **`AuthService.php`:**
    *   The `createUser` method will be updated to accept `firstname`, `lastname`, and `email`. It will also generate a unique `username` (e.g., `firstname.lastname.123`).
    *   The service will now handle two distinct actions: creating a new user during signup and generating an OTP for an existing user during login.
*   **`Chat.php`:**
    *   The `onMessage` router will be updated to handle three distinct message types from the client:
        1.  `signup`: Expects `firstname`, `lastname`, and `email`. Triggers user creation and sends an OTP.
        2.  `request_login`: Expects only `email`. Triggers an OTP for an existing user.
        3.  `verify_login`: Expects `email` and `otp`. Verifies the code and issues a JWT.

---

### **Phase 2: Frontend Implementation**

**Step 3: Create Dedicated Authentication Page (`auth.html`)**
*   A new `auth.html` file will be created to handle all user authentication, completely separating it from the main chat interface.
*   **UI/UX Requirements:**
    *   All forms (signup, login, OTP) will be centered horizontally and vertically on the page for a clean, focused user experience.
    *   The UI will be styled using **Tailwind CSS** to perfectly match the modern and professional aesthetic of `index.html`, ensuring visual consistency.
    *   When the server confirms an OTP has been sent, the UI will smoothly transition from the login/signup form to the OTP verification form using a fade-and-slide animation.
*   **Form Structure:**
    *   The page will feature a tab or link to easily switch between the **Signup Form** (First Name, Last Name, Email) and the **Login Form** (Email).
    *   The **OTP Verification Form** will appear after the initial step is completed.

**Step 4: Implement Frontend Logic in `auth.html`**
*   The JavaScript within `auth.html` will manage the entire authentication flow:
    *   **State Management:** The script will control the visibility of the signup, login, and OTP forms.
    *   **WebSocket Communication:** It will send `signup` or `request_login` messages to the server based on user actions.
    *   **OTP Animation:** Upon receiving a `login_request_sent` confirmation from the server, the script will trigger the transition animation and display the OTP form.
    *   **Verification & Redirection:** After a successful OTP verification, the server will send back a `login_success` message with a JWT. The script will save this token to `localStorage` and automatically redirect the user to `index.html`.

**Step 5: Secure the Main Chat Page (`index.html`)**
*   A security check will be added to the JavaScript in `index.html`. If a JWT is not found in `localStorage`, the user will be immediately redirected to `auth.html` to log in.
*   All legacy login and OTP UI elements and associated JavaScript will be removed from `index.html`.
*   The `sendMessage` function will be updated to include the JWT with every outgoing message, allowing the backend to perform stateless authentication for every user action.

---

### **Required Libraries & Resources**

*   **PHP Libraries (via Composer):**
    *   `firebase/php-jwt`: For creating and validating JSON Web Tokens.
    *   `ramsey/uuid`: For generating secure, random OTPs.
    *   `phpmailer/phpmailer`: For sending OTPs via email.
    *   `vlucas/phpdotenv`: For managing environment variables.
*   **Frontend Styling:**
    *   **Tailwind CSS:** A utility-first CSS framework for rapid UI development.
        *   **Link:** `https://cdn.tailwindcss.com`

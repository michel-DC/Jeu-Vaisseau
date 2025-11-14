# Project Overview

This project is a PHP-based web application for a space-themed game called "Nova Protocol". It features a multiplayer system where players can create or join game sessions. The game involves managing a spaceship, its crew, and engaging in combat. The frontend uses HTML, CSS, and JavaScript, while the backend is powered by PHP and interacts with a MySQL database.

## Key Technologies

*   **Backend:** PHP
*   **Frontend:** HTML, CSS, JavaScript
*   **Database:** MySQL (interacted with via PHP's `mysqli` extension)

## Architecture

The application follows a client-server architecture:
*   **Client-side:** HTML pages (`index.php`, `choix-joueur.php`, `game.php`) render the user interface. JavaScript files (`scripts/*.js`) handle client-side logic, user interactions, and AJAX calls to the API. CSS files (`styles/*.css`) provide styling.
*   **Server-side:** PHP scripts handle requests, manage game state, and interact with the MySQL database.
    *   `index.php`: The landing page for the game.
    *   `choix-joueur.php`: Allows players to create or join a game.
    *   `game.php`: The main game interface, displaying game state and player information.
    *   `api/`: Contains PHP scripts for handling API requests related to game creation, joining, status, and quitting.
    *   `class/`: Contains PHP classes defining game entities like `Vaisseau` (spaceship), `Personne` (base class for crew members), and `Drone`.
    *   `database/db.php`: Manages the database connection.

## Building and Running

This project does not appear to have explicit build scripts or commands. It is a PHP application, so it would typically be run on a web server with PHP and MySQL installed.

**To run this project:**

1.  **Set up a web server:** Ensure you have a web server (e.g., Apache, Nginx) with PHP (version 7.4 or higher is recommended) and MySQL installed.
2.  **Database Configuration:**
    *   Create a MySQL database.
    *   Update the `DEFINE` statements in `database/db.php` with your database credentials (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`).
    *   You will need to create the necessary tables (`parties`, `game_state`) in your MySQL database. Based on the PHP scripts, the schema would look something like this (this is an inference and might need adjustments):

        ```sql
        CREATE TABLE parties (
            partie_id VARCHAR(255) PRIMARY KEY,
            joueur1_id VARCHAR(255),
            joueur2_id VARCHAR(255),
            statut VARCHAR(50) DEFAULT 'en_attente'
        );

        CREATE TABLE game_state (
            partie_id VARCHAR(255) PRIMARY KEY,
            joueur1_hp INT DEFAULT 1000,
            joueur2_hp INT DEFAULT 1000,
            duree_partie INT DEFAULT 0,
            FOREIGN KEY (partie_id) REFERENCES parties(partie_id)
        );
        ```
3.  **Place project files:** Place all project files in your web server's document root (e.g., `htdocs` for Apache, `www` for Nginx).
4.  **Access in browser:** Open your web browser and navigate to the URL where your web server is serving the project (e.g., `http://localhost/Etape-2/`).

## Development Conventions

*   **Language:** Primarily PHP for backend logic and JavaScript for frontend interactivity.
*   **Styling:** CSS is used for styling, with separate files for different sections (`style.css`, `choix-joueur.css`, `game.css`, `game-state.css`).
*   **Database Interaction:** Direct MySQL interaction using `mysqli_prepare` and `mysqli_stmt_bind_param` for parameterized queries, which is a good practice for preventing SQL injection.
*   **Session Management:** PHP sessions are used to manage game state across requests (`session_start()`).
*   **Error Handling:** Basic error handling is present in API endpoints, returning JSON responses with error messages and appropriate HTTP status codes.
*   **File Organization:**
    *   `api/`: API endpoints.
    *   `assets/`: Static assets like audio, fonts, images, and videos.
    *   `class/`: PHP classes.
    *   `database/`: Database connection script.
    *   `scripts/`: JavaScript files.
    *   `styles/`: CSS files.

## Further Exploration (TODO)

*   **Game Logic:** The core game logic (movement, combat, drone actions) is defined in PHP classes (`Vaisseau`, `Personne`, `Drone`) but the client-side interaction for these actions is not fully detailed in the reviewed files. Further investigation into `scripts/game-state.js` and other related JavaScript files would be beneficial.
*   **API Endpoints:** Explore other API endpoints in the `api/` directory (e.g., `rejoindre-partie.php`, `quitter-partie.php`) to understand the full range of server-side interactions.
*   **Game Flow:** A more detailed understanding of the complete game flow, from player selection to in-game actions and victory conditions, would require examining all relevant PHP and JavaScript files.

# ♟️ FOT Knights Arena — Chess Tournament Platform

Official digital tournament management platform for the **Faculty of Technology, University of Ruhuna** Chess Tournament.

Built with high-performance vanilla PHP, MySQL, and modern CSS/JavaScript following a clean **Service-Oriented / MVC Architecture**.

---

## 📋 Table of Contents
- [Features](#-features)
- [Tournament Rules & Scoring System](#-tournament-rules--scoring-system)
- [Architecture & Directory Structure](#-architecture--directory-structure)
- [Prerequisites & Requirements](#-prerequisites--requirements)
- [Installation & Local Setup](#-installation--local-setup)
- [Default Credentials](#-default-credentials)
- [Portals & User Flows](#-portals--user-flows)
- [Database Schema](#-database-schema)

---

## ✨ Features

### 1. 🌐 Public Landing Page (`index.php`)
- **Interactive Hero & Board**: Embedded Sicilian Defense chess diagram and interactive floating chess pieces with cursor/touch parallax.
- **Dynamic Countdown Ticker**: Timezone-aware (Asia/Colombo UTC+05:30) countdown synced to the upcoming round's deadline or tournament start.
- **Dynamic Round Schedule**: Editable round schedule pulled live from the database with active round status badges.
- **Tournament Arbiters & Officials Panel**: Public showcase of appointed tournament arbiters (Chief Arbiter, Deputy Arbiters, Sector Arbiters), displaying roles, FIDE titles/IDs, and official contact channels.
- **Live Team Standings Scoreboard**: Podium badges, Match Points (MP), Game Points (GP), matches played, wins, draws, losses, and arbiter notes.
- **Individual Player Standings**: Real-time tournament-wide individual player leaderboard with 10-per-page client-side pagination.
- **Pairings & Results Display**: Public view of scheduled matchups and finalized board scores.
- **Online Team Registration**:
  - Live eligibility compliance bar checking constraints in real-time.
  - Dynamic member roster inputs (4 to 6 players).
  - Instant validation: minimum 1 female player, at least 2 distinct academic batches, registration numbers format `TG/YYYY/NNNN`.
  - Receipt card with a printable confirmation slip.

### 2. 🛡️ Captain Portal (`captain.php`)
- **Secure Authentication**: Log in using team name and password set during registration.
- **Roster Dashboard**: View registered squad members, board orders, and captain badge.
- **Drop Boards Management**:
  - Teams with 5 or 6 members select reserve players to drop for each round (enforcing exactly 4 active boards).
  - Deadline countdown lock: once the round deadline passes, selections lock automatically.
  - Automated fallback: if a captain fails to submit drops before the deadline, the system automatically drops reserve boards.

### 3. ⚙️ Admin Control Panel (`admin.php`)
- **Team Verification & Approval**: Pending teams remain hidden from the public standings until reviewed and approved by the organizer.
- **Roster Management**: Edit player details, reorder board positions, or remove teams.
- **Instant Toggles**: One-click AJAX switches to show/hide the public scoreboard or countdown ticker without page reloads.
- **Manual Standings Editor**: Override or fine-tune Match Points, Game Points, and arbiter notes.
- **Round Manager**: Create rounds, configure drop deadlines, and update public schedule dates and times.
- **Admin Drop Override**: Arbiters can set or override drop boards for any team, even after round deadlines.
- **Pairings & Matches Manager**:
  - **6x6 Cross-Table Matrix**: Visual matrix showing historical matches and round numbers to prevent duplicate matchups.
  - **Manual Pairings**: Schedule matchups (Team A vs Team B) per round with validation preventing self-matches or duplicate round assignments.
  - **Board-by-Board Result Entry**: Enter individual board scores (`1-0`, `0.5-0.5`, `0-1`) automatically mapped to active playing players.
  - **Automated Recalculation**: Finalizing a match calculates team Game Points & Match Points and updates the tournament standings immediately.
- **Printable Lineups**: Print official 4-board active match rosters.
- **Arbiters & Officials Management**: Register, update, reorder, and remove multi-arbiter panels (Chief Arbiter, Deputy Chief Arbiter, Sector Arbiters, FIDE titles, and contacts).
- **Excel/CSV Export**: One-click download of all teams and player rosters.
- **Admin Password Management**: Dedicated interface to safely change administrator credentials with current password verification.

---

## ♟️ Tournament Rules & Scoring System

- **Format**: 6-team Round-Robin tournament across 5 rounds.
- **Match Composition**: Each match consists of exactly 4 boards.
- **Squad Size**: 4 to 6 players per team.
  - Minimum 1 female player (girl) required.
  - Minimum 2 distinct academic batches required.
- **Custom Match Point (MP) System**:
  - **Match Win**: 2 Match Points
  - **Match Draw**: 1 Match Point
  - **Match Loss (> 0 GP)**: 0 Match Points
  - **Match Loss (0 GP - all 4 boards lost)**: **-1 Match Point** *(Penalty rule)*
- **Game Points (GP)**: Total individual board points (Win = 1.0, Draw = 0.5, Loss = 0.0).
- **Tiebreak Priority**:
  1. Match Points (MP)
  2. Game Points (GP)
  3. Direct Encounter
  4. Sonneborn-Berger (SB)

---

## 🏛️ Architecture & Directory Structure

The application is structured into a lightweight, modular **Service-Oriented / MVC pattern** with zero external library overhead:

```
Chess/
├── config/
│   ├── database.php             # Database credentials & PDO options
│   └── tournament.php           # Tournament constants & scoring rules
│
├── src/
│   ├── Database.php             # High-performance PDO connection singleton
│   ├── Migrations.php           # Database schema initialization & migrations
│   ├── Helpers/
│   │   ├── Flash.php            # Flash messages helper (success/error alerts)
│   │   └── View.php             # Template rendering helper with extracted scope
│   └── Services/
│       ├── AuthService.php      # Admin & Captain authentication and session handling
│       ├── TeamService.php      # Team registration, validation, and roster CRUD
│       ├── TournamentService.php# Scoreboard settings, standings calculation, player rankings
│       ├── MatchService.php     # Pairings validation, 6x6 matrix, board results & scoring
│       └── DropBoardService.php # 4-board resolution, deadline checking & round CRUD
│
├── views/
│   ├── layouts/
│   │   ├── header.php           # Shared responsive header, navigation & floating pieces
│   │   └── footer.php           # Shared responsive footer & particles animations
│   ├── home/                    # Public landing page sections (hero, schedule, standings, etc.)
│   ├── captain/                 # Captain portal views (login, dashboard, drop boards)
│   └── admin/                   # Admin portal views (teams, scoreboard, rounds, pairings, etc.)
│
├── index.php                    # Public front controller
├── admin.php                    # Admin panel front controller
├── captain.php                  # Captain portal front controller
├── api_register.php             # REST/JSON team registration endpoint
├── db.php                       # Backward compatibility connection bridge
├── style.css                    # Unified responsive stylesheet
└── schema.sql                   # Canonical MySQL schema
```

---

## 💻 Prerequisites & Requirements

- **Web Server**: Apache (XAMPP, WampServer, MAMP, or native Apache)
- **PHP**: PHP 8.0 or newer
- **MySQL / MariaDB**: 5.7+ or MariaDB 10.2+
- **Browser**: Any modern web browser (Chrome, Firefox, Safari, Edge)

---

## 🚀 Installation & Local Setup

### 1. Place Project in Web Root
If using **XAMPP**, clone or copy the project into your `htdocs` directory:
```bash
/Applications/XAMPP/xamppfiles/htdocs/knights/fot_arena/Chess
# or on Windows:
C:\xampp\htdocs\knights\fot_arena\Chess
```

### 2. Configure Database Credentials
Edit `config/database.php` if your MySQL username or password differs from default:
```php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'chess_tournament',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];
```

### 3. Initialize the Database
You can initialize the database using either method:
- **Automatic**: Simply open the website in your browser. `Database.php` and `Migrations.php` will automatically create the database and all required tables if they don't already exist.
- **Manual (phpMyAdmin / MySQL CLI)**: Import `schema.sql`:
  ```bash
  mysql -u root -p < schema.sql
  ```

### 4. Run the Application
Start the Apache and MySQL services in the XAMPP Control Panel, then navigate to:
```
http://localhost/knights/fot_arena/Chess/
```

---

## 🔑 Default Credentials

### Administrator Portal (`admin.php`)
- **URL**: `http://localhost/knights/fot_arena/Chess/admin.php`
- **Username**: `admin`
- **Password**: `admin123`

### Captain Portal (`captain.php`)
- **URL**: `http://localhost/knights/fot_arena/Chess/captain.php`
- **Team Name**: Your registered team name (e.g. `FOT Gambit Kings`)
- **Password**: The password defined by the captain during registration

---

## 🔄 Portals & User Flows

### Workflow Example: Running a Tournament Round
1. **Registration**: Teams register via `index.php#register`.
2. **Approval**: Admin logs into `admin.php`, reviews squad rosters for eligibility, and clicks **Approve for Standings**.
3. **Rounds & Drop Deadlines**: Admin creates or opens Round 1 in **Tournament Rounds & Drop Boards**, setting the drop board deadline.
4. **Captains Submit Drops**: Captains log into `captain.php`, select which reserve player(s) will sit out, and submit.
5. **Pairing**: Admin visits **Pairings & Matches Manager**, selects Team A vs Team B for Round 1, and creates the match.
6. **Print Lineups**: Admin opens **View Final Boards (Printable)** to print the active 4 boards for physical board placards.
7. **Score Entry**: After matches finish, Admin clicks **Enter Results** next to the match, chooses the outcome (`1-0`, `0.5-0.5`, `0-1`) for each of the 4 boards, and sets the match status to **Completed**.
8. **Live Sync**: The system calculates Game Points and Match Points (applying the `-1 MP` penalty if applicable), automatically updates team rankings, and reflects changes on `index.php` in real-time.

---

## 🗄️ Database Schema

The database consists of 8 interconnected tables:
- `teams`: Team name, contact information, captain password hash, approval status, and aggregate statistics (P, W, D, L, GP, MP, SB).
- `members`: Registered players, board order (1–6), registration number (`TG/YYYY/NNNN`), gender, batch year, and captain flag.
- `admins`: Administrator usernames and hashed passwords.
- `tournament_settings`: Key-value storage for scoreboard visibility, status text, countdown target, and timer state.
- `rounds`: Round numbers, labels, drop board deadlines, schedule date/time, and open/closed status.
- `drop_boards`: Submitted drop board member IDs per team per round.
- `matches`: Scheduled pairings between Team A and Team B, match status, and aggregate points.
- `match_boards`: Individual board 1–4 player assignments and game results (`1-0`, `0.5-0.5`, `0-1`).

---

## 📄 License
Developed for the Faculty of Technology, University of Ruhuna. All rights reserved.

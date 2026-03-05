# Golf League Manager - React.js + Node.js Rebuild Guide

Complete instructions to rebuild the Laravel/Blade golf league management application as a **React.js** frontend with a **Node.js/Express** backend API.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Tech Stack](#2-tech-stack)
3. [Project Structure](#3-project-structure)
4. [Database Schema](#4-database-schema)
5. [Backend API (Node.js/Express)](#5-backend-api-nodejs--express)
6. [Frontend Application (React)](#6-frontend-application-react)
7. [Authentication & Authorization](#7-authentication--authorization)
8. [Core Business Logic](#8-core-business-logic)
9. [Page-by-Page Rebuild Reference](#9-page-by-page-rebuild-reference)
10. [Third-Party Integrations](#10-third-party-integrations)
11. [Deployment](#11-deployment)

---

## 1. Architecture Overview

### Current (Laravel)
```
Browser → Nginx → Laravel (PHP) → Blade Views + MySQL
```

### Target (React + Node.js)
```
Browser → React SPA (Vite) → Node.js/Express REST API → MySQL
         (port 3000)          (port 4000)
```

**Key architectural changes:**
- Server-rendered Blade templates → React SPA with client-side routing
- Laravel Eloquent ORM → Knex.js query builder (or Prisma/Sequelize)
- Laravel middleware → Express middleware
- Blade `@csrf` → JWT or session-based auth with httpOnly cookies
- Laravel Mail → Nodemailer
- Artisan commands → Node.js CLI scripts (or node-cron scheduled tasks)

---

## 2. Tech Stack

### Backend
| Component | Package |
|---|---|
| Runtime | Node.js 20+ |
| Framework | Express.js |
| ORM/Query Builder | Knex.js + Objection.js (recommended) or Prisma |
| Database | MySQL 8 / MariaDB 10.6+ |
| Authentication | Passport.js with passport-local + JWT (jsonwebtoken) |
| Password Hashing | bcryptjs |
| Validation | express-validator or Joi |
| Email | Nodemailer (SMTP) |
| SMS | @vonage/server-sdk |
| Google Drive | googleapis |
| File Upload | multer |
| CSV Parsing | csv-parse |
| Scheduling | node-cron |
| Database Backup | mysqldump (child_process exec) |
| Environment | dotenv |
| CORS | cors |

### Frontend
| Component | Package |
|---|---|
| Framework | React 18+ |
| Build Tool | Vite |
| Routing | React Router v6 |
| State Management | React Context + useReducer (or Zustand) |
| HTTP Client | Axios |
| Forms | React Hook Form |
| Charts | Chart.js + react-chartjs-2 |
| Styling | Tailwind CSS 4 (or CSS Modules to match current inline styles) |
| Tables | @tanstack/react-table |
| Notifications | react-hot-toast |
| Date Handling | date-fns |
| Drag & Drop | @dnd-kit/core (for team/schedule management) |
| Print | react-to-print |

---

## 3. Project Structure

```
golf-league/
├── server/                          # Node.js backend
│   ├── src/
│   │   ├── index.js                 # Express app entry point
│   │   ├── config/
│   │   │   ├── database.js          # Knex connection config
│   │   │   ├── mail.js              # Nodemailer transport config
│   │   │   └── vonage.js            # Vonage SMS config
│   │   ├── middleware/
│   │   │   ├── auth.js              # JWT verification
│   │   │   ├── admin.js             # Admin role check
│   │   │   ├── superAdmin.js        # Super admin role check
│   │   │   └── validate.js          # Request validation wrapper
│   │   ├── routes/
│   │   │   ├── auth.js              # Login, logout, password reset
│   │   │   ├── profile.js           # User profile management
│   │   │   ├── players.js           # Player CRUD + stats
│   │   │   ├── leagues.js           # League CRUD + management
│   │   │   ├── matches.js           # Match CRUD + scoring
│   │   │   ├── teams.js             # Team CRUD + player assignment
│   │   │   ├── courses.js           # Golf course CRUD + teeboxes
│   │   │   ├── segments.js          # League segment management
│   │   │   ├── import.js            # CSV import endpoints
│   │   │   ├── admin.js             # Admin dashboard + user management
│   │   │   ├── superAdmin.js        # Backup, theme, role management
│   │   │   ├── notifications.js     # Email + SMS sending
│   │   │   └── finances.js          # League financial tracking
│   │   ├── services/
│   │   │   ├── HandicapCalculator.js
│   │   │   ├── MatchPlayCalculator.js
│   │   │   ├── LeagueScheduler.js
│   │   │   ├── SmsService.js
│   │   │   ├── GoogleDriveService.js
│   │   │   └── BackupService.js
│   │   ├── models/                  # Objection.js models (or Prisma schema)
│   │   │   ├── User.js
│   │   │   ├── Player.js
│   │   │   ├── GolfCourse.js
│   │   │   ├── CourseInfo.js
│   │   │   ├── League.js
│   │   │   ├── LeagueSegment.js
│   │   │   ├── Team.js
│   │   │   ├── LeagueMatch.js
│   │   │   ├── MatchPlayer.js
│   │   │   ├── MatchScore.js
│   │   │   ├── MatchResult.js
│   │   │   ├── Round.js
│   │   │   ├── Score.js
│   │   │   ├── HandicapHistory.js
│   │   │   ├── ScoringSetting.js
│   │   │   ├── Par3Winner.js
│   │   │   ├── LeagueFinance.js
│   │   │   └── SiteSetting.js
│   │   ├── emails/                  # Email templates (HTML)
│   │   │   ├── weeklyResults.js
│   │   │   ├── leagueMessage.js
│   │   │   └── backup.js
│   │   ├── scripts/                 # CLI scripts (replacements for Artisan commands)
│   │   │   ├── calculateHandicaps.js
│   │   │   ├── recalculateMatches.js
│   │   │   ├── backupDatabase.js
│   │   │   └── populateNineHoleRatings.js
│   │   └── utils/
│   │       ├── csvParser.js
│   │       ├── colorUtils.js        # Theme color manipulation
│   │       └── phoneFormatter.js
│   ├── migrations/                  # Knex migration files
│   ├── seeds/                       # Knex seed files
│   ├── knexfile.js
│   ├── package.json
│   └── .env
│
├── client/                          # React frontend
│   ├── src/
│   │   ├── main.jsx                 # Entry point
│   │   ├── App.jsx                  # Root component + Router
│   │   ├── api/
│   │   │   ├── client.js            # Axios instance with interceptors
│   │   │   ├── auth.js              # Auth API calls
│   │   │   ├── players.js           # Player API calls
│   │   │   ├── leagues.js           # League API calls
│   │   │   ├── matches.js           # Match API calls
│   │   │   ├── courses.js           # Course API calls
│   │   │   ├── teams.js             # Team API calls
│   │   │   └── admin.js             # Admin API calls
│   │   ├── context/
│   │   │   ├── AuthContext.jsx       # Auth state provider
│   │   │   └── ThemeContext.jsx      # Theme colors provider
│   │   ├── hooks/
│   │   │   ├── useAuth.js
│   │   │   ├── useLeague.js
│   │   │   └── useToast.js
│   │   ├── components/
│   │   │   ├── layout/
│   │   │   │   ├── Navbar.jsx
│   │   │   │   ├── AdminNavbar.jsx
│   │   │   │   ├── Footer.jsx
│   │   │   │   └── PageLayout.jsx
│   │   │   ├── common/
│   │   │   │   ├── Badge.jsx
│   │   │   │   ├── Card.jsx
│   │   │   │   ├── Modal.jsx
│   │   │   │   ├── Pagination.jsx
│   │   │   │   ├── Toast.jsx
│   │   │   │   ├── Table.jsx
│   │   │   │   ├── LoadingSpinner.jsx
│   │   │   │   └── ConfirmDialog.jsx
│   │   │   ├── scorecard/
│   │   │   │   ├── ScorecardTable.jsx
│   │   │   │   ├── ScoreEntry.jsx
│   │   │   │   └── PrintableScorecard.jsx
│   │   │   ├── charts/
│   │   │   │   ├── ScoreTrendChart.jsx
│   │   │   │   └── HandicapChart.jsx
│   │   │   ├── league/
│   │   │   │   ├── StandingsTable.jsx
│   │   │   │   ├── WeekSection.jsx
│   │   │   │   ├── MatchCard.jsx
│   │   │   │   ├── TeamCard.jsx
│   │   │   │   └── SegmentTabs.jsx
│   │   │   └── forms/
│   │   │       ├── LeagueForm.jsx
│   │   │       ├── CourseForm.jsx
│   │   │       ├── MatchForm.jsx
│   │   │       ├── PlayerForm.jsx
│   │   │       └── TeamForm.jsx
│   │   ├── pages/
│   │   │   ├── public/
│   │   │   │   ├── HomePage.jsx          # Main dashboard (home.blade.php)
│   │   │   │   ├── PlayersPage.jsx       # Player listing
│   │   │   │   ├── PlayerProfilePage.jsx # Player detail + charts
│   │   │   │   ├── PlayerRoundPage.jsx   # Round detail
│   │   │   │   ├── MatchDetailPage.jsx   # Match scorecard view
│   │   │   │   ├── PrivacyPage.jsx
│   │   │   │   └── SmsTermsPage.jsx
│   │   │   ├── auth/
│   │   │   │   ├── LoginPage.jsx
│   │   │   │   ├── ForgotPasswordPage.jsx
│   │   │   │   └── ResetPasswordPage.jsx
│   │   │   ├── profile/
│   │   │   │   └── ProfilePage.jsx
│   │   │   ├── admin/
│   │   │   │   ├── DashboardPage.jsx
│   │   │   │   ├── PlayersManagePage.jsx
│   │   │   │   ├── PlayerEditPage.jsx
│   │   │   │   ├── UsersManagePage.jsx
│   │   │   │   ├── UserEditPage.jsx
│   │   │   │   ├── UserCreatePage.jsx
│   │   │   │   ├── CoursesPage.jsx
│   │   │   │   ├── CourseCreatePage.jsx
│   │   │   │   ├── CourseEditPage.jsx
│   │   │   │   ├── CourseShowPage.jsx
│   │   │   │   ├── TeeboxManagePage.jsx
│   │   │   │   ├── ImportCoursesPage.jsx
│   │   │   │   └── ImportScoresPage.jsx
│   │   │   ├── leagues/
│   │   │   │   ├── LeaguesListPage.jsx
│   │   │   │   ├── LeagueCreatePage.jsx
│   │   │   │   ├── LeagueEditPage.jsx
│   │   │   │   ├── LeagueShowPage.jsx
│   │   │   │   ├── ManagePlayersPage.jsx
│   │   │   │   ├── ManageTeamsPage.jsx
│   │   │   │   ├── SegmentsPage.jsx
│   │   │   │   ├── AutoSchedulePage.jsx
│   │   │   │   ├── ScheduleOverviewPage.jsx
│   │   │   │   ├── SchedulePreviewPage.jsx
│   │   │   │   ├── WeeklyScoresPage.jsx
│   │   │   │   ├── HoleStatsPage.jsx
│   │   │   │   ├── FinancesPage.jsx
│   │   │   │   ├── ScoringSettingsPage.jsx
│   │   │   │   ├── PrintScorecardsPage.jsx
│   │   │   │   ├── EmailResultsPage.jsx
│   │   │   │   ├── EmailMessagePage.jsx
│   │   │   │   ├── SmsResultsPage.jsx
│   │   │   │   └── SmsMessagePage.jsx
│   │   │   ├── matches/
│   │   │   │   ├── MatchCreatePage.jsx
│   │   │   │   ├── AssignPlayersPage.jsx
│   │   │   │   └── ScoreEntryPage.jsx
│   │   │   ├── teams/
│   │   │   │   └── TeamShowPage.jsx
│   │   │   └── super/
│   │   │       └── SuperAdminPage.jsx
│   │   └── styles/
│   │       ├── index.css             # Global styles + Tailwind
│   │       └── print.css             # Print-specific styles
│   ├── index.html
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── package.json
│
├── .env.example
└── README.md
```

---

## 4. Database Schema

The database schema stays identical. Use Knex.js migrations to recreate all tables.

### 4.1 Migration Files to Create

Create these Knex migrations in order. Each mirrors the Laravel migration.

#### `001_create_users_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('users', table => {
    table.bigIncrements('id');
    table.string('name');
    table.string('email').unique();
    table.timestamp('email_verified_at').nullable();
    table.string('password');
    table.text('remember_token').nullable();
    table.boolean('is_admin').defaultTo(false);
    table.boolean('is_super_admin').defaultTo(false);
    table.timestamps(true, true);
  });
};
```

#### `002_create_players_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('players', table => {
    table.bigIncrements('id');
    table.string('first_name');
    table.string('last_name');
    table.string('email').unique().nullable();
    table.string('phone_number').nullable();
    table.boolean('email_enabled').defaultTo(true);
    table.boolean('sms_enabled').defaultTo(true);
    table.timestamps(true, true);
  });
};
```

#### `003_create_golf_courses_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('golf_courses', table => {
    table.bigIncrements('id');
    table.string('name');
    table.text('address');
    table.string('address_link').nullable();
    table.timestamps(true, true);
  });
};
```

#### `004_create_course_info_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('course_info', table => {
    table.bigIncrements('id');
    table.bigInteger('golf_course_id').unsigned().references('id').inTable('golf_courses').onDelete('CASCADE');
    table.string('teebox');
    table.integer('hole_number');
    table.integer('par');
    table.decimal('slope', 5, 1);
    table.decimal('slope_9_front', 5, 1).nullable();
    table.decimal('slope_9_back', 5, 1).nullable();
    table.decimal('rating', 4, 1);
    table.decimal('rating_9_front', 4, 1).nullable();
    table.decimal('rating_9_back', 4, 1).nullable();
    table.integer('handicap').nullable();
    table.integer('yardage').nullable();
    table.timestamps(true, true);
    table.index('golf_course_id');
  });
};
```

#### `005_create_leagues_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('leagues', table => {
    table.bigIncrements('id');
    table.string('name');
    table.string('season');
    table.date('start_date');
    table.date('end_date');
    table.bigInteger('golf_course_id').unsigned().references('id').inTable('golf_courses').onDelete('CASCADE');
    table.string('default_teebox');
    table.boolean('is_active').defaultTo(true);
    table.decimal('fee_per_player', 8, 2).nullable();
    table.decimal('par3_payout', 8, 2).nullable();
    table.decimal('payout_1st_pct', 5, 2).defaultTo(50);
    table.decimal('payout_2nd_pct', 5, 2).defaultTo(30);
    table.decimal('payout_3rd_pct', 5, 2).defaultTo(20);
    table.timestamps(true, true);
  });
};
```

#### `006_create_league_segments_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('league_segments', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.string('name');
    table.integer('start_week');
    table.integer('end_week');
    table.integer('display_order').defaultTo(0);
    table.timestamps(true, true);
    table.unique(['league_id', 'name']);
    table.index(['league_id', 'display_order']);
  });
};
```

#### `007_create_teams_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('teams', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.bigInteger('league_segment_id').unsigned().nullable().references('id').inTable('league_segments').onDelete('CASCADE');
    table.string('name');
    table.bigInteger('captain_id').unsigned().nullable().references('id').inTable('players').onDelete('SET NULL');
    table.integer('wins').defaultTo(0);
    table.integer('losses').defaultTo(0);
    table.integer('ties').defaultTo(0);
    table.timestamps(true, true);
    table.unique(['league_id', 'league_segment_id', 'name']);
  });
};
```

#### `008_create_team_players_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('team_players', table => {
    table.bigIncrements('id');
    table.bigInteger('team_id').unsigned().references('id').inTable('teams').onDelete('CASCADE');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.timestamps(true, true);
    table.unique(['team_id', 'player_id']);
  });
};
```

#### `009_create_league_players_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('league_players', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.timestamps(true, true);
    table.unique(['league_id', 'player_id']);
  });
};
```

#### `010_create_matches_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('matches', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.integer('week_number');
    table.date('match_date');
    table.time('tee_time').nullable();
    table.bigInteger('golf_course_id').unsigned().references('id').inTable('golf_courses').onDelete('CASCADE');
    table.string('teebox');
    table.string('holes').defaultTo('front_9');
    table.string('scoring_type').defaultTo('best_ball_match_play');
    table.string('score_mode').defaultTo('net');
    table.bigInteger('home_team_id').unsigned().nullable().references('id').inTable('teams').onDelete('CASCADE');
    table.bigInteger('away_team_id').unsigned().nullable().references('id').inTable('teams').onDelete('CASCADE');
    table.boolean('ride_with_opponent').defaultTo(false);
    table.enum('status', ['scheduled', 'in_progress', 'completed']).defaultTo('scheduled');
    table.timestamps(true, true);
    table.index(['league_id', 'week_number']);
  });
};
```

#### `011_create_match_players_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('match_players', table => {
    table.bigIncrements('id');
    table.bigInteger('match_id').unsigned().references('id').inTable('matches').onDelete('CASCADE');
    table.bigInteger('team_id').unsigned().nullable().references('id').inTable('teams').onDelete('CASCADE');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.bigInteger('substitute_player_id').unsigned().nullable().references('id').inTable('players').onDelete('SET NULL');
    table.string('substitute_name').nullable();
    table.decimal('handicap_index', 4, 1);
    table.decimal('course_handicap', 4, 1);
    table.integer('position_in_pairing').defaultTo(1);
    table.timestamps(true, true);
    table.index(['match_id', 'team_id']);
  });
};
```

#### `012_create_match_scores_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('match_scores', table => {
    table.bigIncrements('id');
    table.bigInteger('match_player_id').unsigned().references('id').inTable('match_players').onDelete('CASCADE');
    table.integer('hole_number');
    table.integer('strokes');
    table.integer('net_score');
    table.integer('adjusted_gross').nullable();
    table.timestamps(true, true);
    table.unique(['match_player_id', 'hole_number']);
  });
};
```

#### `013_create_match_results_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('match_results', table => {
    table.bigIncrements('id');
    table.bigInteger('match_id').unsigned().unique().references('id').inTable('matches').onDelete('CASCADE');
    table.bigInteger('winning_team_id').unsigned().nullable().references('id').inTable('teams').onDelete('CASCADE');
    table.integer('holes_won_home').defaultTo(0);
    table.integer('holes_won_away').defaultTo(0);
    table.integer('holes_tied').defaultTo(0);
    table.decimal('team_points_home', 4, 2).defaultTo(0);
    table.decimal('team_points_away', 4, 2).defaultTo(0);
    table.timestamps(true, true);
  });
};
```

#### `014_create_rounds_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('rounds', table => {
    table.bigIncrements('id');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.bigInteger('match_player_id').unsigned().nullable().references('id').inTable('match_players').onDelete('SET NULL');
    table.bigInteger('golf_course_id').unsigned().references('id').inTable('golf_courses').onDelete('CASCADE');
    table.string('teebox');
    table.date('played_at');
    table.integer('holes_played').defaultTo(18);
    table.timestamps(true, true);
  });
};
```

#### `015_create_scores_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('scores', table => {
    table.bigIncrements('id');
    table.bigInteger('round_id').unsigned().references('id').inTable('rounds').onDelete('CASCADE');
    table.integer('hole_number');
    table.integer('strokes');
    table.integer('adjusted_gross').nullable();
    table.integer('net_score').nullable();
    table.timestamps(true, true);
  });
};
```

#### `016_create_handicap_history_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('handicap_history', table => {
    table.bigIncrements('id');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.date('calculation_date');
    table.decimal('handicap_index', 4, 1);
    table.integer('rounds_used');
    table.text('score_differentials').nullable(); // JSON string
    table.timestamps(true, true);
    table.index(['player_id', 'calculation_date']);
  });
};
```

#### `017_create_scoring_settings_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('scoring_settings', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.string('scoring_type');
    table.string('outcome');
    table.decimal('points', 5, 2).defaultTo(0);
    table.string('description').nullable();
    table.timestamps(true, true);
    table.unique(['league_id', 'scoring_type', 'outcome']);
  });
};
```

#### `018_create_par3_winners_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('par3_winners', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.integer('week_number');
    table.integer('hole_number');
    table.bigInteger('player_id').unsigned().nullable().references('id').inTable('players').onDelete('SET NULL');
    table.string('distance').nullable();
    table.timestamps(true, true);
    table.unique(['league_id', 'week_number', 'hole_number']);
  });
};
```

#### `019_create_league_finances_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('league_finances', table => {
    table.bigIncrements('id');
    table.bigInteger('league_id').unsigned().references('id').inTable('leagues').onDelete('CASCADE');
    table.bigInteger('player_id').unsigned().references('id').inTable('players').onDelete('CASCADE');
    table.enum('type', ['fee_paid', 'winnings', 'payout']);
    table.decimal('amount', 8, 2);
    table.date('date');
    table.string('notes', 255).nullable();
    table.bigInteger('par3_winner_id').unsigned().nullable().references('id').inTable('par3_winners').onDelete('CASCADE');
    table.timestamps(true, true);
    table.index(['league_id', 'player_id']);
  });
};
```

#### `020_create_site_settings_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('site_settings', table => {
    table.bigIncrements('id');
    table.string('key').unique();
    table.text('value').nullable();
    table.timestamps(true, true);
  }).then(() => {
    return knex('site_settings').insert([
      { key: 'theme_primary_color', value: '#667eea' },
      { key: 'theme_secondary_color', value: '#764ba2' },
      { key: 'theme_name', value: 'classic' },
    ]);
  });
};
```

#### `021_create_sessions_table.js`
```js
exports.up = function(knex) {
  return knex.schema.createTable('sessions', table => {
    table.string('id').primary();
    table.bigInteger('user_id').unsigned().nullable().index();
    table.string('ip_address', 45).nullable();
    table.text('user_agent').nullable();
    table.text('payload');
    table.integer('last_activity').index();
  });
};
```

### 4.2 Entity Relationship Diagram (Summary)

```
GolfCourse ──1:N──> CourseInfo
     │
     └──1:N──> League ──1:N──> LeagueSegment ──1:N──> Team
                  │                                      │
                  ├──M:N──> Player (via league_players)   ├──M:N──> Player (via team_players)
                  │                                      │
                  ├──1:N──> LeagueMatch ──1:N──> MatchPlayer ──1:N──> MatchScore
                  │              │                    │
                  │              └──1:1──> MatchResult └──> Player (+ SubstitutePlayer)
                  │
                  ├──1:N──> ScoringSetting
                  ├──1:N──> Par3Winner ──> Player
                  └──1:N──> LeagueFinance ──> Player

Player ──1:N──> Round ──1:N──> Score
Player ──1:N──> HandicapHistory
User (separate from Player - admin authentication only)
SiteSetting (key-value store for app configuration)
```

---

## 5. Backend API (Node.js / Express)

### 5.1 Express App Setup (`server/src/index.js`)

```js
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const cookieParser = require('cookie-parser');
require('dotenv').config();

const app = express();

app.use(helmet());
app.use(cors({ origin: process.env.CLIENT_URL, credentials: true }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());
app.use('/uploads', express.static('uploads'));

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/profile', require('./routes/profile'));
app.use('/api/players', require('./routes/players'));
app.use('/api/leagues', require('./routes/leagues'));
app.use('/api/matches', require('./routes/matches'));
app.use('/api/teams', require('./routes/teams'));
app.use('/api/courses', require('./routes/courses'));
app.use('/api/segments', require('./routes/segments'));
app.use('/api/import', require('./routes/import'));
app.use('/api/admin', require('./routes/admin'));
app.use('/api/super', require('./routes/superAdmin'));
app.use('/api/notifications', require('./routes/notifications'));
app.use('/api/finances', require('./routes/finances'));

// Public settings endpoint (theme, app name)
app.get('/api/settings/public', async (req, res) => {
  // Return theme colors, app name, slogan for unauthenticated access
});

app.listen(process.env.PORT || 4000);
```

### 5.2 API Endpoint Reference

All endpoints return JSON. Below is the complete route mapping from the Laravel app.

#### Authentication (`/api/auth`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/auth/login` | Login with email/password | No |
| POST | `/api/auth/logout` | Logout (clear cookie) | Yes |
| GET | `/api/auth/me` | Get current user | Yes |
| POST | `/api/auth/forgot-password` | Send reset email | No |
| POST | `/api/auth/reset-password` | Reset with token | No |

#### Profile (`/api/profile`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/profile` | Get profile | Yes |
| PUT | `/api/profile` | Update name/email | Yes |
| PUT | `/api/profile/password` | Change password | Yes |

#### Players (`/api/players`) — Public read, Admin write
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/players` | List all players (with round counts) | No |
| GET | `/api/players/:id` | Player profile + stats + charts | No |
| GET | `/api/players/:id/rounds/:roundId` | Round detail with scorecard | No |
| GET | `/api/players/search?q=` | Search players by name | Admin |
| POST | `/api/players` | Create player(s) — bulk | Admin |
| PUT | `/api/players/:id` | Update player | Admin |
| DELETE | `/api/players/:id` | Delete player | Admin |
| POST | `/api/players/bulk-update` | Bulk update players | Admin |
| POST | `/api/players/recompute-handicaps` | Recalculate all handicaps | Admin |
| GET | `/api/players/export-csv` | Export player scores CSV | Admin |

#### Golf Courses (`/api/courses`) — Public read, Admin write
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/courses` | List all courses | No |
| GET | `/api/courses/:id` | Course detail with teeboxes | No |
| POST | `/api/courses` | Create course with hole data | Admin |
| PUT | `/api/courses/:id` | Update course info | Admin |
| DELETE | `/api/courses/:id` | Delete course | Admin |
| POST | `/api/courses/search` | AI-powered course search | Admin |
| GET | `/api/courses/:id/teeboxes` | List teeboxes | No |
| POST | `/api/courses/:id/teeboxes` | Add teebox | Admin |
| PUT | `/api/courses/:id/teeboxes/:name` | Update teebox | Admin |
| DELETE | `/api/courses/:id/teeboxes/:name` | Delete teebox | Admin |

#### Leagues (`/api/leagues`) — Public read (show), Admin write
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues` | List all leagues | Admin |
| GET | `/api/leagues/:id` | League overview (teams, matches, standings) | No |
| POST | `/api/leagues` | Create league | Admin |
| PUT | `/api/leagues/:id` | Update league | Admin |
| DELETE | `/api/leagues/:id` | Delete league (if no scores) | Admin |
| POST | `/api/leagues/:id/duplicate` | Duplicate league | Admin |
| GET | `/api/leagues/:id/hole-stats` | Hole statistics | No |
| GET | `/api/leagues/:id/hole-stats-partial` | Hole stats (gross/net toggle) | No |
| GET | `/api/leagues/:id/player-stats` | Player stats partial | No |
| GET | `/api/leagues/:id/schedule` | Schedule partial | No |
| GET | `/api/leagues/:id/week-results/:week` | Week results partial | No |

#### League Players (`/api/leagues/:id/players`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/players` | List league players + available | Admin |
| POST | `/api/leagues/:id/players` | Add player to league | Admin |
| DELETE | `/api/leagues/:id/players/:playerId` | Remove player from league | Admin |

#### League Schedule (`/api/leagues/:id/schedule`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/auto-schedule` | Get schedule config form data | Admin |
| POST | `/api/leagues/:id/auto-schedule/generate` | Generate schedule preview | Admin |
| POST | `/api/leagues/:id/auto-schedule/save` | Save generated schedule | Admin |
| GET | `/api/leagues/:id/schedule-overview` | Full schedule with edit data | Admin |
| PUT | `/api/leagues/:id/week/:week/settings` | Update week settings | Admin |
| POST | `/api/leagues/:id/week/:week/reorder` | Reorder matches in week | Admin |
| POST | `/api/leagues/:id/reorder-weeks` | Reorder weeks | Admin |
| DELETE | `/api/leagues/:id/week/:week` | Delete week | Admin |
| POST | `/api/leagues/:id/schedule/add-weeks` | Add more weeks | Admin |
| POST | `/api/leagues/:id/schedule/add-empty-weeks` | Add empty weeks | Admin |

#### League Scores (`/api/leagues/:id/scores`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/scores?week=` | Get weekly scores form data | Admin |
| POST | `/api/leagues/:id/scores` | Store weekly scores | Admin |
| POST | `/api/leagues/:id/par3-winners` | Store par-3 winners | Admin |
| GET | `/api/leagues/:id/scorecards/:week` | Printable scorecards data | Admin |

#### League Scoring Settings (`/api/leagues/:id/scoring`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/scoring` | Get scoring settings | Admin |
| PUT | `/api/leagues/:id/scoring` | Update scoring points | Admin |

#### League Segments (`/api/leagues/:id/segments`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/segments` | List segments | Admin |
| POST | `/api/leagues/:id/segments` | Create segment | Admin |
| PUT | `/api/segments/:id` | Update segment | Admin |
| DELETE | `/api/segments/:id` | Delete segment | Admin |

#### League Finances (`/api/leagues/:id/finances`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/finances` | Get financial data | Admin |
| POST | `/api/leagues/:id/finances` | Add finance entry | Admin |
| DELETE | `/api/leagues/:id/finances/:finId` | Delete finance entry | Admin |

#### League Notifications (`/api/leagues/:id/notifications`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/leagues/:id/email-results/preview?week=` | Preview email HTML | Admin |
| POST | `/api/leagues/:id/email-results` | Send weekly results email | Admin |
| POST | `/api/leagues/:id/email-message` | Send custom email | Admin |
| GET | `/api/leagues/:id/sms-results/preview?week=` | Preview SMS text | Admin |
| POST | `/api/leagues/:id/sms-results` | Send weekly results SMS | Admin |
| POST | `/api/leagues/:id/sms-message` | Send custom SMS | Admin |

#### Matches (`/api/matches`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/matches/:id` | Match detail with scorecard | No |
| POST | `/api/matches` | Create match | Admin |
| GET | `/api/matches/:id/assign-players` | Get assignment data | Admin |
| POST | `/api/matches/:id/players` | Assign players to match | Admin |
| GET | `/api/matches/:id/score-entry` | Get score entry data | Admin |
| POST | `/api/matches/:id/scores` | Store scores | Admin |

#### Match Players (`/api/match-players`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/match-players/:id/swap` | Swap player | Admin |
| PUT | `/api/match-players/:id/handicap` | Update handicap | Admin |
| POST | `/api/match-players/:id/substitute` | Assign substitute | Admin |
| DELETE | `/api/match-players/:id/substitute` | Remove substitute | Admin |

#### Teams (`/api/teams`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/teams/:id` | Team detail | No |
| POST | `/api/teams` | Create team | Admin |
| PUT | `/api/teams/:id` | Update team | Admin |
| DELETE | `/api/teams/:id` | Delete team | Admin |
| POST | `/api/teams/:id/players` | Add players to team | Admin |
| DELETE | `/api/teams/:id/players/:playerId` | Remove player | Admin |

#### Scorecard (`/api/scorecard`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/scorecard/create` | Get form data (courses, players) | Admin |
| POST | `/api/scorecard` | Create scorecard round | Admin |

#### Import (`/api/import`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/import/courses` | Import courses from CSV | Admin |
| POST | `/api/import/scores` | Import scores from CSV | Admin |

#### Admin Dashboard (`/api/admin`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/admin/dashboard` | Dashboard stats | Admin |
| GET | `/api/admin/users` | List users | Admin |
| POST | `/api/admin/users` | Create user | Admin |
| GET | `/api/admin/users/:id` | Get user | Admin |
| PUT | `/api/admin/users/:id` | Update user | Admin |
| DELETE | `/api/admin/users/:id` | Delete user | Admin |
| GET | `/api/admin/schedule-modal/:week` | Schedule modal data | Admin |

#### Super Admin (`/api/super`) — requires super admin role
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/super` | Super admin dashboard data | Super |
| POST | `/api/super/backup` | Download database backup | Super |
| POST | `/api/super/restore` | Restore from SQL file | Super |
| POST | `/api/super/backup-schedule` | Update backup schedule | Super |
| POST | `/api/super/backup-now` | Run backup immediately | Super |
| GET | `/api/super/backup/download/:filename` | Download backup file | Super |
| DELETE | `/api/super/backup/:filename` | Delete backup file | Super |
| POST | `/api/super/users/:id/role` | Change user role | Super |
| POST | `/api/super/users/:id/password` | Reset user password | Super |
| POST | `/api/super/theme` | Update theme colors | Super |
| POST | `/api/super/backup-delivery` | Configure backup delivery | Super |
| POST | `/api/super/backup-test-email` | Test backup email | Super |
| POST | `/api/super/backup-gdrive-creds` | Upload Google creds | Super |
| DELETE | `/api/super/backup-gdrive-creds` | Delete Google creds | Super |
| POST | `/api/super/backup-test-gdrive` | Test Google Drive | Super |

#### Public / Home (`/api/home`)
| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | `/api/home?league=` | Home page data (standings, scores, matches) | No |
| GET | `/api/settings/public` | App name, slogan, theme colors | No |

### 5.3 Middleware Implementation

#### `auth.js` — JWT Authentication
```js
const jwt = require('jsonwebtoken');

module.exports = (req, res, next) => {
  const token = req.cookies.token || req.headers.authorization?.split(' ')[1];
  if (!token) return res.status(401).json({ error: 'Not authenticated' });

  try {
    req.user = jwt.verify(token, process.env.JWT_SECRET);
    next();
  } catch {
    return res.status(401).json({ error: 'Invalid token' });
  }
};
```

#### `admin.js` — Admin Role Check
```js
const auth = require('./auth');

module.exports = [auth, (req, res, next) => {
  if (!req.user.is_admin && !req.user.is_super_admin) {
    return res.status(403).json({ error: 'Admin access required' });
  }
  next();
}];
```

#### `superAdmin.js` — Super Admin Role Check
```js
const auth = require('./auth');

module.exports = [auth, (req, res, next) => {
  if (!req.user.is_super_admin) {
    return res.status(403).json({ error: 'Super admin access required' });
  }
  next();
}];
```

---

## 6. Frontend Application (React)

### 6.1 Routing Setup (`App.jsx`)

```jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import ProtectedRoute from './components/ProtectedRoute';
import AdminRoute from './components/AdminRoute';
import SuperAdminRoute from './components/SuperAdminRoute';

// Pages imported here...

function App() {
  return (
    <AuthProvider>
      <ThemeProvider>
        <BrowserRouter>
          <Routes>
            {/* Public */}
            <Route path="/" element={<HomePage />} />
            <Route path="/players" element={<PlayersPage />} />
            <Route path="/players/:id" element={<PlayerProfilePage />} />
            <Route path="/players/:playerId/rounds/:roundId" element={<PlayerRoundPage />} />
            <Route path="/matches/:id" element={<MatchDetailPage />} />
            <Route path="/leagues/:id/hole-stats" element={<HoleStatsPage />} />
            <Route path="/privacy" element={<PrivacyPage />} />
            <Route path="/sms-terms" element={<SmsTermsPage />} />

            {/* Auth */}
            <Route path="/login" element={<LoginPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password/:token" element={<ResetPasswordPage />} />

            {/* Authenticated */}
            <Route element={<ProtectedRoute />}>
              <Route path="/profile" element={<ProfilePage />} />
            </Route>

            {/* Admin */}
            <Route element={<AdminRoute />}>
              <Route path="/admin/dashboard" element={<DashboardPage />} />
              <Route path="/admin/players" element={<PlayersManagePage />} />
              <Route path="/admin/players/:id/edit" element={<PlayerEditPage />} />
              <Route path="/admin/users" element={<UsersManagePage />} />
              <Route path="/admin/users/create" element={<UserCreatePage />} />
              <Route path="/admin/users/:id/edit" element={<UserEditPage />} />
              <Route path="/admin/courses" element={<CoursesPage />} />
              <Route path="/admin/courses/create" element={<CourseCreatePage />} />
              <Route path="/admin/courses/:id" element={<CourseShowPage />} />
              <Route path="/admin/courses/:id/edit" element={<CourseEditPage />} />
              <Route path="/admin/courses/:id/teeboxes" element={<TeeboxManagePage />} />
              <Route path="/admin/leagues" element={<LeaguesListPage />} />
              <Route path="/admin/leagues/create" element={<LeagueCreatePage />} />
              <Route path="/admin/leagues/:id" element={<LeagueShowPage />} />
              <Route path="/admin/leagues/:id/edit" element={<LeagueEditPage />} />
              <Route path="/admin/leagues/:id/players" element={<ManagePlayersPage />} />
              <Route path="/admin/leagues/:id/teams" element={<ManageTeamsPage />} />
              <Route path="/admin/leagues/:id/segments" element={<SegmentsPage />} />
              <Route path="/admin/leagues/:id/auto-schedule" element={<AutoSchedulePage />} />
              <Route path="/admin/leagues/:id/schedule-overview" element={<ScheduleOverviewPage />} />
              <Route path="/admin/leagues/:id/schedule-preview" element={<SchedulePreviewPage />} />
              <Route path="/admin/leagues/:id/scores" element={<WeeklyScoresPage />} />
              <Route path="/admin/leagues/:id/scoring" element={<ScoringSettingsPage />} />
              <Route path="/admin/leagues/:id/finances" element={<FinancesPage />} />
              <Route path="/admin/leagues/:id/scorecards/:week" element={<PrintScorecardsPage />} />
              <Route path="/admin/leagues/:id/email-results" element={<EmailResultsPage />} />
              <Route path="/admin/leagues/:id/email-message" element={<EmailMessagePage />} />
              <Route path="/admin/leagues/:id/sms-results" element={<SmsResultsPage />} />
              <Route path="/admin/leagues/:id/sms-message" element={<SmsMessagePage />} />
              <Route path="/admin/matches/create/:leagueId" element={<MatchCreatePage />} />
              <Route path="/admin/matches/:id/assign-players" element={<AssignPlayersPage />} />
              <Route path="/admin/matches/:id/score-entry" element={<ScoreEntryPage />} />
              <Route path="/admin/scorecard/create" element={<ScorecardCreatePage />} />
              <Route path="/admin/import/courses" element={<ImportCoursesPage />} />
              <Route path="/admin/import/scores" element={<ImportScoresPage />} />
            </Route>

            {/* Super Admin */}
            <Route element={<SuperAdminRoute />}>
              <Route path="/admin/super" element={<SuperAdminPage />} />
            </Route>
          </Routes>
        </BrowserRouter>
      </ThemeProvider>
    </AuthProvider>
  );
}
```

### 6.2 API Client (`api/client.js`)

```jsx
import axios from 'axios';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:4000/api',
  withCredentials: true,  // send httpOnly cookies
  headers: { 'Content-Type': 'application/json' },
});

// Response interceptor for 401 redirect
client.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default client;
```

### 6.3 Auth Context (`context/AuthContext.jsx`)

```jsx
import { createContext, useContext, useState, useEffect } from 'react';
import client from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    client.get('/auth/me')
      .then(res => setUser(res.data))
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
  }, []);

  const login = async (email, password) => {
    const res = await client.post('/auth/login', { email, password });
    setUser(res.data.user);
    return res.data;
  };

  const logout = async () => {
    await client.post('/auth/logout');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, isAdmin: user?.is_admin, isSuperAdmin: user?.is_super_admin }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
```

### 6.4 Theme Context (`context/ThemeContext.jsx`)

```jsx
import { createContext, useContext, useState, useEffect } from 'react';
import client from '../api/client';

const ThemeContext = createContext(null);

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState({
    primary: '#667eea',
    secondary: '#764ba2',
    appName: 'Golf League',
    slogan: '',
  });

  useEffect(() => {
    client.get('/settings/public').then(res => {
      setTheme(res.data);
      // Apply CSS variables to :root
      document.documentElement.style.setProperty('--primary-color', res.data.primary);
      document.documentElement.style.setProperty('--secondary-color', res.data.secondary);
    });
  }, []);

  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}

export const useTheme = () => useContext(ThemeContext);
```

---

## 7. Authentication & Authorization

### 7.1 User Roles

| Role | `is_admin` | `is_super_admin` | Access |
|------|-----------|-----------------|--------|
| Player | false | false | Public pages, own profile |
| Admin | true | false | All player + league/team/schedule/score management |
| Super Admin | true | true | All admin + backup, theme, user role management |

### 7.2 Auth Flow

1. **Login:** POST `/api/auth/login` → returns JWT in httpOnly cookie + user object
2. **Verification:** Every API request includes cookie; middleware verifies JWT
3. **Logout:** POST `/api/auth/logout` → clears cookie
4. **Password Reset:** Generates token, emails link, POST to reset

### 7.3 Important: Login Restriction

The current app restricts login to admin users only. Non-admin users (players) have no login accounts — they are managed by admins. Reproduce this by checking `is_admin || is_super_admin` during login.

---

## 8. Core Business Logic

These are the most complex parts of the app. Port these service classes from PHP to JavaScript carefully.

### 8.1 HandicapCalculator (`server/src/services/HandicapCalculator.js`)

**WHS (World Handicap System) Rules to implement:**

1. **Score Differential (18 holes):** `(113 / slope) * (adjustedGross - rating)`
2. **Score Differential (9 holes):** Same formula with 9-hole slope/rating
3. **Expected 9-hole Differential:** `handicapIndex * 0.607`
4. **Building differentials list:** Combine 18-hole rounds and paired 9-hole rounds into 18-hole equivalents
5. **Best N of last 20:** Use lookup table:
   - 3 rounds → best 1, minus 2.0
   - 4 rounds → best 1, minus 1.0
   - 5 rounds → best 1, minus 0.0
   - 6 rounds → best 2, minus 1.0
   - 7-8 → best 2, minus 0.0
   - 9-11 → best 3, minus 0.0
   - 12-14 → best 4, minus 0.0
   - 15-16 → best 5, minus 0.0
   - 17-18 → best 6, minus 0.0
   - 19 → best 7, minus 0.0
   - 20 → best 8, minus 0.0
6. **Final HI:** `average(bestDiffs) * 0.96`, capped at 54.0
7. **Course Handicap:** `(HI * slope / 113) + (rating - par)`
8. **Net Double Bogey Cap:** Per hole: `par + 2 + strokesReceived`
9. **Stroke Allocation:** Based on hole handicap ranking — player receives strokes on hardest holes first

### 8.2 MatchPlayCalculator (`server/src/services/MatchPlayCalculator.js`)

**Match formats to implement:**

1. **Individual Match Play** — Each player pair plays head-to-head; compare net (or gross) scores hole by hole
2. **Best Ball Match Play** — Best score from each team on each hole wins the hole
3. **Team 2-Ball Match Play** — Combined scores of both teammates on each hole
4. **Scramble** — All team members play from best drive (always gross scoring)
5. **Stableford** — Points per hole based on score vs par

**For each format:**
- Calculate per-hole winner (home, away, tie)
- Tally holes won by each team
- Apply scoring settings points (win/loss/tie values from `scoring_settings` table)
- Determine match winner and team points

### 8.3 LeagueScheduler (`server/src/services/LeagueScheduler.js`)

**Schedule generation algorithm:**

1. Accept parameters: number of weeks, start date, team-based or random pairings
2. For team-based: Create 2v2 foursomes ensuring all team combinations play
3. Use intelligent shuffle to minimize repeat player pairings across weeks
4. Support tee time intervals (configurable 5-30 min between groups)
5. Return preview data before saving
6. On save: create `matches` + `match_players` records with calculated handicaps

---

## 9. Page-by-Page Rebuild Reference

### 9.1 Public Pages

#### Home Page (`HomePage.jsx`)
**Laravel source:** `home.blade.php` + `HomeController@index`

This is the most complex public page. It displays:
- League selector dropdown (active leagues)
- **Team Standings table** — rank, team name, W-L-T, points (sortable)
- **Player Standings table** — rank, player name, handicap, avg score, W-L-T, par-3 wins, segment points
- **Weekly Scores** — collapsible week sections showing match results with:
  - Team matchup cards
  - Hole-by-hole scorecard with gross/net scores
  - Match result (holes won/lost/tied, points)
  - Color-coded scores (eagle, birdie, par, bogey, etc.)
- **Par-3 Winners** — table of weekly par-3 closest-to-pin winners
- **Upcoming Matches** — next scheduled week with team assignments
- **Segment standings** — if league has segments, show per-segment team standings

**Data needed from API:**
```json
{
  "activeLeagues": [...],
  "selectedLeague": { "id": 1, "name": "...", ... },
  "teamStandings": [{ "rank": 1, "name": "Team A", "wins": 5, "losses": 2, "ties": 1, "points": 12.5 }],
  "playerStandings": [{ "rank": 1, "name": "John Doe", "handicap": 12.3, "avgScore": 42.1, "wins": 4, ... }],
  "weeklyScores": {
    "1": { "matches": [...], "scorecardData": {...} },
    "2": { ... }
  },
  "par3Winners": [...],
  "upcomingMatches": [...],
  "segmentStandings": { "Half 1": [...], "Half 2": [...] }
}
```

**UI features to recreate:**
- Gradient header with app name (beer mug emojis, Impact font, gold color, text-stroke)
- League dropdown selector
- Collapsible week accordion sections (most recent 2 open by default)
- Color-coded score cells (albatross=gold, eagle=orange, birdie=green, par=gray, bogey=tomato, double+=red)
- Responsive grid that collapses to single column on mobile
- Admin gear icon link (visible only to admin users)

#### Player Profile (`PlayerProfilePage.jsx`)
**Laravel source:** `players/show.blade.php`

- Player info header
- Date filter buttons (7 days, 30 days, 90 days, 1 year, all)
- **Score Trend Chart** — Chart.js line chart of scores over time
- **Handicap Progression Chart** — Chart.js line chart of handicap index over time
- Rounds table with date, course, teebox, holes played, total score, differential

#### Match Detail (`MatchDetailPage.jsx`)
**Laravel source:** `matches/show.blade.php`

- Match header (teams, date, course, status)
- Full scorecard table:
  - Hole numbers row (1-9 or 10-18)
  - Par row
  - Each player row with gross strokes, strokes received indicator, net score
  - Per-hole match result (color-coded: green=win, red=loss, gray=tie)
- Match result summary box (winner, score, points)
- Support for all match formats (individual, best ball, 2-ball, scramble)

### 9.2 Admin Pages

#### Admin Dashboard (`DashboardPage.jsx`)
**Laravel source:** `admin/dashboard.blade.php`

- Stats grid: total leagues, active leagues, total players, total matches, total courses, total users
- Recent leagues table with status badges
- Quick links dropdown to common admin actions
- Responsive navbar with hamburger menu

#### Score Entry (`ScoreEntryPage.jsx`)
**Laravel source:** `matches/score-entry.blade.php` + `leagues/weekly-scores.blade.php`

This is the most complex admin page:
- Week selector
- For each match in the week:
  - Home team vs Away team header
  - Scorecard grid: hole columns (1-9 or 10-18) × player rows
  - Number input fields for each cell (strokes 1-15)
  - Par row for reference
  - Auto-calculated totals
- Par-3 winners section below scorecard
- Submit saves all scores, calculates adjusted gross, net scores, match results, team records, and handicaps

#### Manage Teams (`ManageTeamsPage.jsx`)
**Laravel source:** `leagues/manage-teams.blade.php`

- Segment filter tabs (if league has segments)
- Team cards showing team name, captain, players
- Available players panel (unassigned)
- Drag-and-drop or button-based player assignment
- Create/edit/delete team forms
- AJAX-driven (no page reloads)

#### Schedule Overview (`ScheduleOverviewPage.jsx`)
**Laravel source:** `leagues/schedule-overview.blade.php`

- Week-by-week grid showing all matches
- Editable: match date, tee time, holes, scoring type, score mode
- Player swap functionality (click player → select replacement)
- Substitute assignment (search by name)
- Handicap display and manual override
- Drag to reorder matches within a week
- Drag to reorder weeks
- Delete week button (only if no scores recorded)
- Add weeks button

#### League Finances (`FinancesPage.jsx`)
**Laravel source:** `leagues/finances.blade.php`

- Summary cards: total fees collected, total payouts, balance
- Expandable player rows with individual transactions
- Add transaction form (type: fee_paid/winnings/payout, amount, date, notes)
- Transaction badges by type
- Auto-generated par-3 payout entries

### 9.3 Email Templates

For the Node.js backend, recreate these as HTML string templates or use a templating engine like Handlebars:

1. **Weekly Results Email** — Table-based HTML email with inline CSS:
   - Header with league name and theme colors
   - Next week schedule table
   - Team standings table
   - Player standings table
   - Par-3 winners
   - Footer

2. **League Message Email** — Simple formatted message with league branding

3. **Backup Notification Email** — Backup file attached with metadata

---

## 10. Third-Party Integrations

### 10.1 Vonage SMS

```js
// server/src/config/vonage.js
const { Vonage } = require('@vonage/server-sdk');

const vonage = new Vonage({
  apiKey: process.env.VONAGE_KEY,
  apiSecret: process.env.VONAGE_SECRET,
});

// server/src/services/SmsService.js
class SmsService {
  formatPhoneNumber(phone) {
    const digits = phone.replace(/\D/g, '');
    return digits.startsWith('1') ? `+${digits}` : `+1${digits}`;
  }

  async sendSms(to, message) {
    return vonage.sms.send({
      to: this.formatPhoneNumber(to),
      from: process.env.VONAGE_SMS_FROM,
      text: message,
    });
  }

  async sendBulkSms(recipients, message) {
    const results = { success: 0, failed: 0, errors: [] };
    for (const phone of recipients) {
      try {
        await this.sendSms(phone, message);
        results.success++;
      } catch (err) {
        results.failed++;
        results.errors.push({ phone, error: err.message });
      }
    }
    return results;
  }
}
```

### 10.2 Google Drive Backup

```js
// server/src/services/GoogleDriveService.js
const { google } = require('googleapis');
const fs = require('fs');

class GoogleDriveService {
  constructor() {
    const credPath = path.join(__dirname, '../../storage/google/service-account.json');
    if (fs.existsSync(credPath)) {
      const auth = new google.auth.GoogleAuth({
        keyFile: credPath,
        scopes: ['https://www.googleapis.com/auth/drive.file'],
      });
      this.drive = google.drive({ version: 'v3', auth });
    }
  }

  async uploadFile(filePath, folderId) {
    const fileMetadata = {
      name: path.basename(filePath),
      parents: [folderId],
    };
    return this.drive.files.create({
      resource: fileMetadata,
      media: { mimeType: 'application/sql', body: fs.createReadStream(filePath) },
      fields: 'id',
    });
  }

  async testConnection(folderId) {
    return this.drive.files.list({
      q: `'${folderId}' in parents`,
      pageSize: 1,
      fields: 'files(id, name)',
    });
  }
}
```

### 10.3 Database Backup

```js
// server/src/services/BackupService.js
const { exec } = require('child_process');
const path = require('path');

class BackupService {
  async createBackup() {
    const filename = `backup_${new Date().toISOString().slice(0, 10)}.sql`;
    const filepath = path.join(__dirname, '../../backups', filename);

    const cmd = `mysqldump --single-transaction --routines --triggers \
      -h ${process.env.DB_HOST} -u ${process.env.DB_USERNAME} \
      -p'${process.env.DB_PASSWORD}' ${process.env.DB_DATABASE} > ${filepath}`;

    return new Promise((resolve, reject) => {
      exec(cmd, (error) => {
        if (error) reject(error);
        else resolve({ filepath, filename });
      });
    });
  }
}
```

### 10.4 Scheduled Tasks (node-cron)

```js
// server/src/index.js (add to main app)
const cron = require('node-cron');
const BackupService = require('./services/BackupService');
const SiteSetting = require('./models/SiteSetting');

// Check backup schedule every minute
cron.schedule('* * * * *', async () => {
  const enabled = await SiteSetting.get('backup_enabled');
  if (enabled !== '1') return;

  const backupTime = await SiteSetting.get('backup_time', '02:00');
  const [hour, minute] = backupTime.split(':');
  const now = new Date();

  if (now.getHours() === parseInt(hour) && now.getMinutes() === parseInt(minute)) {
    const frequency = await SiteSetting.get('backup_frequency', 'daily');
    // Check frequency (daily/weekly/monthly) before running
    const backup = new BackupService();
    await backup.createBackup();
  }
});
```

---

## 11. Deployment

### 11.1 Environment Variables (`.env`)

```env
# App
APP_NAME="Golf League Manager"
SLOGAN_NAME=""
CLIENT_URL=http://localhost:3000

# Server
PORT=4000
NODE_ENV=production
JWT_SECRET=your-jwt-secret-here

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=golf
DB_USERNAME=golf_user
DB_PASSWORD=your-password

# Mail (SMTP)
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Golf League"

# Vonage SMS
VONAGE_KEY=your-vonage-key
VONAGE_SECRET=your-vonage-secret
VONAGE_SMS_FROM=+1234567890

# Google Drive (optional)
GOOGLE_DRIVE_FOLDER_ID=your-folder-id
```

### 11.2 Development Setup

```bash
# Backend
cd server
npm install
cp .env.example .env   # configure database, etc.
npx knex migrate:latest
npx knex seed:run       # optional seed data
npm run dev              # nodemon src/index.js

# Frontend
cd client
npm install
npm run dev              # vite dev server on port 3000
```

### 11.3 Production Build

```bash
# Build React app
cd client
npm run build            # outputs to client/dist/

# Serve with Express (add to server/src/index.js for production)
if (process.env.NODE_ENV === 'production') {
  app.use(express.static(path.join(__dirname, '../../client/dist')));
  app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, '../../client/dist/index.html'));
  });
}
```

### 11.4 Production Server (Nginx reverse proxy)

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    # React SPA
    location / {
        proxy_pass http://localhost:4000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### 11.5 Process Management

Use PM2 to keep the Node.js server running:

```bash
npm install -g pm2
pm2 start server/src/index.js --name golf-api
pm2 save
pm2 startup
```

---

## Build Order Recommendation

Follow this order to build incrementally, testing as you go:

### Phase 1: Foundation
1. Initialize Node.js project, install dependencies
2. Set up Knex + run all migrations
3. Create Express app with CORS, helmet, cookie-parser
4. Build auth middleware (JWT) and auth routes (login/logout/me)
5. Initialize React project with Vite + Tailwind
6. Build AuthContext, login page, protected routes
7. Build basic layout components (Navbar, PageLayout)

### Phase 2: Data Layer
8. Build all Objection.js (or Prisma) models with relationships
9. Build CRUD routes for players, courses, teams
10. Build React pages for player listing, player profile (with Chart.js)
11. Build course management pages (CRUD + teebox management)

### Phase 3: League Core
12. Build league CRUD routes and pages
13. Build league player management (add/remove players)
14. Build team management with drag-and-drop
15. Build segment management
16. Port HandicapCalculator service to JavaScript
17. Port MatchPlayCalculator service to JavaScript
18. Build scoring settings management

### Phase 4: Schedule & Scoring
19. Port LeagueScheduler service to JavaScript
20. Build auto-schedule generation (preview + save)
21. Build schedule overview with editing
22. Build score entry page (the most complex form)
23. Build match detail view with scorecard
24. Build weekly scores display

### Phase 5: Home Page
25. Build the home/dashboard page (most complex public page)
26. Build all data aggregation queries for standings, scores, etc.
27. Build collapsible week sections with color-coded scorecards
28. Build league selector dropdown

### Phase 6: Communication & Finance
29. Port SmsService to JavaScript
30. Build email results (Nodemailer + HTML template)
31. Build SMS results
32. Build custom email/SMS message sending
33. Build league finances page

### Phase 7: Admin & Super Admin
34. Build admin dashboard with stats
35. Build user management (CRUD)
36. Build CSV import (courses + scores)
37. Build CSV export
38. Port BackupService + GoogleDriveService
39. Build super admin page (backup, restore, theme, roles)

### Phase 8: Polish
40. Build print scorecards page with print CSS
41. Build privacy policy and SMS terms pages
42. Build profile management page
43. Build password reset flow
44. Add loading states, error boundaries, toast notifications
45. Mobile responsive testing across all pages
46. Add node-cron scheduled backup task

---

## Key Differences from Laravel to Watch For

| Laravel Feature | Node.js Equivalent |
|---|---|
| `@csrf` token | JWT in httpOnly cookie (no CSRF needed for API) |
| `old('field')` form repopulation | React Hook Form `defaultValues` |
| `@error('field')` validation display | Axios error response → form `setError()` |
| `session()->flash()` | React toast notifications |
| Blade `@if`, `@foreach` | JSX conditional rendering, `.map()` |
| `{{ config('app.name') }}` | Theme context or env variable |
| Eloquent relationships | Objection.js `$relatedQuery()` or Prisma `include` |
| Laravel pagination | Knex `.limit().offset()` + total count query |
| `redirect()->back()->with()` | React Router `navigate()` + toast |
| Method spoofing `@method('PUT')` | Direct PUT/DELETE from Axios |
| File validation (`mimes:csv`) | Multer file filter |
| Queue (database driver) | Bull queue with Redis, or process inline |
| `php artisan` commands | Node.js scripts in `package.json` scripts |
| Carbon date library | date-fns |
| Collection methods (`->map`, `->filter`) | Native JS array methods |

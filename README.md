# Golf League Manager

A full-featured golf league management application built with Laravel. Manage leagues, teams, schedules, scoring, handicaps, finances, and player communications — all from one place.

## Features

### League & Team Management
- Create and manage multiple leagues with seasons and segments (divisions)
- Organize teams with captain assignments and player rosters
- Duplicate leagues for easy season-to-season setup

### Schedule Generation
- Automated 16-week schedule generation with intelligent player pairing
- Minimizes repeat pairings across weeks
- Supports 2v2 team foursomes and flexible team counts
- Manual editing: reorder weeks/matches, swap players, assign substitutes

### Match Play & Scoring
- **Individual Match Play** — player vs player with hole-by-hole comparison
- **Best Ball Match Play** — best score per team on each hole
- **Team 2-Ball Match Play** — combined team scores per hole
- **Scramble** — all team members play from best drive
- **Stableford** — point-per-hole scoring
- Configurable per-league point values for wins, losses, ties
- Gross and net score modes
- 9-hole (front/back) and 18-hole support
- Per-hole score entry with printable scorecards (2 per page, portrait)

### WHS Handicap System
- Full World Handicap System (2024 rules) compliance
- Score differentials: `(113 / Slope) x (Adjusted Gross - Rating)`
- Best N of last 20 differentials (lookup table from 3-20 rounds)
- 0.96 multiplier, adjustment table for low round counts, 54.0 cap
- Net double bogey per-hole cap (par + 2 + strokes received)
- 9-hole support with expected differential method (HI x 0.6)
- Historical handicap tracking with per-round snapshots
- Course handicap: `(HI x Slope / 113) + (Rating - Par)`
- Hole-by-hole stroke allocation based on hole handicap ranking

### Course Management
- Store multiple courses with tee box configurations
- Per-hole data: par, yardage, handicap ranking, slope, rating
- Separate 9-hole slope/rating for front and back nines

### Financial Tracking
- Per-league fee and payout configuration (1st, 2nd, 3rd place percentages)
- Player ledger with fee tracking
- Par 3 contest payouts and winner tracking

### Sub Requests
- Players can request subs from the public home page
- Admins notified via email and/or SMS (configurable per admin)
- Optional league passphrase (`sub_request_code`) to prevent unauthorized requests
- Rate limiting (5 requests per 60 minutes per IP)
- Sub request code printed on scorecards when configured

### Notifications
- **Email** — weekly results, custom messages, backup delivery
- **SMS** — weekly results and custom messages via Vonage
- Per-player opt-in preferences for email and SMS
- Preview before sending

### Automated Backups
- Scheduled database backups (daily, weekly, or monthly)
- Configurable retention policies
- Delivery via email attachment or Google Drive
- Manual backup download and restore from SQL upload

### Theme Customization
- Admin-configurable primary and secondary colors
- Applied globally across the UI and email templates
- Toggleable logo and icons via environment variables

### League Simulator
- Generate realistic simulated scores for testing
- `php artisan league:simulate {league_id} --weeks=8`

## Tech Stack

- **Backend:** PHP 8.2+ / Laravel 12
- **Database:** MySQL / MariaDB
- **Frontend:** Tailwind CSS 4, Vite
- **SMS:** Vonage SDK
- **Cloud Storage:** Google Drive API (for backups)
- **Web Server:** Nginx with PHP-FPM

## Installation

### Automated (recommended)

The install script sets up everything on a fresh Debian/Ubuntu server: PHP 8.4, Nginx, MariaDB, Node.js, Composer, and the Laravel application.

**Option 1 — Clone first:**
```bash
git clone https://github.com/ptmuldoon/Laravel-Golf.git
cd Laravel-Golf && sudo bash install.sh
```

The script will prompt for:
- Install directory (default: `/var/www/html/golf`)
- App name and URL
- Database name, user, and password
- Admin name, email, and password

### Manual

1. Clone the repository and install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```

2. Copy `.env.example` to `.env` and configure your database, mail, and Vonage settings.

3. Generate the app key and run migrations:
   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. Set permissions and create the storage symlink:
   ```bash
   chown -R www-data:www-data .
   chmod -R 755 .
   chmod -R 775 storage bootstrap/cache
   chmod 640 .env
   php artisan storage:link
   ```

5. Point Nginx (or your web server) at the `public/` directory.

## Environment Variables

Beyond the standard Laravel variables, the app uses:

| Variable | Purpose |
|---|---|
| `VONAGE_KEY` | Vonage API key for SMS |
| `VONAGE_SECRET` | Vonage API secret |
| `VONAGE_SMS_FROM` | Vonage sender phone number |
| `SLOGAN_NAME` | Custom slogan displayed in the UI |
| `SHOW_LOGO` | Show/hide the site logo on the home page (default: `true`) |
| `SHOW_ICONS` | Show/hide the beer mug icons on the home page (default: `true`) |

Google Drive backup credentials are uploaded through the admin UI (service account JSON).

## User Roles

| Role | Access |
|---|---|
| **Player** | View leagues, standings, results, player profiles, own profile editing |
| **Admin** | All player access + league/team/schedule/score/finance management, notifications |
| **Super Admin** | All admin access + backup management, user role management, theme customization |

## Handicap Recalculation

To recalculate handicaps for all players (or a specific player):
```bash
php artisan handicaps:calculate
php artisan handicaps:calculate --player=5
php artisan handicaps:calculate --historical
php artisan handicaps:calculate --fresh
```

## License

This project is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

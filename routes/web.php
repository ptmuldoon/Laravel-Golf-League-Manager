<?php

use App\Http\Controllers\GolfCourseController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ScoringSettingsController;
use App\Http\Controllers\LeagueSegmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\PlayerDashboardController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registration routes
Route::get('/register', [RegistrationController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegistrationController::class, 'register'])->name('register.post');

// Password Reset routes
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Profile routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Player routes (for registered players)
Route::middleware('player')->prefix('player')->name('player.')->group(function () {
    Route::get('/dashboard', [PlayerDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/score-entry', [PlayerDashboardController::class, 'scoreEntry'])->name('score-entry');
    Route::post('/score-entry', [PlayerDashboardController::class, 'storeScore'])->name('score-entry.store');
});

// Admin routes (protected by admin middleware)
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/players', [AdminController::class, 'players'])->name('players');
    Route::get('/players/export-csv', [AdminController::class, 'exportPlayerScoresCsv'])->name('players.exportCsv');
    Route::get('/players/{id}/edit', [AdminController::class, 'editPlayer'])->name('players.edit');
    Route::post('/players', [AdminController::class, 'storePlayer'])->name('players.store');
    Route::put('/players/{id}', [AdminController::class, 'updatePlayer'])->name('players.update');
    Route::delete('/players/{id}', [AdminController::class, 'deletePlayer'])->name('players.delete');
    Route::post('/players/bulk-update', [AdminController::class, 'bulkUpdatePlayers'])->name('players.bulkUpdate');
    Route::post('/players/recompute-handicaps', [AdminController::class, 'recomputeHandicaps'])->name('players.recomputeHandicaps');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/leagues', [AdminController::class, 'leagues'])->name('leagues');
    Route::get('/leagues/{league_id}/scoring', [ScoringSettingsController::class, 'index'])->name('leagues.scoring');
    Route::put('/leagues/{league_id}/scoring', [ScoringSettingsController::class, 'update'])->name('leagues.scoring.update');
    Route::get('/import/scores', [ImportController::class, 'showScoreImport'])->name('import.scores.form');
    Route::post('/import/scores', [ImportController::class, 'importScores'])->name('import.scores.process');
    Route::get('/scorecard/create', [PlayerController::class, 'createScorecard'])->name('scorecard.create');
    Route::post('/scorecard/store', [PlayerController::class, 'storeScorecard'])->name('scorecard.store');
    Route::get('/courses', [GolfCourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [GolfCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses/search', [GolfCourseController::class, 'searchCourse'])->name('courses.search');
    Route::post('/courses', [GolfCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{id}/edit', [GolfCourseController::class, 'edit'])->name('courses.edit');
    Route::get('/courses/{id}', [GolfCourseController::class, 'show'])->name('courses.show');
    Route::put('/courses/{id}', [GolfCourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{id}', [GolfCourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/courses/{course_id}/teeboxes/manage', [GolfCourseController::class, 'manageTeeboxes'])->name('courses.teeboxes.manage');
    Route::post('/courses/{course_id}/teeboxes', [GolfCourseController::class, 'addTeebox'])->name('courses.teeboxes.add');
    Route::put('/courses/{course_id}/teeboxes/{teebox_name}', [GolfCourseController::class, 'updateTeebox'])->name('courses.teeboxes.update');
    Route::delete('/courses/{course_id}/teeboxes/{teebox_name}', [GolfCourseController::class, 'deleteTeebox'])->name('courses.teeboxes.delete');
    // League routes
    Route::get('/leagues/list', [LeagueController::class, 'index'])->name('leagues.index');
    Route::get('/leagues/create', [LeagueController::class, 'create'])->name('leagues.create');
    Route::post('/leagues', [LeagueController::class, 'store'])->name('leagues.store');
    Route::get('/leagues/{id}', [LeagueController::class, 'show'])->name('leagues.show');
    Route::get('/leagues/{id}/edit', [LeagueController::class, 'edit'])->name('leagues.edit');
    Route::put('/leagues/{id}', [LeagueController::class, 'update'])->name('leagues.update');
    Route::delete('/leagues/{id}', [LeagueController::class, 'destroy'])->name('leagues.destroy');
    Route::post('/leagues/{id}/duplicate', [LeagueController::class, 'duplicate'])->name('leagues.duplicate');
    Route::get('/leagues/{league_id}/teams/manage', [LeagueController::class, 'manageTeams'])->name('leagues.teams.manage');
    Route::get('/leagues/{league_id}/players/manage', [LeagueController::class, 'managePlayers'])->name('leagues.players.manage');
    Route::post('/leagues/{league_id}/players', [LeagueController::class, 'addPlayer'])->name('leagues.players.add');
    Route::delete('/leagues/{league_id}/players/{player_id}', [LeagueController::class, 'removePlayer'])->name('leagues.players.remove');
    Route::get('/leagues/{league_id}/auto-schedule', [LeagueController::class, 'showAutoSchedule'])->name('leagues.autoSchedule');
    Route::post('/leagues/{league_id}/auto-schedule/generate', [LeagueController::class, 'generateAutoSchedule'])->name('leagues.generateSchedule');
    Route::post('/leagues/{league_id}/auto-schedule/save', [LeagueController::class, 'saveAutoSchedule'])->name('leagues.saveSchedule');
    Route::get('/leagues/{league_id}/schedule-overview', [LeagueController::class, 'scheduleOverview'])->name('leagues.scheduleOverview');
    Route::put('/leagues/{league_id}/week/{week_number}/settings', [LeagueController::class, 'updateWeekSettings'])->name('leagues.updateWeekSettings');
    Route::post('/leagues/{league_id}/week/{week_number}/reorder', [LeagueController::class, 'reorderWeekMatches'])->name('leagues.reorderWeekMatches');
    Route::post('/leagues/{league_id}/reorder-weeks', [LeagueController::class, 'reorderWeeks'])->name('leagues.reorderWeeks');
    Route::delete('/leagues/{league_id}/week/{week_number}', [LeagueController::class, 'deleteWeek'])->name('leagues.deleteWeek');
    Route::get('/leagues/{league_id}/week/{week_number}/scorecards', [LeagueController::class, 'printScorecards'])->name('leagues.printScorecards');
    Route::post('/leagues/{league_id}/schedule/add-weeks', [LeagueController::class, 'addWeeks'])->name('leagues.addWeeks');
    Route::post('/leagues/{league_id}/schedule/add-empty-weeks', [LeagueController::class, 'addEmptyWeeks'])->name('leagues.addEmptyWeeks');
    Route::post('/matches/{match_id}/assign-player', [LeagueController::class, 'assignPlayerToMatch'])->name('matches.assignPlayer');
    // Segment routes
    Route::get('/leagues/{league_id}/segments', [LeagueSegmentController::class, 'index'])->name('leagues.segments.index');
    Route::post('/leagues/{league_id}/segments', [LeagueSegmentController::class, 'store'])->name('leagues.segments.store');
    Route::put('/segments/{id}', [LeagueSegmentController::class, 'update'])->name('segments.update');
    Route::delete('/segments/{id}', [LeagueSegmentController::class, 'destroy'])->name('segments.destroy');
    Route::post('/match-players/{match_player_id}/swap', [LeagueController::class, 'swapMatchPlayer'])->name('matchPlayers.swap');
    Route::put('/match-players/{match_player_id}/handicap', [LeagueController::class, 'updateMatchPlayerHandicap'])->name('matchPlayers.updateHandicap');
    Route::get('/players/search', [LeagueController::class, 'searchPlayers'])->name('players.search');
    Route::post('/match-players/{match_player_id}/substitute', [LeagueController::class, 'assignSubstitute'])->name('matchPlayers.substitute');
    Route::delete('/match-players/{match_player_id}/substitute', [LeagueController::class, 'removeSubstitute'])->name('matchPlayers.removeSubstitute');
    Route::get('/leagues/{league_id}/scores', [LeagueController::class, 'weeklyScores'])->name('leagues.scores');
    Route::post('/leagues/{league_id}/scores', [LeagueController::class, 'storeWeeklyScores'])->name('leagues.scores.store');
    Route::post('/leagues/{league_id}/par3-winners', [LeagueController::class, 'storePar3Winners'])->name('leagues.par3Winners.store');
    // League email routes
    Route::get('/leagues/{league_id}/email-results', [LeagueController::class, 'showEmailResults'])->name('leagues.emailResults');
    Route::get('/leagues/{league_id}/email-results/preview', [LeagueController::class, 'previewEmailResults'])->name('leagues.previewEmailResults');
    Route::post('/leagues/{league_id}/email-results', [LeagueController::class, 'sendEmailResults'])->name('leagues.sendEmailResults');
    Route::get('/leagues/{league_id}/email-message', [LeagueController::class, 'showEmailMessage'])->name('leagues.emailMessage');
    Route::post('/leagues/{league_id}/email-message', [LeagueController::class, 'sendEmailMessage'])->name('leagues.sendEmailMessage');
    // League SMS routes
    Route::get('/leagues/{league_id}/sms-results', [LeagueController::class, 'showSmsResults'])->name('leagues.smsResults');
    Route::get('/leagues/{league_id}/sms-results/preview', [LeagueController::class, 'previewSmsResults'])->name('leagues.previewSmsResults');
    Route::post('/leagues/{league_id}/sms-results', [LeagueController::class, 'sendSmsResults'])->name('leagues.sendSmsResults');
    Route::get('/leagues/{league_id}/sms-message', [LeagueController::class, 'showSmsMessage'])->name('leagues.smsMessage');
    Route::post('/leagues/{league_id}/sms-message', [LeagueController::class, 'sendSmsMessage'])->name('leagues.sendSmsMessage');
    // League finance routes
    Route::get('/leagues/{league_id}/hole-stats', [LeagueController::class, 'holeStats'])->name('leagues.holeStats');
    Route::get('/leagues/{league_id}/tee-time-distribution', [LeagueController::class, 'teeTimeDistribution'])->name('leagues.teeTimeDistribution');
    Route::post('/leagues/{league_id}/flash-message', [LeagueController::class, 'updateFlashMessage'])->name('leagues.flashMessage.update');
    Route::get('/leagues/{league_id}/finances', [LeagueController::class, 'showFinances'])->name('leagues.finances');
    Route::post('/leagues/{league_id}/finances', [LeagueController::class, 'storeFinance'])->name('leagues.finances.store');
    Route::delete('/leagues/{league_id}/finances/{id}', [LeagueController::class, 'deleteFinance'])->name('leagues.finances.delete');
    // Team routes
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{id}', [TeamController::class, 'show'])->name('teams.show');
    Route::put('/teams/{id}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{id}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team_id}/players', [TeamController::class, 'addPlayer'])->name('teams.players.add');
    Route::delete('/teams/{team_id}/players/{player_id}', [TeamController::class, 'removePlayer'])->name('teams.players.remove');
    // Match routes
    Route::get('/leagues/{league_id}/matches/create', [MatchController::class, 'create'])->name('matches.create');
    Route::post('/matches', [MatchController::class, 'store'])->name('matches.store');
    Route::get('/matches/{id}', [MatchController::class, 'show'])->name('matches.show');
    Route::get('/matches/{match_id}/assign-players', [MatchController::class, 'assignPlayers'])->name('matches.assignPlayers');
    Route::post('/matches/{match_id}/players', [MatchController::class, 'storePlayers'])->name('matches.storePlayers');
    Route::get('/matches/{match_id}/score-entry', [MatchController::class, 'scoreEntry'])->name('matches.scoreEntry');
    Route::post('/matches/{match_id}/scores', [MatchController::class, 'storeScores'])->name('matches.storeScores');
    Route::get('/leagues/{league_id}/schedule-modal-week/{week}', [AdminController::class, 'scheduleModalWeek'])->name('leagues.scheduleModalWeek');

    // Super Admin routes
    Route::middleware('super-admin')->prefix('super')->name('super.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::post('/backup', [SuperAdminController::class, 'backup'])->name('backup');
        Route::post('/restore', [SuperAdminController::class, 'restore'])->name('restore');
        Route::post('/backup-schedule', [SuperAdminController::class, 'updateBackupSchedule'])->name('backup.schedule');
        Route::post('/backup-now', [SuperAdminController::class, 'runBackupNow'])->name('backup.now');
        Route::get('/backup/download/{filename}', [SuperAdminController::class, 'downloadBackup'])->name('backup.download')->where('filename', '[a-zA-Z0-9_\-\.]+');
        Route::delete('/backup/{filename}', [SuperAdminController::class, 'deleteBackup'])->name('backup.delete')->where('filename', '[a-zA-Z0-9_\-\.]+');
        Route::post('/users/{id}/role', [SuperAdminController::class, 'updateUserRole'])->name('users.role');
        Route::post('/users/{id}/password', [SuperAdminController::class, 'resetUserPassword'])->name('users.password');
        Route::post('/theme', [SuperAdminController::class, 'updateTheme'])->name('theme.update');
        Route::post('/site-settings', [SuperAdminController::class, 'updateSiteSettings'])->name('site-settings');
        // Backup delivery routes
        Route::post('/backup-delivery', [SuperAdminController::class, 'updateBackupDelivery'])->name('backup.delivery');
        Route::post('/backup-test-email', [SuperAdminController::class, 'testBackupEmail'])->name('backup.testEmail');
        Route::post('/backup-test-gdrive', [SuperAdminController::class, 'testGdriveConnection'])->name('backup.testGdrive');
    });
});

// Home route - shows weekly league results
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/request-sub', [HomeController::class, 'requestSub'])->name('requestSub')->middleware('throttle:5,60');
Route::get('/privacy', fn () => view('privacy'))->name('privacy');
Route::get('/sms-terms', fn () => view('sms-terms'))->name('sms-terms');

Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/{id}', [PlayerController::class, 'show'])->name('players.show');
Route::get('/players/{player_id}/rounds/{round_id}', [PlayerController::class, 'showRound'])->name('players.round');

Route::get('/matches/{id}', [MatchController::class, 'show'])->name('matches.show');

Route::get('/leagues/{league_id}/hole-stats', [LeagueController::class, 'holeStats'])->name('leagues.holeStats');
Route::get('/leagues/{league_id}/hole-stats-partial', [LeagueController::class, 'holeStatsPartial'])->name('leagues.holeStatsPartial');
Route::get('/leagues/{league_id}/week-results-partial/{week}', [HomeController::class, 'weekResultsPartial'])->name('leagues.weekResultsPartial');
Route::get('/leagues/{league_id}/schedule-partial', [LeagueController::class, 'schedulePartial'])->name('leagues.schedulePartial');
Route::get('/leagues/{league_id}/player-stats-partial', [LeagueController::class, 'playerStatsPartial'])->name('leagues.playerStatsPartial');
Route::get('/leagues/{league_id}/player-history-partial', [LeagueController::class, 'playerHistoryPartial'])->name('leagues.playerHistoryPartial');

// Import routes (admin only)
Route::middleware('admin')->prefix('import')->name('import.')->group(function () {
    Route::get('/courses', [ImportController::class, 'showCourseImport'])->name('courses.form');
    Route::post('/courses', [ImportController::class, 'importCourses'])->name('courses.process');
});

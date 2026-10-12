<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\IndividualController;
use App\Http\Controllers\IndividualEventScoreController;
use App\Http\Controllers\IndividualCompetitionScoreController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeamCompetitionScoreController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamEventScoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LudoController;
use Illuminate\Support\Facades\Route;

// Ludo Tournament Platform Routes
Route::get('/', [LudoController::class, 'home'])->name('ludo.home');
Route::get('/play', [LudoController::class, 'battles'])->name('ludo.play');
Route::get('/battles', [LudoController::class, 'battles'])->name('ludo.battles');
Route::post('/battles/create', [LudoController::class, 'createBattle'])->name('ludo.battles.create');
Route::post('/battles/join/{id}', [LudoController::class, 'joinBattle'])->name('ludo.battles.join');
Route::post('/battles/cancel/{id}', [LudoController::class, 'cancelBattle'])->name('ludo.battles.cancel');
Route::get('/refer', [LudoController::class, 'refer'])->name('ludo.refer');
Route::get('/support', [LudoController::class, 'support'])->name('ludo.support');
Route::get('/wallet', [LudoController::class, 'wallet'])->name('ludo.wallet');
Route::get('/profile', [LudoController::class, 'profile'])->name('ludo.profile');
Route::post('/profile/update', [LudoController::class, 'updateProfile'])->name('ludo.profile.update');
Route::post('/profile/password', [LudoController::class, 'updatePassword'])->name('ludo.profile.password');
Route::get('/history', [LudoController::class, 'history'])->name('ludo.history');
Route::get('/kyc', [LudoController::class, 'kyc'])->name('ludo.kyc');

// Legacy University Competitions home route
Route::get('/competitions-home', [UserController::class, 'showHome']);

// Authentication routes
Route::get('/showregister', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/showlogin', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

// Authorize user for all routes
Route::middleware('auth')->group(function () {
    // competition details page
    Route::get('/competition-details/{id}', [CompetitionController::class, 'index']);
    // competition register
    Route::get('/competition-showregister/{id}', [CompetitionController::class, 'showRegister']);
    Route::post('/competition-register/{id}', [CompetitionController::class, 'competitionRegister']);

    // competition user rank
    Route::get('competition-rank/{id}', [CompetitionController::class, 'competitionRank']);

    // dashboard
    Route::middleware('admin')->group(function () {
        // admins
        Route::get('/dashboard/admins', [SuperAdminController::class, 'showAdmins'])->middleware('superadmin');
        Route::post('/dashboard/add-admin', [SuperAdminController::class, 'addAdmin'])->middleware('superadmin');
        Route::get('/dashboard/delete-admin/{id}', [SuperAdminController::class, 'deleteAdmin'])->middleware('superadmin');
        Route::put('/dashboard/update-admin/{id}', [SuperAdminController::class, 'updateAdmin'])->middleware('superadmin');
        // users
        Route::get('/dashboard/users', [UserController::class, 'showUsers']);
        Route::post('/dashboard/add-user', [UserController::class, 'addUser']);
        Route::get('/dashboard/delete-user/{id}', [UserController::class, 'deleteUser']);
        Route::put('/dashboard/update-user/{id}', [UserController::class, 'updateUser']);
        // competitions
        Route::get('/dashboard/competitions', [CompetitionController::class, 'showCompetitions']);
        Route::post('/dashboard/add-competition', [CompetitionController::class, 'addCompetition']);
        Route::get('/dashboard/delete-competition/{id}', [CompetitionController::class, 'deleteCompetition']);
        Route::put('/dashboard/update-competition/{id}', [CompetitionController::class, 'updateCompetition']);
        // events
        Route::get('/dashboard/events', [EventController::class, 'showEvents']);
        Route::post('/dashboard/add-event', [EventController::class, 'addEvent']);
        Route::get('/dashboard/delete-event/{id}', [EventController::class, 'deleteEvent']);
        Route::put('/dashboard/update-event/{id}', [EventController::class, 'updateEvent']);
        // teams
        Route::get('/dashboard/teams', [TeamController::class, 'showTeams']);
        Route::post('/dashboard/add-team', [TeamController::class, 'addTeam']);
        Route::get('/dashboard/delete-team/{id}', [TeamController::class, 'deleteTeam']);
        Route::put('/dashboard/update-team/{id}', [TeamController::class, 'updateTeam']);
        // teams events score
        Route::get('/dashboard/teams-events-score', [TeamEventScoreController::class, 'showTeamsEventScore']);
        Route::post('/dashboard/add-team-event-score', [TeamEventScoreController::class, 'addTeamEventScore']);
        Route::get('/dashboard/delete-team-event-score/{id}', [TeamEventScoreController::class, 'deleteTeamEventScore']);
        Route::put('/dashboard/update-team-event-score/{id}', [TeamEventScoreController::class, 'updateTeamEventScore']);
        // teams competitions score
        Route::get('/dashboard/teams-competitions-score', [TeamCompetitionScoreController::class, 'showTeamsCompetitionScore']);
        Route::post('/dashboard/add-team-competition-score', [TeamCompetitionScoreController::class, 'addTeamCompetitionScore']);
        Route::get('/dashboard/delete-team-competition-score/{id}', [TeamCompetitionScoreController::class, 'deleteTeamCompetitionScore']);
        Route::put('/dashboard/update-team-competition-score/{id}', [TeamCompetitionScoreController::class, 'updateTeamCompetitionScore']);
        // individuals
        Route::get('/dashboard/individuals', [IndividualController::class, 'showIndividuals']);
        Route::post('/dashboard/add-individual', [IndividualController::class, 'addIndividual']);
        Route::get('/dashboard/delete-individual/{id}', [IndividualController::class, 'deleteIndividual']);
        Route::put('/dashboard/update-individual/{id}', [IndividualController::class, 'updateIndividual']);
        // individuals events score
        Route::get('/dashboard/individuals-events-score', [IndividualEventScoreController::class, 'showIndividualsEventScore']);
        Route::post('/dashboard/add-individual-event-score', [IndividualEventScoreController::class, 'addIndividualEventScore']);
        Route::get('/dashboard/delete-individual-event-score/{id}', [IndividualEventScoreController::class, 'deleteIndividualEventScore']);
        Route::put('/dashboard/update-individual-event-score/{id}', [IndividualEventScoreController::class, 'updateIndividualEventScore']);
        // individuals competitions score
        Route::get('/dashboard/individuals-competitions-score', [IndividualCompetitionScoreController::class, 'showIndividualsCompetitionScore']);
        Route::post('/dashboard/add-individual-competition-score', [IndividualCompetitionScoreController::class, 'addIndividualsCompetitionScore']);
        Route::get('/dashboard/delete-individual-competition-score/{id}', [IndividualCompetitionScoreController::class, 'deleteIndividualsCompetitionScore']);
        Route::put('/dashboard/update-individual-competition-score/{id}', [IndividualCompetitionScoreController::class, 'updateIndividualsCompetitionScore']);
    });
});

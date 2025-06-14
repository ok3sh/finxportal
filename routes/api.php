<?php

// This file is kept for potential future API-only endpoints
// All current endpoints have been moved to web.php for session access

// Candidate routes
Route::get('/candidates', [CandidateController::class, 'index']);
Route::get('/candidates/sources', [CandidateController::class, 'getSources']);
Route::get('/candidates/skills', [CandidateController::class, 'getSkills']);
Route::post('/candidates', [CandidateController::class, 'store']);
Route::put('/candidates/{id}/status', [CandidateController::class, 'updateStatus']);

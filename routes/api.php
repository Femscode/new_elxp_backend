<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



//New register approach
Route::any('/auth/register', [RegisteredUserController::class, 'store2'])->name('signup');

Route::any('/auth/set-password', [RegisteredUserController::class, 'update_password'])->name('set-password');

//authentication route
Route::post('/auth/login', [RegisteredUserController::class, 'login'])->name('signin');
Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('signout');
Route::post('/password/forgot-password', [UserController::class, 'forgot_password']);
Route::post('/password/reset-password', [UserController::class, 'reset'])->name('resetpasswordfield');

//profile route 
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/profile', [UserController::class, 'show'])->name('get-profile');
    Route::post('/profile/update', [UserController::class, 'update'])->name('update-profile');

    Route::group(['prefix' => 'user'], function () {
        Route::any('/change_role/{id}', [UserController::class, 'change_role'])->name('change_role');
    });


    Route::group(['prefix' => 'course'], function () {
        Route::post('/create', [CourseController::class, 'create'])->name('create-course');
        Route::post('/update', [CourseController::class, 'update'])->name('update-course');
        Route::get('/view/{id}', [CourseController::class, 'view'])->name('view-course');
        Route::get('/allcourses/{id}', [CourseController::class, 'allcourses'])->name('all-courses-by-user');
        Route::delete('/delete/{id}', [CourseController::class, 'delete'])->name('delete-course');
        Route::get('/fetchContent/{id}', [CourseController::class, 'fetchCourseContent'])->name('fetch-course-content');
        Route::post('/saveContent', [CourseController::class, 'saveCourseContent'])->name('save-course-content');
    });
    Route::group(['prefix' => 'section'], function () {
        Route::post('/create', [CourseController::class, 'createSection'])->name('create-section');
        Route::post('/update', [CourseController::class, 'updateSection'])->name('update-section');
        Route::get('/fetch/{id}', [CourseController::class, 'fetchSection'])->name('fetch-section');
        Route::delete('/delete/{id}', [CourseController::class, 'deleteSection'])->name('delete-section');
    });
    Route::group(['prefix' => 'content'], function () {
        Route::post('/create', [CourseController::class, 'createContent'])->name('create-content');
        Route::post('/update', [CourseController::class, 'updateContent'])->name('update-content');
        Route::get('/fetch/{id}', [CourseController::class, 'fetchContent'])->name('fetch-content');
        Route::delete('/delete/{id}', [CourseController::class, 'deleteContent'])->name('delete-content');
    });
    Route::group(['prefix' => 'group'], function () {
        Route::post('/create', [GroupController::class, 'create'])->name('create-group');
        Route::post('/update', [GroupController::class, 'update'])->name('update-group');
        Route::get('/view/{id}', [GroupController::class, 'view'])->name('view-group');
        Route::get('/allgroups', [GroupController::class, 'allgroups'])->name('all-groups');
        Route::delete('/delete/{id}', [GroupController::class, 'delete'])->name('delete-group');

        Route::post('/add-course', [GroupController::class, 'add_course'])->name('add-course');
        Route::post('/add-user', [GroupController::class, 'add_user'])->name('add-user');
        Route::post('/add-file', [GroupController::class, 'add_file'])->name('add-file');

        Route::delete('/remove-course/{group_id}/{course_id}', [GroupController::class, 'remove_course'])->name('remove-course');
        Route::delete('/remove-user/{group_id}/{user_id}', [GroupController::class, 'remove_user'])->name('remove-user');
        Route::delete('/remove-file/{group_id}/{file_id}', [GroupController::class, 'remove_file'])->name('remove-file');

        Route::get('/users/{group_id}', [GroupController::class, 'users'])->name('get_group_users');
        Route::get('/courses/{group_id}', [GroupController::class, 'courses'])->name('get_group_courses');
        Route::get('/files', [GroupController::class, 'files'])->name('get_group_files');
    });
});

// User Management Route (Can be accessed with and without authentication)
Route::apiResource('users', UserController::class);

//Transaction Management Route (User must be authenticated using a Bearer Token generated from the login/register api)
Route::middleware(['auth:sanctum'])->apiResource('transactions', TransactionController::class);

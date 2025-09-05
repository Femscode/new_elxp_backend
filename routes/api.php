<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalenderController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SurveyController;
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
        Route::any('/fetchContent', [CourseController::class, 'fetchCourseContent'])->name('fetch-course-content');
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
        Route::get('/fetchdata/{id}', [CourseController::class, 'fetchContentData'])->name('fetch-content');
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
        Route::get('/files/{group_id}', [GroupController::class, 'files'])->name('get_group_files');
    });
});

Route::group(['prefix' => 'discussions', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/', [DiscussionController::class, 'create'])->name('create-discussion');
    Route::post('/create', [DiscussionController::class, 'create'])->name('create-discussion');
    Route::put('/update/{id}', [DiscussionController::class, 'update'])->name('update-discussion');
    Route::delete('/delete/{id}', [DiscussionController::class, 'delete'])->name('delete-discussion');
    Route::get('/course/{course_id}', [DiscussionController::class, 'fetchByCourse'])->name('fetch-discussions-by-course');
    Route::get('/user/{user_id}', [DiscussionController::class, 'fetchByUser'])->name('fetch-discussions-by-user');
    Route::get('/fetchAll', [DiscussionController::class, 'fetchAll'])->name('fetch-all-discussions');
});

Route::group(['prefix' => 'replies', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [DiscussionController::class, 'createReply'])->name('create-reply');
    Route::put('/update/{id}', [DiscussionController::class, 'updateReply'])->name('update-reply');
    Route::delete('/delete/{id}', [DiscussionController::class, 'deleteReply'])->name('delete-reply');
    Route::get('/discussion/{discussion_id}', [DiscussionController::class, 'fetchByDiscussion'])->name('fetch-replies-by-discussion');
});

Route::group(['prefix' => 'calender', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/event', [CalenderController::class, 'index'])->name('event');
    Route::post('/create', [CalenderController::class, 'create'])->name('create_event');
    Route::put('/update/{id}', [CalenderController::class, 'update'])->name('update_event');
    Route::delete('/delete/{id}', [CalenderController::class, 'delete'])->name('delete_event');
    Route::get('/event/{id}', [CalenderController::class, 'fetchByEvent'])->name('fetch_event');
    Route::get('/events', [CalenderController::class, 'count'])->name('event_count');
});


Route::group(['prefix' => 'assignment', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [AssignmentController::class, 'create'])->name("create_assignment");
    Route::put('update/{id}', [AssignmentController::class, 'update'])->name("update_assignment");
    Route::get('show/{id}', [AssignmentController::class, 'show'])->name("show_assignment");
    Route::get('fetch/{id}', [AssignmentController::class, 'fetch'])->name("fetch_assignment");
    Route::delete('delete/{id}', [AssignmentController::class, 'destroy'])->name("delete_assignment");
});

Route::group(['prefix' => 'quiz', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [QuizController::class, 'create'])->name("create_quiz");
    Route::put('update/{id}', [QuizController::class, 'update'])->name("update_quiz");
    Route::get('show/{id}', [QuizController::class, 'show'])->name("show_quiz");
    Route::get('fetch/{id}', [QuizController::class, 'fetch'])->name("fetch_quiz");
    Route::delete('delete/{id}', [QuizController::class, 'destroy'])->name("delete_quiz");
});

Route::group(['prefix' => 'survey', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [SurveyController::class, 'create'])->name("create_survey");
    Route::put('update/{id}', [SurveyController::class, 'update'])->name("update_survey");
    Route::get('show/{id}', [SurveyController::class, 'show'])->name("show_survey");
    Route::get('fetch/{id}', [SurveyController::class, 'fetch'])->name("fetch_survey");
    Route::delete('delete/{id}', [SurveyController::class, 'destroy'])->name("delete_survey");
});


// User Management Route (Can be accessed with and without authentication)
Route::apiResource('users', UserController::class);

//Transaction Management Route (User must be authenticated using a Bearer Token generated from the login/register api)
Route::middleware(['auth:sanctum'])->apiResource('transactions', TransactionController::class);

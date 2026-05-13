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
use App\Http\Controllers\MBIController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\Learners\AuthController as LearnersAuthController;
use App\Http\Controllers\Learners\ProfileController as LearnersProfileController;
use App\Http\Controllers\Learners\CourseController as LearnersCourseController;
use App\Http\Controllers\GradingController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Learners Route Group
Route::group(['prefix' => 'learners'], function () {
    Route::post('/register', [LearnersAuthController::class, 'register']);
    Route::post('/login', [LearnersAuthController::class, 'login']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/profile', [LearnersAuthController::class, 'profile']);
        Route::post('/logout', [LearnersAuthController::class, 'logout']);

        // Profile Management
        Route::post('/profile/update', [LearnersProfileController::class, 'updateProfile']);
        Route::post('/profile/notifications', [LearnersProfileController::class, 'updateNotifications']);
        Route::post('/profile/change-password', [LearnersProfileController::class, 'changePassword']);
        Route::post('/profile/delete-account', [LearnersProfileController::class, 'deleteAccount']);

        // Course Management
        Route::get('/courses', [LearnersCourseController::class, 'index']);
        Route::get('/courses/{uuid}', [LearnersCourseController::class, 'show']);
        Route::get('/courses/{course_id}/contents/{content_id}', [LearnersCourseController::class, 'getContent']);
        Route::post('/courses/content/complete', [LearnersCourseController::class, 'markAsComplete']);
        Route::post('/courses/quiz/submit', [LearnersCourseController::class, 'submitQuiz']);
        Route::post('/courses/assignment/submit', [LearnersCourseController::class, 'submitAssignment']);
        Route::post('/courses/survey/submit', [LearnersCourseController::class, 'submitSurvey']);
        Route::post('/courses/enroll-free', [LearnersCourseController::class, 'enrollFree']);
        Route::post('/courses/payment/initialize', [LearnersCourseController::class, 'initializePayment']);
        Route::post('/courses/payment/verify', [LearnersCourseController::class, 'verifyPayment']);
        Route::get('/evaluations', [LearnersCourseController::class, 'getUserEvaluations']);
        Route::get('/achievements', [LearnersCourseController::class, 'getUserAchievements']);
        Route::get('/dashboard', [LearnersCourseController::class, 'getDashboardStats']);
    });
});

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
        Route::post('/duplicate/{id}', [CourseController::class, 'duplicateSection'])->name('duplicate-section');
    });
    Route::group(['prefix' => 'content'], function () {
        Route::post('/create', [CourseController::class, 'createContent'])->name('create-content');
        Route::post('/update', [CourseController::class, 'updateContent'])->name('update-content');
        Route::get('/fetch/{id}', [CourseController::class, 'fetchContent'])->name('fetch-content');
        Route::get('/fetchdata/{id}', [CourseController::class, 'fetchContentData'])->name('fetch-content');
        Route::delete('/delete/{id}', [CourseController::class, 'deleteContent'])->name('delete-content');
        Route::post('/duplicate/{id}', [CourseController::class, 'duplicateContent'])->name('duplicate-content');
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
    Route::get('/{id}', [DiscussionController::class, 'fetchSingle'])->name('fetch-single-discussion');
    Route::post('/toggle-like/{id}', [DiscussionController::class, 'toggleLike'])->name('toggle-discussion-like');
    Route::post('/toggle-save/{id}', [DiscussionController::class, 'toggleSave'])->name('toggle-discussion-save');
});

Route::group(['prefix' => 'replies', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [DiscussionController::class, 'createReply'])->name('create-reply');
    Route::put('/update/{id}', [DiscussionController::class, 'updateReply'])->name('update-reply');
    Route::delete('/delete/{id}', [DiscussionController::class, 'deleteReply'])->name('delete-reply');
    Route::get('/discussion/{discussion_id}', [DiscussionController::class, 'fetchByDiscussion'])->name('fetch-replies-by-discussion');
    Route::post('/toggle-like/{id}', [DiscussionController::class, 'toggleReplyLike'])->name('toggle-reply-like');
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

Route::group(['prefix' => 'grading', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/overview', [GradingController::class, 'getOverview']);
    Route::get('/courses', [GradingController::class, 'getCourses']);
    Route::get('/course/{uuid}/assessments', [GradingController::class, 'getAssessments']);
    Route::get('/assessment/{contentId}/submissions', [GradingController::class, 'getSubmissions']);
    Route::post('/submission/{submissionId}/grade', [GradingController::class, 'gradeSubmission']);
});

Route::group(['prefix' => 'reports', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/metrics', [ReportController::class, 'getMetrics']);
    Route::get('/course-performance', [ReportController::class, 'getCoursePerformance']);
    Route::get('/detailed', [ReportController::class, 'getDetailedReport']);
});

Route::group(['prefix' => 'mbi'], function() {
    Route::post('contact-us/save', [MBIController::class,'saveContact'])->name('saveContact');
    Route::get('contact-us/fetch/{id}', [MBIController::class,'fetchC ontact'])->name('fetchContact');
    Route::get('contact-us/fetchall', [MBIController::class,'fetchAllContact'])->name('fetchAllContact');
    
    Route::post('newsletter/save', [MBIController::class,'saveNewsletter'])->name('saveNewsletter');
    Route::get('newsletter/fetch/{id}', [MBIController::class,'fetchNewsletter'])->name('fetchNewsletter');
    Route::get('newsletter/fetchall', [MBIController::class,'fetchAllNewsletter'])->name('fetchAllNewsletter');

    Route::post('waiting-list/save', [MBIController::class,'saveWaitingList'])->name('saveWaitingList');
    Route::get('waiting-list/fetch/{id}', [MBIController::class,'fetchWaitingList'])->name('fetchWaitingList');
    Route::get('waiting-list/fetchall', [MBIController::class,'fetchAllWaitingList'])->name('fetchAllWaitingList');
});


// User Management Route (Can be accessed with and without authentication)
Route::apiResource('users', UserController::class);

//Transaction Management Route (User must be authenticated using a Bearer Token generated from the login/register api)
Route::middleware(['auth:sanctum'])->apiResource('transactions', TransactionController::class);

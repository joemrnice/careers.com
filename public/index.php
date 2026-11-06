<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
require $root . '/app/core/Helpers.php';
require $root . '/app/core/Router.php';
require $root . '/app/core/Session.php';
require $root . '/app/core/CSRF.php';
require $root . '/app/core/Auth.php';

if (($config['display_errors'] ?? false) === true) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

Session::start();
$page = Router::page();
$dbError = null;
$db = null;
$formErrors = [];
$resetErrors = [];
$resetLink = null;
$verificationErrors = [];
$verificationLink = null;
$verificationMessage = null;
$careers = [];
$categories = [];
$career = null;
$careerSkills = [];
$careerSaved = false;
$comparisonCareers = [];
$comparisonOptions = [];
$learningPaths = [];
$learningPath = null;
$learningModules = [];
$learningEnrolled = false;
$learningCompleted = 0;
$learningTotal = 0;
$dashboardPaths = [];
$dashboardSavedCareers = [];
$dashboardBookmarks = [];
$dashboardAssessments = [];
$learningStreak = 0;
$dashboardBadges = [];
$dashboardGoals = [];
$goalErrors = [];
$articles = [];
$article = null;
$articleBookmarked = false;
$resources = [];
$assessment = null;
$assessmentQuestions = [];
$assessmentResult = null;
$assessmentErrors = [];
$profileErrors = [];
$profileUser = null;
$profileImageSupported = true;
$adminMetrics = [];
$adminSection = 'overview';
$adminItems = [];
$adminSearch = '';
$userManagementItems = [];
$moderationItems = [];
$adminEdit = null;
$adminLearningPath = null;
$adminLearningModules = [];
$adminFormErrors = [];
$articleComments = [];
$engagementErrors = [];
$discussions = [];
$discussion = null;
$discussionReplies = [];
$communityErrors = [];
$challenges = [];
$challengeErrors = [];
$plannerEntries = [];
$plannerErrors = [];
$journalEntries = [];
$journalErrors = [];
$transitionCareers = [];
$transitionPlans = [];
$transitionErrors = [];
$showcases = [];
$showcaseErrors = [];
$siteSettings = [
    'site_name' => $config['app_name'] ?? 'CareerPath',
    'site_tagline' => 'Discover what you can become.',
    'site_description' => 'CareerPath helps you explore careers, build practical skills, and prepare for the future of work.',
    'contact_email' => '',
];
$moderationType = in_array($_GET['type'] ?? '', ['comments', 'messages', 'discussions', 'reports', 'showcases'], true) ? $_GET['type'] : 'comments';

if (in_array($page, ['home', 'register', 'login', 'forgot-password', 'reset-password', 'verify-email', 'dashboard', 'profile', 'admin', 'admin-path', 'users', 'moderation', 'community', 'challenges', 'planner', 'journal', 'transition', 'showcase', 'compare', 'settings', 'logout', 'careers', 'career', 'learning', 'path', 'articles', 'article', 'resources', 'assessment', 'contact'], true)) {
    try {
        $db = Database::connect($config);
    } catch (PDOException $exception) {
        $dbError = 'The database is not configured yet. Import the schema and configure your local database connection.';
    }
}

if ($db instanceof PDO && $dbError === null) {
    try {
        $settingsQuery = $db->query('SELECT setting_key, setting_value FROM site_settings');
        foreach ($settingsQuery->fetchAll() as $setting) {
            if (array_key_exists($setting['setting_key'], $siteSettings)) {
                $siteSettings[$setting['setting_key']] = (string) $setting['setting_value'];
            }
        }
    } catch (PDOException $exception) {
        if ($page === 'settings') {
            $dbError = 'Site settings are unavailable until the database schema is imported.';
        }
    }
}

if (in_array($page, ['careers', 'career'], true) && $db instanceof PDO && $dbError === null) {
    if ($page === 'career' && is_post() && ($_POST['action'] ?? '') === 'toggle_saved') {
        Auth::requireLogin($config);
        $careerId = filter_var($_POST['career_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null) || $careerId === false) {
            flash('error', 'Your request could not be validated. Please try again.');
        } else {
            $exists = $db->prepare('SELECT id FROM careers WHERE id = :id LIMIT 1');
            $exists->execute(['id' => $careerId]);
            if (!$exists->fetchColumn()) {
                flash('error', 'That career profile is no longer available.');
            } else {
                $saved = $db->prepare('SELECT 1 FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
                $saved->execute(['user_id' => Auth::user()['id'], 'career_id' => $careerId]);
                if ($saved->fetchColumn()) {
                    $delete = $db->prepare('DELETE FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
                    $delete->execute(['user_id' => Auth::user()['id'], 'career_id' => $careerId]);
                    flash('success', 'Career removed from your saved list.');
                } else {
                    $insert = $db->prepare('INSERT INTO saved_careers (user_id, career_id) VALUES (:user_id, :career_id)');
                    $insert->execute(['user_id' => Auth::user()['id'], 'career_id' => $careerId]);
                    flash('success', 'Career saved to your dashboard.');
                }
            }
        }
        redirect($config, '?page=career&slug=' . rawurlencode((string) ($_GET['slug'] ?? '')));
    }

    if ($page === 'careers') {
        $search = trim((string) ($_GET['q'] ?? ''));
        $category = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $level = in_array($_GET['level'] ?? '', ['beginner', 'intermediate', 'advanced'], true) ? $_GET['level'] : '';
        $conditions = [];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(c.title LIKE :search_title OR c.summary LIKE :search_summary OR c.overview LIKE :search_overview)';
            $searchPattern = '%' . $search . '%';
            $parameters['search_title'] = $searchPattern;
            $parameters['search_summary'] = $searchPattern;
            $parameters['search_overview'] = $searchPattern;
        }
        if ($category !== false && $category !== null) {
            $conditions[] = 'c.category_id = :category_id';
            $parameters['category_id'] = $category;
        }
        if ($level !== '') {
            $conditions[] = 'c.level = :level';
            $parameters['level'] = $level;
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $query = $db->prepare("SELECT c.id, c.title, c.slug, c.summary, c.level, c.remote_friendly, cc.name AS category_name FROM careers c LEFT JOIN career_categories cc ON cc.id = c.category_id $where ORDER BY c.title");
        $query->execute($parameters);
        $careers = $query->fetchAll();
        $categories = $db->query('SELECT id, name FROM career_categories ORDER BY name')->fetchAll();
    } else {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $query = $db->prepare('SELECT c.*, cc.name AS category_name FROM careers c LEFT JOIN career_categories cc ON cc.id = c.category_id WHERE c.slug = :slug LIMIT 1');
        $query->execute(['slug' => $slug]);
        $career = $query->fetch() ?: null;
        if ($career) {
            $skillsQuery = $db->prepare('SELECT s.name, cs.importance FROM career_skills cs INNER JOIN skills s ON s.id = cs.skill_id WHERE cs.career_id = :career_id ORDER BY FIELD(cs.importance, "essential", "useful", "advanced"), s.name');
            $skillsQuery->execute(['career_id' => $career['id']]);
            $careerSkills = $skillsQuery->fetchAll();
            $savedQuery = $db->prepare('SELECT 1 FROM saved_careers WHERE user_id = :user_id AND career_id = :career_id');
            $savedQuery->execute(['user_id' => Auth::user()['id'] ?? 0, 'career_id' => $career['id']]);
            $careerSaved = (bool) $savedQuery->fetchColumn();
        }
    }
}

if ($page === 'compare' && $db instanceof PDO && $dbError === null) {
    $comparisonOptions = $db->query('SELECT id, title FROM careers ORDER BY title')->fetchAll();
    $rawIds = $_GET['ids'] ?? [];
    $rawIds = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
    $selectedIds = [];
    foreach ($rawIds as $rawId) {
        $validId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validId !== false && !in_array($validId, $selectedIds, true)) {
            $selectedIds[] = $validId;
        }
    }
    $selectedIds = array_slice($selectedIds, 0, 3);
    if ($selectedIds) {
        $wherePlaceholders = [];
        $orderPlaceholders = [];
        $parameters = [];
        foreach ($selectedIds as $index => $selectedId) {
            $whereKey = 'career_' . $index;
            $orderKey = 'order_' . $index;
            $wherePlaceholders[] = ':' . $whereKey;
            $orderPlaceholders[] = ':' . $orderKey;
            $parameters[$whereKey] = $selectedId;
            $parameters[$orderKey] = $selectedId;
        }
        $comparisonQuery = $db->prepare(
            'SELECT c.id, c.title, c.slug, c.summary, c.overview, c.level, c.remote_friendly, cc.name AS category_name
             FROM careers c
             LEFT JOIN career_categories cc ON cc.id = c.category_id
             WHERE c.id IN (' . implode(', ', $wherePlaceholders) . ')
             ORDER BY FIELD(c.id, ' . implode(', ', $orderPlaceholders) . ')'
        );
        $comparisonQuery->execute($parameters);
        $comparisonCareers = $comparisonQuery->fetchAll();
    }
}

if ($page === 'logout') {
    if (is_post() && CSRF::verify($_POST['_csrf'] ?? null)) {
        Auth::logout();
    }
    redirect($config, '');
}

if ($page === 'profile') {
    Auth::requireLogin($config);
    if ($db instanceof PDO && $dbError === null) {
        try {
            $profileQuery = $db->prepare('SELECT id, name, email, password_hash, avatar_path FROM users WHERE id = :id AND status = "active" LIMIT 1');
            $profileQuery->execute(['id' => Auth::user()['id']]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '42S22' || !str_contains($exception->getMessage(), 'avatar_path')) {
                throw $exception;
            }
            $profileImageSupported = false;
            $profileQuery = $db->prepare('SELECT id, name, email, password_hash FROM users WHERE id = :id AND status = "active" LIMIT 1');
            $profileQuery->execute(['id' => Auth::user()['id']]);
        }
        $profileUser = $profileQuery->fetch() ?: null;
        if (!$profileUser) {
            Auth::logout();
            redirect($config, '?page=login');
        }
        if (is_post()) {
            if (!CSRF::verify($_POST['_csrf'] ?? null)) {
                $profileErrors[] = 'Your form session expired. Please try again.';
            } elseif (($_POST['action'] ?? '') === 'delete_account') {
                $currentPassword = (string) ($_POST['current_password'] ?? '');
                if (!password_verify($currentPassword, $profileUser['password_hash'])) {
                    $profileErrors[] = 'Enter your current password to delete your account.';
                } else {
                    $deleteAccount = $db->prepare('UPDATE users SET status = "deleted", email = CONCAT("deleted+", id, "@invalid.local") WHERE id = :id AND status = "active"');
                    $deleteAccount->execute(['id' => $profileUser['id']]);
                    Auth::logout();
                    redirect($config, '?page=login&deleted=1');
                }
            } elseif (($_POST['action'] ?? '') === 'upload_avatar') {
                if (!$profileImageSupported) {
                    $profileErrors[] = 'Profile images are unavailable until the updated database schema is imported.';
                } elseif (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    $profileErrors[] = 'Choose a valid image file to upload.';
                } else {
                    $upload = $_FILES['avatar'];
                    if ($upload['size'] > 2 * 1024 * 1024) {
                        $profileErrors[] = 'Profile images must be 2 MB or smaller.';
                    } else {
                        $imageInfo = @getimagesize($upload['tmp_name']);
                        $mime = $imageInfo['mime'] ?? '';
                        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                        if (!$imageInfo || !isset($extensions[$mime]) || ($imageInfo[0] ?? 0) < 80 || ($imageInfo[1] ?? 0) < 80) {
                            $profileErrors[] = 'Upload a JPEG, PNG, or WebP image at least 80 by 80 pixels.';
                        } else {
                            $uploadDirectory = $root . '/public/uploads';
                            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                                $profileErrors[] = 'The profile image directory could not be created.';
                            } else {
                                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                                $destination = $uploadDirectory . '/' . $filename;
                                if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                                    $profileErrors[] = 'The profile image could not be saved.';
                                } else {
                                    $avatarPath = 'uploads/' . $filename;
                                    $avatarUpdate = $db->prepare('UPDATE users SET avatar_path = :avatar_path WHERE id = :id');
                                    $avatarUpdate->execute(['avatar_path' => $avatarPath, 'id' => $profileUser['id']]);
                                    if (!empty($profileUser['avatar_path'])) {
                                        $oldAvatar = $root . '/public/' . ltrim((string) $profileUser['avatar_path'], '/');
                                        if (is_file($oldAvatar)) {
                                            unlink($oldAvatar);
                                        }
                                    }
                                    flash('success', 'Your profile image was updated.');
                                    redirect($config, '?page=profile');
                                }
                            }
                        }
                    }
                }
            } elseif (($_POST['action'] ?? '') === 'update_profile') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $currentPassword = (string) ($_POST['current_password'] ?? '');
                $newPassword = (string) ($_POST['new_password'] ?? '');
                $newPasswordConfirmation = (string) ($_POST['new_password_confirmation'] ?? '');
                if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
                    $profileErrors[] = 'Enter a name between 2 and 120 characters.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                    $profileErrors[] = 'Enter a valid email address.';
                }
                if (!password_verify($currentPassword, $profileUser['password_hash'])) {
                    $profileErrors[] = 'Enter your current password to save account changes.';
                }
                if ($newPassword !== '' && (strlen($newPassword) < 8 || !hash_equals($newPassword, $newPasswordConfirmation))) {
                    $profileErrors[] = 'A new password must contain at least 8 characters and match its confirmation.';
                }
                if (!$profileErrors) {
                    $emailCheck = $db->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
                    $emailCheck->execute(['email' => $email, 'id' => $profileUser['id']]);
                    if ($emailCheck->fetchColumn()) {
                        $profileErrors[] = 'That email address is already in use.';
                    } else {
                        try {
                            $sql = $newPassword === ''
                                ? 'UPDATE users SET name = :name, email = :email WHERE id = :id'
                                : 'UPDATE users SET name = :name, email = :email, password_hash = :password_hash WHERE id = :id';
                            $params = ['name' => $name, 'email' => $email, 'id' => $profileUser['id']];
                            if ($newPassword !== '') {
                                $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                            }
                            $updateProfile = $db->prepare($sql);
                            $updateProfile->execute($params);
                            Auth::login(['id' => $profileUser['id'], 'name' => $name, 'email' => $email]);
                            flash('success', 'Your account details were updated.');
                            redirect($config, '?page=profile');
                        } catch (PDOException $exception) {
                            $profileErrors[] = 'Your account could not be updated. Please try again.';
                        }
                    }
                }
            } else {
                $profileErrors[] = 'The account request was not recognized.';
            }
        }
    }
}

if ($page === 'dashboard') {
    Auth::requireLogin($config);
    if ($db instanceof PDO && $dbError === null) {
        if (is_post()) {
            $goalAction = (string) ($_POST['action'] ?? '');
            if (!CSRF::verify($_POST['_csrf'] ?? null)) {
                $goalErrors[] = 'Your form session expired. Please try again.';
            } elseif ($goalAction === 'create_goal') {
                $goalTitle = trim((string) ($_POST['title'] ?? ''));
                $goalDescription = trim((string) ($_POST['description'] ?? ''));
                $goalDate = trim((string) ($_POST['target_date'] ?? ''));
                $goalDateValue = $goalDate === '' ? null : $goalDate;
                if ($goalTitle === '' || strlen($goalTitle) > 220) {
                    $goalErrors[] = 'Enter a goal title up to 220 characters.';
                }
                if ($goalDescription === '' || strlen($goalDescription) > 5000) {
                    $goalErrors[] = 'Describe how you will measure this goal, using up to 5,000 characters.';
                }
                if ($goalDateValue !== null) {
                    $date = DateTime::createFromFormat('Y-m-d', $goalDateValue);
                    if (!$date || $date->format('Y-m-d') !== $goalDateValue) {
                        $goalErrors[] = 'Enter a valid target date.';
                    }
                }
                if (!$goalErrors) {
                    try {
                        $createGoal = $db->prepare('INSERT INTO user_goals (user_id, title, description, target_date) VALUES (:user_id, :title, :description, :target_date)');
                        $createGoal->execute([
                            'user_id' => Auth::user()['id'],
                            'title' => $goalTitle,
                            'description' => $goalDescription,
                            'target_date' => $goalDateValue,
                        ]);
                        flash('success', 'Your goal was added.');
                        redirect($config, '?page=dashboard');
                    } catch (PDOException $exception) {
                        $goalErrors[] = 'Goals are not available yet. Import the updated database schema and try again.';
                    }
                }
            } elseif (in_array($goalAction, ['complete_goal', 'delete_goal'], true)) {
                $goalId = filter_var($_POST['goal_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($goalId === false) {
                    $goalErrors[] = 'That goal could not be found.';
                } else {
                    try {
                        if ($goalAction === 'complete_goal') {
                            $goalUpdate = $db->prepare('UPDATE user_goals SET status = "completed" WHERE id = :id AND user_id = :user_id AND status = "active"');
                            $goalUpdate->execute(['id' => $goalId, 'user_id' => Auth::user()['id']]);
                            flash('success', 'Goal marked as complete.');
                        } else {
                            $goalDelete = $db->prepare('DELETE FROM user_goals WHERE id = :id AND user_id = :user_id');
                            $goalDelete->execute(['id' => $goalId, 'user_id' => Auth::user()['id']]);
                            flash('success', 'Goal removed.');
                        }
                        redirect($config, '?page=dashboard');
                    } catch (PDOException $exception) {
                        $goalErrors[] = 'That goal could not be updated. Please import the updated database schema if needed.';
                    }
                }
            }
        }
        $dashboardQuery = $db->prepare(
            'SELECT lp.title, lp.slug, COUNT(DISTINCT l.id) AS total_lessons,
                COUNT(DISTINCT CASE WHEN prog.user_id = :user_id THEN prog.lesson_id END) AS completed_lessons
             FROM enrollments e
             INNER JOIN learning_paths lp ON lp.id = e.learning_path_id
             LEFT JOIN learning_modules lm ON lm.learning_path_id = lp.id
             LEFT JOIN lessons l ON l.module_id = lm.id
             LEFT JOIN lesson_progress prog ON prog.lesson_id = l.id AND prog.user_id = :progress_user_id
             WHERE e.user_id = :enrollment_user_id
             GROUP BY lp.id, lp.title, lp.slug
             ORDER BY e.enrolled_at DESC'
        );
        $dashboardQuery->execute([
            'user_id' => Auth::user()['id'],
            'progress_user_id' => Auth::user()['id'],
            'enrollment_user_id' => Auth::user()['id'],
        ]);
        $dashboardPaths = $dashboardQuery->fetchAll();
        try {
            $activityQuery = $db->prepare('SELECT DISTINCT DATE(completed_at) AS activity_date FROM lesson_progress WHERE user_id = :user_id AND completed_at >= CURDATE() - INTERVAL 365 DAY ORDER BY activity_date DESC');
            $activityQuery->execute(['user_id' => Auth::user()['id']]);
            $activityDates = array_map(static fn (array $row): string => (string) $row['activity_date'], $activityQuery->fetchAll());
            $activityDateSet = array_fill_keys($activityDates, true);
            $streakDate = new DateTimeImmutable('today');
            if (!isset($activityDateSet[$streakDate->format('Y-m-d')])) {
                $streakDate = $streakDate->modify('-1 day');
            }
            while (isset($activityDateSet[$streakDate->format('Y-m-d')])) {
                $learningStreak++;
                $streakDate = $streakDate->modify('-1 day');
            }
            $completedLessons = array_sum(array_map(static fn (array $path): int => (int) $path['completed_lessons'], $dashboardPaths));
            if ($completedLessons >= 1) {
                $dashboardBadges[] = ['name' => 'First lesson', 'description' => 'Completed your first tracked lesson.'];
            }
            if ($completedLessons >= 5) {
                $dashboardBadges[] = ['name' => 'Building momentum', 'description' => 'Completed at least five lessons.'];
            }
            if ($learningStreak >= 3) {
                $dashboardBadges[] = ['name' => 'Three-day streak', 'description' => 'Learned on three consecutive days.'];
            }
            if ($learningStreak >= 7) {
                $dashboardBadges[] = ['name' => 'Week of progress', 'description' => 'Learned on seven consecutive days.'];
            }
        } catch (PDOException $exception) {
            $goalErrors[] = 'Learning activity insights are unavailable right now.';
        }
        $savedCareersQuery = $db->prepare(
            'SELECT c.title, c.slug, c.summary
             FROM saved_careers sc
             INNER JOIN careers c ON c.id = sc.career_id
             WHERE sc.user_id = :user_id
             ORDER BY sc.created_at DESC'
        );
        $savedCareersQuery->execute(['user_id' => Auth::user()['id']]);
        $dashboardSavedCareers = $savedCareersQuery->fetchAll();
        $bookmarksQuery = $db->prepare(
            'SELECT a.title, a.slug, a.excerpt
             FROM bookmarks b
             INNER JOIN articles a ON a.id = b.article_id
             WHERE b.user_id = :user_id AND a.status = "published"
             ORDER BY b.created_at DESC'
        );
        $bookmarksQuery->execute(['user_id' => Auth::user()['id']]);
        $dashboardBookmarks = $bookmarksQuery->fetchAll();
        try {
            $goalsQuery = $db->prepare('SELECT id, title, description, target_date, status FROM user_goals WHERE user_id = :user_id ORDER BY status ASC, target_date IS NULL, target_date ASC, created_at DESC');
            $goalsQuery->execute(['user_id' => Auth::user()['id']]);
            $dashboardGoals = $goalsQuery->fetchAll();
        } catch (PDOException $exception) {
            $goalErrors[] = 'Goals are unavailable until the updated database schema is imported.';
        }
        try {
            $assessmentHistoryQuery = $db->prepare(
                'SELECT ar.id, ar.result_json, ar.created_at, a.title
                 FROM assessment_results ar
                 INNER JOIN assessments a ON a.id = ar.assessment_id
                 WHERE ar.user_id = :user_id
                 ORDER BY ar.created_at DESC
                 LIMIT 10'
            );
            $assessmentHistoryQuery->execute(['user_id' => Auth::user()['id']]);
            foreach ($assessmentHistoryQuery->fetchAll() as $historyItem) {
                $decodedResult = json_decode((string) $historyItem['result_json'], true);
                $historyItem['recommendations'] = is_array($decodedResult['recommendations'] ?? null)
                    ? array_slice($decodedResult['recommendations'], 0, 3)
                    : [];
                $dashboardAssessments[] = $historyItem;
            }
        } catch (PDOException $exception) {
            $goalErrors[] = 'Assessment history is unavailable until the updated database schema is imported.';
        }
    }

}

if ($page === 'settings') {
    Auth::requireLogin($config);
    if (!$db instanceof PDO || $dbError !== null) {
        $dbError = $dbError ?? 'Site settings are not available until the database is configured.';
    } else {
        Auth::requireAdministrator($config, $db);
    }
    if ($db instanceof PDO && $dbError === null && is_post()) {
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $formErrors[] = 'Your form session expired. Please try again.';
        } else {
            $submittedSettings = [
                'site_name' => trim((string) ($_POST['site_name'] ?? '')),
                'site_tagline' => trim((string) ($_POST['site_tagline'] ?? '')),
                'site_description' => trim((string) ($_POST['site_description'] ?? '')),
                'contact_email' => strtolower(trim((string) ($_POST['contact_email'] ?? ''))),
            ];
            if ($submittedSettings['site_name'] === '' || mb_strlen($submittedSettings['site_name']) > 120) {
                $formErrors[] = 'Enter a site name up to 120 characters.';
            }
            if ($submittedSettings['site_tagline'] === '' || mb_strlen($submittedSettings['site_tagline']) > 220) {
                $formErrors[] = 'Enter a tagline up to 220 characters.';
            }
            if ($submittedSettings['site_description'] === '' || mb_strlen($submittedSettings['site_description']) > 320) {
                $formErrors[] = 'Enter a site description up to 320 characters.';
            }
            if ($submittedSettings['contact_email'] !== '' && !filter_var($submittedSettings['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $formErrors[] = 'Enter a valid contact email address or leave it blank.';
            }
            if (!$formErrors) {
                try {
                    $db->beginTransaction();
                    $settingUpdate = $db->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                    foreach ($submittedSettings as $key => $value) {
                        $settingUpdate->execute(['setting_key' => $key, 'setting_value' => $value]);
                    }
                    $db->commit();
                    $siteSettings = $submittedSettings;
                    $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, ip_address) VALUES (:user_id, :action, :entity_type, :ip)');
                    $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'update', 'entity_type' => 'site_settings', 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                    flash('success', 'Site settings updated.');
                } catch (PDOException $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $formErrors[] = 'Site settings could not be saved. Please try again.';
                }
            }
        }
    }
}

if ($page === 'admin') {
    Auth::requireLogin($config);
    if (!$db instanceof PDO || $dbError !== null) {
        $dbError = $dbError ?? 'The database is not configured yet.';
    } else {
        if (!Auth::isAdministrator($db)) {
            flash('error', 'Administrator access is required for that page.');
            redirect($config, '?page=dashboard');
        }
        $metricQueries = [
            'users' => 'SELECT COUNT(*) FROM users WHERE status = "active"',
            'careers' => 'SELECT COUNT(*) FROM careers',
            'articles' => 'SELECT COUNT(*) FROM articles WHERE status = "published"',
            'drafts' => 'SELECT COUNT(*) FROM articles WHERE status = "draft"',
            'learning_paths' => 'SELECT COUNT(*) FROM learning_paths WHERE published = 1',
            'resources' => 'SELECT COUNT(*) FROM resources WHERE published = 1',
        ];
        foreach ($metricQueries as $metric => $sql) {
            $adminMetrics[$metric] = (int) $db->query($sql)->fetchColumn();
        }
        $adminSection = in_array($_GET['section'] ?? '', ['careers', 'articles', 'resources', 'learning_paths', 'comments', 'messages', 'users'], true) ? $_GET['section'] : 'overview';
        if (in_array($adminSection, ['comments', 'messages'], true)) {
            redirect($config, '?page=moderation&type=' . rawurlencode($adminSection));
        }
        $adminId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($adminSection === 'careers') {
            $categories = $db->query('SELECT id, name FROM career_categories ORDER BY name')->fetchAll();
            if ($adminId !== false && $adminId !== null) {
                $editQuery = $db->prepare('SELECT id, title, slug, summary, overview, level, remote_friendly, category_id FROM careers WHERE id = :id');
                $editQuery->execute(['id' => $adminId]);
                $adminEdit = $editQuery->fetch() ?: null;
            }
            $adminItems = $db->query('SELECT c.id, c.title, c.slug, c.level, cc.name AS category_name FROM careers c LEFT JOIN career_categories cc ON cc.id = c.category_id ORDER BY c.title')->fetchAll();
        } elseif ($adminSection === 'articles') {
            if ($adminId !== false && $adminId !== null) {
                $editQuery = $db->prepare('SELECT id, title, slug, excerpt, body, status FROM articles WHERE id = :id');
                $editQuery->execute(['id' => $adminId]);
                $adminEdit = $editQuery->fetch() ?: null;
            }
            $adminItems = $db->query('SELECT id, title, slug, status, published_at FROM articles ORDER BY created_at DESC')->fetchAll();
        } elseif ($adminSection === 'resources') {
            if ($adminId !== false && $adminId !== null) {
                $editQuery = $db->prepare('SELECT id, title, description, url, category, level, published FROM resources WHERE id = :id');
                $editQuery->execute(['id' => $adminId]);
                $adminEdit = $editQuery->fetch() ?: null;
            }
            $adminItems = $db->query('SELECT id, title, category, level, published FROM resources ORDER BY title')->fetchAll();
        } elseif ($adminSection === 'learning_paths') {
            if ($adminId !== false && $adminId !== null) {
                $editQuery = $db->prepare('SELECT id, title, slug, description, level, published FROM learning_paths WHERE id = :id');
                $editQuery->execute(['id' => $adminId]);
                $adminEdit = $editQuery->fetch() ?: null;
            }
            $adminItems = $db->query('SELECT id, title, slug, level, published FROM learning_paths ORDER BY title')->fetchAll();
        } elseif ($adminSection === 'comments') {
            $adminItems = $db->query('SELECT c.id, c.body AS title, c.status, a.title AS article_title, u.email FROM comments c INNER JOIN users u ON u.id = c.user_id LEFT JOIN articles a ON a.id = c.article_id ORDER BY c.created_at DESC')->fetchAll();
        } elseif ($adminSection === 'messages') {
            $adminItems = $db->query('SELECT id, subject AS title, name, email, status, created_at FROM contact_messages ORDER BY created_at DESC')->fetchAll();
        } elseif ($adminSection === 'users') {
            redirect($config, '?page=users');
        }
    }
}

if ($page === 'admin' && is_post() && $db instanceof PDO && $dbError === null) {
    Auth::requireAdministrator($config, $db);
    $adminSection = in_array($_POST['section'] ?? '', ['careers', 'articles', 'resources', 'learning_paths', 'comments', 'messages', 'users'], true) ? $_POST['section'] : 'overview';
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $adminFormErrors[] = 'Your form session expired. Please try again.';
    } else {
        $adminId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($adminSection === 'users') {
            $userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $userStatus = in_array($_POST['status'] ?? '', ['active', 'suspended', 'deleted'], true) ? $_POST['status'] : null;
            if ($userId === false || $userStatus === null || $userId === (int) Auth::user()['id']) {
                $adminFormErrors[] = 'That user status change is not allowed.';
            } else {
                $userUpdate = $db->prepare('UPDATE users SET status = :status WHERE id = :id');
                $userUpdate->execute(['status' => $userStatus, 'id' => $userId]);
                if ($userUpdate->rowCount() !== 1) {
                    $adminFormErrors[] = 'The user was not found or already has that status.';
                } else {
                    $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
                    $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'update_status', 'entity_type' => 'user', 'entity_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                    flash('success', 'User status updated.');
                    redirect($config, '?page=admin&section=users');
                }
            }
        }
        if (in_array($adminSection, ['comments', 'messages'], true)) {
            $moderationId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $moderationStatus = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected', 'new', 'read', 'closed'], true) ? $_POST['status'] : null;
            if ($moderationId === false || $moderationStatus === null || !CSRF::verify($_POST['_csrf'] ?? null)) {
                $adminFormErrors[] = 'The moderation request could not be validated.';
            } else {
                $table = $adminSection === 'comments' ? 'comments' : 'contact_messages';
                $update = $db->prepare("UPDATE {$table} SET status = :status WHERE id = :id");
                $update->execute(['status' => $moderationStatus, 'id' => $moderationId]);
                $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
                $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'moderate', 'entity_type' => $adminSection, 'entity_id' => $moderationId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                flash('success', ucfirst($adminSection) . ' status updated.');
                redirect($config, '?page=admin&section=' . rawurlencode($adminSection));
            }
        }
        if (!in_array($adminSection, ['comments', 'messages', 'users'], true)) {
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($title === '' || mb_strlen($title) > 220) {
            $adminFormErrors[] = 'Enter a title within the allowed length.';
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $adminFormErrors[] = 'Use a lowercase hyphenated slug.';
        }
        if (!$adminFormErrors) {
            try {
                $db->beginTransaction();
                if ($adminSection === 'careers') {
                    $summary = trim((string) ($_POST['summary'] ?? ''));
                    $overview = trim((string) ($_POST['overview'] ?? ''));
                    $level = in_array($_POST['level'] ?? '', ['beginner', 'intermediate', 'advanced'], true) ? $_POST['level'] : 'beginner';
                    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($summary === '' || $overview === '' || $categoryId === false) {
                        $adminFormErrors[] = 'Complete the career summary, overview, and category.';
                    } else {
                        $sql = $adminId ? 'UPDATE careers SET title=:title,slug=:slug,summary=:summary,overview=:overview,level=:level,remote_friendly=:remote_friendly,category_id=:category_id WHERE id=:id' : 'INSERT INTO careers (title,slug,summary,overview,level,remote_friendly,category_id) VALUES (:title,:slug,:summary,:overview,:level,:remote_friendly,:category_id)';
                        $params = ['title' => $title, 'slug' => $slug, 'summary' => $summary, 'overview' => $overview, 'level' => $level, 'remote_friendly' => isset($_POST['remote_friendly']) ? 1 : 0, 'category_id' => $categoryId];
                    }
                } elseif ($adminSection === 'articles') {
                    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
                    $body = trim((string) ($_POST['body'] ?? ''));
                    $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
                    if ($excerpt === '' || $body === '') {
                        $adminFormErrors[] = 'Complete the excerpt and body.';
                    } else {
                        $sql = $adminId ? 'UPDATE articles SET title=:title,slug=:slug,excerpt=:excerpt,body=:body,status=:status,published_at=IF(:status_date="published",COALESCE(published_at,NOW()),NULL) WHERE id=:id' : 'INSERT INTO articles (title,slug,excerpt,body,status,published_at,author_id) VALUES (:title,:slug,:excerpt,:body,:status,IF(:status_date="published",NOW(),NULL),:author_id)';
                        $params = ['title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'body' => $body, 'status' => $status, 'status_date' => $status, 'author_id' => Auth::user()['id']];
                    }
                } elseif ($adminSection === 'learning_paths') {
                    $description = trim((string) ($_POST['description'] ?? ''));
                    $level = in_array($_POST['level'] ?? '', ['beginner', 'intermediate', 'advanced'], true) ? $_POST['level'] : 'beginner';
                    if ($description === '') {
                        $adminFormErrors[] = 'Complete the learning path description.';
                    } else {
                        $sql = $adminId ? 'UPDATE learning_paths SET title=:title,slug=:slug,description=:description,level=:level,published=:published WHERE id=:id' : 'INSERT INTO learning_paths (title,slug,description,level,published) VALUES (:title,:slug,:description,:level,:published)';
                        $params = ['title' => $title, 'slug' => $slug, 'description' => $description, 'level' => $level, 'published' => isset($_POST['published']) ? 1 : 0];
                    }
                } else {
                    $description = trim((string) ($_POST['description'] ?? ''));
                    $resourceUrl = trim((string) ($_POST['url'] ?? ''));
                    $categoryName = trim((string) ($_POST['category'] ?? ''));
                    $level = in_array($_POST['level'] ?? '', ['beginner', 'intermediate', 'advanced', 'all'], true) ? $_POST['level'] : 'all';
                    if ($description === '' || $categoryName === '' || !filter_var($resourceUrl, FILTER_VALIDATE_URL)) {
                        $adminFormErrors[] = 'Enter a valid URL, category, and description.';
                    } else {
                        $sql = $adminId ? 'UPDATE resources SET title=:title,description=:description,url=:url,category=:category,level=:level,published=:published WHERE id=:id' : 'INSERT INTO resources (title,description,url,category,level,published,reviewed_at) VALUES (:title,:description,:url,:category,:level,:published,CURDATE())';
                        $params = ['title' => $title, 'description' => $description, 'url' => $resourceUrl, 'category' => $categoryName, 'level' => $level, 'published' => isset($_POST['published']) ? 1 : 0];
                    }
                }
                if (!$adminFormErrors) {
                    if ($adminId) {
                        $params['id'] = $adminId;
                    }
                    $statement = $db->prepare($sql);
                    $statement->execute($params);
                    $entityId = $adminId ?: (int) $db->lastInsertId();
                    $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
                    $audit->execute(['user_id' => Auth::user()['id'], 'action' => $adminId ? 'update' : 'create', 'entity_type' => $adminSection, 'entity_id' => $entityId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                    $db->commit();
                    flash('success', ucfirst($adminSection) . ' saved successfully.');
                    redirect($config, '?page=admin&section=' . rawurlencode($adminSection));
                }
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $adminFormErrors[] = 'The record could not be saved. Check that the slug is unique and try again.';
            }
            }
        }
    }
}

if ($page === 'admin-path') {
    Auth::requireLogin($config);
}

if ($page === 'admin-path' && $db instanceof PDO && $dbError === null) {
    Auth::requireAdministrator($config, $db);
    $pathId = filter_var($_GET['id'] ?? $_POST['path_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($pathId === false) {
        redirect($config, '?page=admin&section=learning_paths');
    }
    if (is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $position = filter_var($_POST['position'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null) || !in_array($action, ['add_module', 'add_lesson'], true)) {
            flash('error', 'The learning content request could not be validated.');
        } elseif ($title === '' || mb_strlen($title) > 220 || ($action === 'add_lesson' && ($content === '' || mb_strlen($content) > 10000)) || $position === false) {
            flash('error', 'Enter a valid title, position, and lesson content.');
        } else {
            try {
                if ($action === 'add_module') {
                    $pathCheck = $db->prepare('SELECT id FROM learning_paths WHERE id = :id');
                    $pathCheck->execute(['id' => $pathId]);
                    if (!$pathCheck->fetchColumn()) {
                        throw new RuntimeException('Learning path not found.');
                    }
                    $statement = $db->prepare('INSERT INTO learning_modules (learning_path_id,title,position) VALUES (:path_id,:title,:position)');
                    $statement->execute(['path_id' => $pathId, 'title' => $title, 'position' => $position]);
                    $entityType = 'learning_module';
                    $entityId = (int) $db->lastInsertId();
                } else {
                    $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    $moduleCheck = $db->prepare('SELECT lm.id FROM learning_modules lm WHERE lm.id = :module_id AND lm.learning_path_id = :path_id');
                    $moduleCheck->execute(['module_id' => $moduleId, 'path_id' => $pathId]);
                    if ($moduleId === false || !$moduleCheck->fetchColumn()) {
                        throw new RuntimeException('Learning module not found.');
                    }
                    $statement = $db->prepare('INSERT INTO lessons (module_id,title,content,position) VALUES (:module_id,:title,:content,:position)');
                    $statement->execute(['module_id' => $moduleId, 'title' => $title, 'content' => $content, 'position' => $position]);
                    $entityType = 'lesson';
                    $entityId = (int) $db->lastInsertId();
                }
                $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
                $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'create', 'entity_type' => $entityType, 'entity_id' => $entityId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                flash('success', ucfirst(str_replace('_', ' ', $entityType)) . ' created.');
            } catch (PDOException | RuntimeException $exception) {
                flash('error', 'That position may already be in use or the learning content could not be saved.');
            }
        }
        redirect($config, '?page=admin-path&id=' . $pathId);
    }
    $pathQuery = $db->prepare('SELECT id, title, slug FROM learning_paths WHERE id = :id');
    $pathQuery->execute(['id' => $pathId]);
    $adminLearningPath = $pathQuery->fetch() ?: null;
    if (!$adminLearningPath) {
        redirect($config, '?page=admin&section=learning_paths');
    }
    $moduleQuery = $db->prepare('SELECT id, title, position FROM learning_modules WHERE learning_path_id = :path_id ORDER BY position');
    $moduleQuery->execute(['path_id' => $pathId]);
    $adminLearningModules = $moduleQuery->fetchAll();
    foreach ($adminLearningModules as &$module) {
        $lessonQuery = $db->prepare('SELECT id, title, content, position FROM lessons WHERE module_id = :module_id ORDER BY position');
        $lessonQuery->execute(['module_id' => $module['id']]);
        $module['lessons'] = $lessonQuery->fetchAll();
    }
    unset($module);
}

if ($page === 'moderation' && $db instanceof PDO && $dbError === null) {
    Auth::requireAdministrator($config, $db);
    if (is_post()) {
        $moderationId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $moderationStatus = $_POST['status'] ?? '';
        $allowedStatuses = in_array($moderationType, ['comments', 'discussions', 'showcases'], true) ? ['pending', 'approved', 'rejected'] : ($moderationType === 'reports' ? ['new', 'reviewed', 'dismissed'] : ['new', 'read', 'closed']);
        if (!CSRF::verify($_POST['_csrf'] ?? null) || $moderationId === false || !in_array($moderationStatus, $allowedStatuses, true)) {
            flash('error', 'The moderation request could not be validated.');
        } else {
            $table = match ($moderationType) {
                'comments' => 'comments',
                'discussions' => 'discussions',
                'showcases' => 'project_showcases',
                'reports' => 'discussion_reports',
                default => 'contact_messages',
            };
            $statement = $db->prepare("UPDATE {$table} SET status = :status WHERE id = :id");
            $statement->execute(['status' => $moderationStatus, 'id' => $moderationId]);
            $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
            $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'moderate', 'entity_type' => $moderationType, 'entity_id' => $moderationId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
            flash('success', ucfirst($moderationType) . ' status updated.');
        }
        redirect($config, '?page=moderation&type=' . rawurlencode($moderationType));
    }
    if ($moderationType === 'comments') {
        $moderationItems = $db->query('SELECT c.id, c.body, c.status, c.created_at, u.name, a.title AS article_title FROM comments c INNER JOIN users u ON u.id = c.user_id LEFT JOIN articles a ON a.id = c.article_id ORDER BY c.created_at DESC')->fetchAll();
    } elseif ($moderationType === 'discussions') {
        $moderationItems = $db->query('SELECT d.id, d.title, d.body, d.status, d.created_at, u.name FROM discussions d INNER JOIN users u ON u.id = d.user_id ORDER BY d.created_at DESC')->fetchAll();
    } elseif ($moderationType === 'reports') {
        $moderationItems = $db->query('SELECT dr.id, dr.reason, dr.status, dr.created_at, u.name, d.title AS discussion_title, r.body AS reply_body FROM discussion_reports dr INNER JOIN users u ON u.id = dr.reporter_id LEFT JOIN discussions d ON d.id = dr.discussion_id LEFT JOIN discussion_replies r ON r.id = dr.reply_id ORDER BY dr.created_at DESC')->fetchAll();
    } elseif ($moderationType === 'showcases') {
        $moderationItems = $db->query('SELECT ps.id, ps.title, ps.description, ps.project_url, ps.skills, ps.status, ps.created_at, u.name FROM project_showcases ps INNER JOIN users u ON u.id = ps.user_id ORDER BY ps.created_at DESC')->fetchAll();
    } else {
        $moderationItems = $db->query('SELECT id, name, email, subject, message, status, created_at FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    }
}

if ($page === 'users') {
    Auth::requireLogin($config);
}

if ($page === 'users' && $db instanceof PDO && $dbError === null) {
    Auth::requireAdministrator($config, $db);
    if (is_post()) {
        $userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userStatus = in_array($_POST['status'] ?? '', ['active', 'suspended', 'deleted'], true) ? $_POST['status'] : null;
        if (!CSRF::verify($_POST['_csrf'] ?? null) || $userId === false || $userStatus === null || $userId === (int) Auth::user()['id']) {
            flash('error', 'That user status change is not allowed.');
        } else {
            $userUpdate = $db->prepare('UPDATE users SET status = :status WHERE id = :id');
            $userUpdate->execute(['status' => $userStatus, 'id' => $userId]);
            if ($userUpdate->rowCount() === 1) {
                $audit = $db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address) VALUES (:user_id,:action,:entity_type,:entity_id,:ip)');
                $audit->execute(['user_id' => Auth::user()['id'], 'action' => 'update_status', 'entity_type' => 'user', 'entity_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
                flash('success', 'User status updated.');
            } else {
                flash('error', 'The user was not found or already has that status.');
            }
        }
        redirect($config, '?page=users');
    }
    $userSearch = trim((string) ($_GET['q'] ?? ''));
    $userSql = 'SELECT id, name, email, status, created_at FROM users';
    $userParams = [];
    if ($userSearch !== '') {
        $userSql .= ' WHERE name LIKE :search_name OR email LIKE :search_email';
        $userPattern = '%' . $userSearch . '%';
        $userParams['search_name'] = $userPattern;
        $userParams['search_email'] = $userPattern;
    }
    $userSql .= ' ORDER BY created_at DESC';
    $userQuery = $db->prepare($userSql);
    $userQuery->execute($userParams);
    $userManagementItems = $userQuery->fetchAll();
}

if ($page === 'community' && $db instanceof PDO && $dbError === null) {
    if (is_post()) {
        Auth::requireLogin($config);
        $action = (string) ($_POST['action'] ?? '');
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $communityErrors[] = 'Your form session expired. Please try again.';
        } elseif ($action === 'create_discussion') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = trim((string) ($_POST['body'] ?? ''));
            if ($title === '' || mb_strlen($title) > 220 || $body === '' || mb_strlen($body) > 5000) {
                $communityErrors[] = 'Topics need a title up to 220 characters and a message up to 5,000 characters.';
            } else {
                $statement = $db->prepare('INSERT INTO discussions (user_id, title, body) VALUES (:user_id, :title, :body)');
                $statement->execute(['user_id' => Auth::user()['id'], 'title' => $title, 'body' => $body]);
                flash('success', 'Your discussion was submitted for moderation.');
                redirect($config, '?page=community');
            }
        } elseif ($action === 'reply') {
            $discussionId = filter_var($_POST['discussion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $body = trim((string) ($_POST['body'] ?? ''));
            $discussionCheck = $db->prepare('SELECT id FROM discussions WHERE id = :id AND status = "approved"');
            $discussionCheck->execute(['id' => $discussionId]);
            if ($discussionId === false || !$discussionCheck->fetchColumn() || $body === '' || mb_strlen($body) > 4000) {
                $communityErrors[] = 'Replies must contain between 1 and 4,000 characters on an approved discussion.';
            } else {
                $statement = $db->prepare('INSERT INTO discussion_replies (discussion_id, user_id, body) VALUES (:discussion_id, :user_id, :body)');
                $statement->execute(['discussion_id' => $discussionId, 'user_id' => Auth::user()['id'], 'body' => $body]);
                flash('success', 'Your reply was submitted for moderation.');
                redirect($config, '?page=community&discussion=' . $discussionId);
            }
        } elseif ($action === 'report') {
            $discussionId = filter_var($_POST['discussion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $replyId = filter_var($_POST['reply_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $reason = trim((string) ($_POST['reason'] ?? ''));
            if (($discussionId === false && $replyId === false) || ($discussionId !== false && $replyId !== false) || $reason === '' || mb_strlen($reason) > 500) {
                $communityErrors[] = 'Select one item to report and briefly explain the concern.';
            } else {
                if ($replyId !== false) {
                    $targetCheck = $db->prepare('SELECT id FROM discussion_replies WHERE id = :id AND status = "approved"');
                    $targetCheck->execute(['id' => $replyId]);
                } else {
                    $targetCheck = $db->prepare('SELECT id FROM discussions WHERE id = :id AND status = "approved"');
                    $targetCheck->execute(['id' => $discussionId]);
                }
                if (!$targetCheck->fetchColumn()) {
                    $communityErrors[] = 'That discussion content is no longer available.';
                } else {
                    $statement = $db->prepare('INSERT INTO discussion_reports (reporter_id, discussion_id, reply_id, reason) VALUES (:reporter_id, :discussion_id, :reply_id, :reason)');
                    $statement->execute(['reporter_id' => Auth::user()['id'], 'discussion_id' => $discussionId === false ? null : $discussionId, 'reply_id' => $replyId === false ? null : $replyId, 'reason' => $reason]);
                    flash('success', 'Thank you. The report was sent to moderators.');
                    redirect($config, '?page=community' . ($discussionId !== false ? '&discussion=' . $discussionId : ''));
                }
            }
        }
    }
    $discussionId = filter_var($_GET['discussion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($discussionId !== false && $discussionId !== null) {
        $discussionQuery = $db->prepare('SELECT d.id, d.title, d.body, d.created_at, u.name FROM discussions d INNER JOIN users u ON u.id = d.user_id WHERE d.id = :id AND d.status = "approved"');
        $discussionQuery->execute(['id' => $discussionId]);
        $discussion = $discussionQuery->fetch() ?: null;
        if ($discussion) {
            $replyQuery = $db->prepare('SELECT r.id, r.body, r.created_at, u.name FROM discussion_replies r INNER JOIN users u ON u.id = r.user_id WHERE r.discussion_id = :discussion_id AND r.status = "approved" ORDER BY r.created_at');
            $replyQuery->execute(['discussion_id' => $discussion['id']]);
            $discussionReplies = $replyQuery->fetchAll();
        }
    } else {
        $discussionQuery = $db->query('SELECT d.id, d.title, d.body, d.created_at, u.name, COUNT(r.id) AS reply_count FROM discussions d INNER JOIN users u ON u.id = d.user_id LEFT JOIN discussion_replies r ON r.discussion_id = d.id AND r.status = "approved" WHERE d.status = "approved" GROUP BY d.id, d.title, d.body, d.created_at, u.name ORDER BY d.created_at DESC');
        $discussions = $discussionQuery->fetchAll();
    }
}

if ($page === 'challenges' && $db instanceof PDO && $dbError === null) {
    Auth::requireLogin($config);
    if (is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        $challengeId = filter_var($_POST['challenge_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null) || $challengeId === false || !in_array($action, ['start_challenge', 'complete_challenge'], true)) {
            $challengeErrors[] = 'The challenge request could not be validated.';
        } else {
            $challengeCheck = $db->prepare('SELECT id FROM challenges WHERE id = :id AND published = 1');
            $challengeCheck->execute(['id' => $challengeId]);
            if (!$challengeCheck->fetchColumn()) {
                $challengeErrors[] = 'That challenge is no longer available.';
            } elseif ($action === 'start_challenge') {
                $statement = $db->prepare('INSERT INTO user_challenges (user_id, challenge_id) VALUES (:user_id, :challenge_id) ON DUPLICATE KEY UPDATE user_id = user_id');
                $statement->execute(['user_id' => Auth::user()['id'], 'challenge_id' => $challengeId]);
                flash('success', 'Challenge added to your progress.');
                redirect($config, '?page=challenges');
            } else {
                $statement = $db->prepare('UPDATE user_challenges SET completed_at = COALESCE(completed_at, NOW()) WHERE user_id = :user_id AND challenge_id = :challenge_id');
                $statement->execute(['user_id' => Auth::user()['id'], 'challenge_id' => $challengeId]);
                flash('success', 'Challenge marked as complete.');
                redirect($config, '?page=challenges');
            }
        }
    }
    try {
        $challengeQuery = $db->prepare('SELECT c.id, c.title, c.description, c.action_text, c.duration_days, uc.started_at, uc.completed_at FROM challenges c LEFT JOIN user_challenges uc ON uc.challenge_id = c.id AND uc.user_id = :user_id WHERE c.published = 1 ORDER BY c.created_at DESC');
        $challengeQuery->execute(['user_id' => Auth::user()['id']]);
        $challenges = $challengeQuery->fetchAll();
    } catch (PDOException $exception) {
        $challengeErrors[] = 'Challenges are unavailable until the updated database schema is imported.';
    }
}

if ($page === 'planner' && $db instanceof PDO && $dbError === null) {
    Auth::requireLogin($config);
    if (is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        $entryId = filter_var($_POST['entry_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $plannerErrors[] = 'Your form session expired. Please try again.';
        } elseif ($action === 'add_planner_entry') {
            $plannedFor = trim((string) ($_POST['planned_for'] ?? ''));
            $task = trim((string) ($_POST['task'] ?? ''));
            $minutes = filter_var($_POST['minutes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 1440]]);
            $date = DateTime::createFromFormat('Y-m-d', $plannedFor);
            if (!$date || $date->format('Y-m-d') !== $plannedFor || $task === '' || mb_strlen($task) > 220 || $minutes === false) {
                $plannerErrors[] = 'Enter a valid date, task, and study time between 5 and 1,440 minutes.';
            } else {
                $statement = $db->prepare('INSERT INTO learning_planner_entries (user_id, planned_for, task, minutes) VALUES (:user_id, :planned_for, :task, :minutes)');
                $statement->execute(['user_id' => Auth::user()['id'], 'planned_for' => $plannedFor, 'task' => $task, 'minutes' => $minutes]);
                flash('success', 'Learning plan entry added.');
                redirect($config, '?page=planner');
            }
        } elseif (in_array($action, ['complete_planner_entry', 'delete_planner_entry'], true) && $entryId !== false) {
            if ($action === 'complete_planner_entry') {
                $statement = $db->prepare('UPDATE learning_planner_entries SET completed_at = COALESCE(completed_at, NOW()) WHERE id = :id AND user_id = :user_id');
            } else {
                $statement = $db->prepare('DELETE FROM learning_planner_entries WHERE id = :id AND user_id = :user_id');
            }
            $statement->execute(['id' => $entryId, 'user_id' => Auth::user()['id']]);
            flash('success', $action === 'complete_planner_entry' ? 'Learning task marked complete.' : 'Learning task removed.');
            redirect($config, '?page=planner');
        } else {
            $plannerErrors[] = 'That planner request could not be validated.';
        }
    }
    try {
        $plannerQuery = $db->prepare('SELECT id, planned_for, task, minutes, completed_at FROM learning_planner_entries WHERE user_id = :user_id AND planned_for >= CURDATE() - INTERVAL 1 DAY ORDER BY planned_for, completed_at IS NOT NULL, id');
        $plannerQuery->execute(['user_id' => Auth::user()['id']]);
        $plannerEntries = $plannerQuery->fetchAll();
    } catch (PDOException $exception) {
        $plannerErrors[] = 'The learning planner is unavailable until the updated database schema is imported.';
    }
}

if ($page === 'journal') {
    Auth::requireLogin($config);
    if ($db instanceof PDO && $dbError === null && is_post()) {
        $action = (string) ($_POST['action'] ?? '');
        $entryId = filter_var($_POST['entry_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $journalErrors[] = 'Your form session expired. Please try again.';
        } elseif ($action === 'add_journal_entry') {
            $entryDate = trim((string) ($_POST['entry_date'] ?? ''));
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = trim((string) ($_POST['body'] ?? ''));
            $date = DateTime::createFromFormat('Y-m-d', $entryDate);
            if (!$date || $date->format('Y-m-d') !== $entryDate || $title === '' || mb_strlen($title) > 220 || $body === '' || mb_strlen($body) > 10000) {
                $journalErrors[] = 'Enter a valid date, title, and reflection up to 10,000 characters.';
            } else {
                $statement = $db->prepare('INSERT INTO development_journal_entries (user_id, entry_date, title, body) VALUES (:user_id, :entry_date, :title, :body)');
                $statement->execute(['user_id' => Auth::user()['id'], 'entry_date' => $entryDate, 'title' => $title, 'body' => $body]);
                flash('success', 'Journal entry saved.');
                redirect($config, '?page=journal');
            }
        } elseif ($action === 'delete_journal_entry' && $entryId !== false) {
            $statement = $db->prepare('DELETE FROM development_journal_entries WHERE id = :id AND user_id = :user_id');
            $statement->execute(['id' => $entryId, 'user_id' => Auth::user()['id']]);
            flash('success', 'Journal entry removed.');
            redirect($config, '?page=journal');
        } else {
            $journalErrors[] = 'That journal request could not be validated.';
        }
    }
    try {
        $journalQuery = $db->prepare('SELECT id, entry_date, title, body, created_at FROM development_journal_entries WHERE user_id = :user_id ORDER BY entry_date DESC, id DESC LIMIT 50');
        $journalQuery->execute(['user_id' => Auth::user()['id']]);
        $journalEntries = $journalQuery->fetchAll();
    } catch (PDOException $exception) {
        $journalErrors[] = 'The journal is unavailable until the updated database schema is imported.';
    }
}

if ($page === 'transition') {
    Auth::requireLogin($config);
    if ($db instanceof PDO && $dbError === null) {
        if (is_post()) {
            $action = (string) ($_POST['action'] ?? '');
            $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!CSRF::verify($_POST['_csrf'] ?? null)) {
                $transitionErrors[] = 'Your form session expired. Please try again.';
            } elseif ($action === 'add_transition_plan') {
                $currentPosition = trim((string) ($_POST['current_position'] ?? ''));
                $targetCareerId = filter_var($_POST['target_career_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $targetDate = trim((string) ($_POST['target_date'] ?? ''));
                $reason = trim((string) ($_POST['reason'] ?? ''));
                $date = $targetDate === '' ? null : DateTime::createFromFormat('Y-m-d', $targetDate);
                if ($currentPosition === '' || mb_strlen($currentPosition) > 220 || $targetCareerId === false || $reason === '' || mb_strlen($reason) > 5000 || ($targetDate !== '' && (!$date || $date->format('Y-m-d') !== $targetDate))) {
                    $transitionErrors[] = 'Enter your current role, a target career, a valid target date, and a reason up to 5,000 characters.';
                } else {
                    $careerCheck = $db->prepare('SELECT id FROM careers WHERE id = :id LIMIT 1');
                    $careerCheck->execute(['id' => $targetCareerId]);
                    if (!$careerCheck->fetchColumn()) {
                        $transitionErrors[] = 'Select an available target career.';
                    } else {
                        $statement = $db->prepare('INSERT INTO career_transition_plans (user_id, current_position, target_career_id, target_date, reason) VALUES (:user_id, :current_position, :target_career_id, :target_date, :reason)');
                        $statement->execute(['user_id' => Auth::user()['id'], 'current_position' => $currentPosition, 'target_career_id' => $targetCareerId, 'target_date' => $targetDate === '' ? null : $targetDate, 'reason' => $reason]);
                        flash('success', 'Career transition plan saved.');
                        redirect($config, '?page=transition');
                    }
                }
            } elseif (in_array($action, ['complete_transition_plan', 'delete_transition_plan'], true) && $planId !== false) {
                if ($action === 'complete_transition_plan') {
                    $statement = $db->prepare('UPDATE career_transition_plans SET status = "completed" WHERE id = :id AND user_id = :user_id AND status = "active"');
                } else {
                    $statement = $db->prepare('DELETE FROM career_transition_plans WHERE id = :id AND user_id = :user_id');
                }
                $statement->execute(['id' => $planId, 'user_id' => Auth::user()['id']]);
                flash('success', $action === 'complete_transition_plan' ? 'Transition plan marked complete.' : 'Transition plan removed.');
                redirect($config, '?page=transition');
            } else {
                $transitionErrors[] = 'That transition request could not be validated.';
            }
        }
        try {
            $careerQuery = $db->query('SELECT id, title FROM careers ORDER BY title');
            $transitionCareers = $careerQuery->fetchAll();
            $planQuery = $db->prepare('SELECT p.id, p.current_position, p.target_date, p.reason, p.status, p.created_at, c.title AS target_title, c.slug AS target_slug FROM career_transition_plans p INNER JOIN careers c ON c.id = p.target_career_id WHERE p.user_id = :user_id ORDER BY p.status ASC, p.target_date IS NULL, p.target_date ASC, p.created_at DESC');
            $planQuery->execute(['user_id' => Auth::user()['id']]);
            $transitionPlans = $planQuery->fetchAll();
        } catch (PDOException $exception) {
            $transitionErrors[] = 'The transition planner is unavailable until the updated database schema is imported.';
        }
    }
}

if ($page === 'showcase') {
    if ($db instanceof PDO && $dbError === null && is_post()) {
        Auth::requireLogin($config);
        $action = (string) ($_POST['action'] ?? '');
        $showcaseId = filter_var($_POST['showcase_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $showcaseErrors[] = 'Your form session expired. Please try again.';
        } elseif ($action === 'add_showcase') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $projectUrl = trim((string) ($_POST['project_url'] ?? ''));
            $skills = trim((string) ($_POST['skills'] ?? ''));
            if ($title === '' || mb_strlen($title) > 220 || $description === '' || mb_strlen($description) > 5000 || ($projectUrl !== '' && (!filter_var($projectUrl, FILTER_VALIDATE_URL) || mb_strlen($projectUrl) > 500)) || mb_strlen($skills) > 500) {
                $showcaseErrors[] = 'Enter a title and description. URLs must be valid, and skills must be 500 characters or fewer.';
            } else {
                $statement = $db->prepare('INSERT INTO project_showcases (user_id, title, description, project_url, skills) VALUES (:user_id, :title, :description, :project_url, :skills)');
                $statement->execute(['user_id' => Auth::user()['id'], 'title' => $title, 'description' => $description, 'project_url' => $projectUrl === '' ? null : $projectUrl, 'skills' => $skills === '' ? null : $skills]);
                flash('success', 'Your project was submitted for moderation.');
                redirect($config, '?page=showcase');
            }
        } elseif ($action === 'delete_showcase' && $showcaseId !== false) {
            $statement = $db->prepare('DELETE FROM project_showcases WHERE id = :id AND user_id = :user_id');
            $statement->execute(['id' => $showcaseId, 'user_id' => Auth::user()['id']]);
            flash('success', 'Project removed.');
            redirect($config, '?page=showcase');
        } else {
            $showcaseErrors[] = 'That showcase request could not be validated.';
        }
    }
    try {
        $showcaseQuery = $db->prepare('SELECT ps.id, ps.user_id, ps.title, ps.description, ps.project_url, ps.skills, ps.status, ps.created_at, u.name FROM project_showcases ps INNER JOIN users u ON u.id = ps.user_id WHERE ps.status = "approved" OR ps.user_id = :user_id ORDER BY ps.created_at DESC');
        $showcaseQuery->execute(['user_id' => Auth::user()['id'] ?? 0]);
        $showcases = $showcaseQuery->fetchAll();
    } catch (PDOException $exception) {
        $showcaseErrors[] = 'Project showcases are unavailable until the updated database schema is imported.';
    }
}

if (in_array($page, ['home', 'article', 'contact'], true) && $db instanceof PDO && $dbError === null && is_post()) {
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $engagementErrors[] = 'Your form session expired. Please try again.';
    } elseif ($page === 'article' && ($_POST['action'] ?? '') === 'bookmark') {
        Auth::requireLogin($config);
        $articleId = filter_var($_POST['article_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($articleId === false) {
            $engagementErrors[] = 'That guide could not be identified.';
        } else {
            $articleCheck = $db->prepare('SELECT id, slug FROM articles WHERE id = :id AND slug = :slug AND status = "published" LIMIT 1');
            $articleCheck->execute(['id' => $articleId, 'slug' => trim((string) ($_GET['slug'] ?? ''))]);
            $articleRecord = $articleCheck->fetch();
            if (!$articleRecord) {
                $engagementErrors[] = 'That guide is no longer available.';
            } else {
                $bookmarkCheck = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = :user_id AND article_id = :article_id');
                $bookmarkCheck->execute(['user_id' => Auth::user()['id'], 'article_id' => $articleId]);
                if ($bookmarkCheck->fetchColumn()) {
                    $bookmarkDelete = $db->prepare('DELETE FROM bookmarks WHERE user_id = :user_id AND article_id = :article_id');
                    $bookmarkDelete->execute(['user_id' => Auth::user()['id'], 'article_id' => $articleId]);
                    flash('success', 'Guide removed from your bookmarks.');
                } else {
                    $bookmarkInsert = $db->prepare('INSERT INTO bookmarks (user_id, article_id) VALUES (:user_id, :article_id)');
                    $bookmarkInsert->execute(['user_id' => Auth::user()['id'], 'article_id' => $articleId]);
                    flash('success', 'Guide saved to your bookmarks.');
                }
                redirect($config, '?page=article&slug=' . rawurlencode($articleRecord['slug']));
            }
        }
    } elseif ($page === 'article' && ($_POST['action'] ?? '') === 'comment') {
        Auth::requireLogin($config);
        $commentBody = trim((string) ($_POST['body'] ?? ''));
        $articleId = filter_var($_POST['article_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $articleValid = false;
        if ($articleId !== false) {
            $articleCheck = $db->prepare('SELECT id FROM articles WHERE id = :id AND slug = :slug AND status = "published" LIMIT 1');
            $articleCheck->execute(['id' => $articleId, 'slug' => trim((string) ($_GET['slug'] ?? ''))]);
            $articleValid = (bool) $articleCheck->fetchColumn();
        }
        if ($commentBody === '' || mb_strlen($commentBody) > 4000 || $articleId === false || !$articleValid) {
            $engagementErrors[] = 'Comments must contain between 1 and 4,000 characters.';
        } else {
            $commentInsert = $db->prepare('INSERT INTO comments (user_id, article_id, body) VALUES (:user_id, :article_id, :body)');
            $commentInsert->execute(['user_id' => Auth::user()['id'], 'article_id' => $articleId, 'body' => $commentBody]);
            flash('success', 'Your comment was submitted for moderation.');
            redirect($config, '?page=article&slug=' . rawurlencode((string) ($_GET['slug'] ?? '')));
        }
    } elseif ($page === 'contact') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($subject) < 1 || mb_strlen($subject) > 220 || mb_strlen($message) < 1 || mb_strlen($message) > 5000) {
            $engagementErrors[] = 'Complete your name, valid email, subject, and message.';
        } else {
            $contactInsert = $db->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (:name,:email,:subject,:message)');
            $contactInsert->execute(['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]);
            flash('success', 'Your message has been received.');
            redirect($config, '?page=contact');
        }
    } elseif ($page === 'home' && ($_POST['action'] ?? '') === 'newsletter') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $engagementErrors[] = 'Enter a valid email address.';
        } else {
            $newsletterInsert = $db->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)');
            $newsletterInsert->execute(['email' => $email]);
            flash('success', 'You are subscribed to CareerPath updates.');
            redirect($config, '');
        }
    }
}

if (in_array($page, ['learning', 'path'], true) && $db instanceof PDO && $dbError === null) {
    if ($page === 'path' && is_post()) {
        Auth::requireLogin($config);
        $action = (string) ($_POST['action'] ?? '');
        $pathId = filter_var($_POST['path_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $lessonId = filter_var($_POST['lesson_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!CSRF::verify($_POST['_csrf'] ?? null) || ($action === 'enroll' && $pathId === false) || ($action === 'complete' && $lessonId === false)) {
            flash('error', 'Your request could not be validated. Please try again.');
        } elseif ($action === 'enroll') {
            $pathCheck = $db->prepare('SELECT id FROM learning_paths WHERE id = :id AND published = 1');
            $pathCheck->execute(['id' => $pathId]);
            if (!$pathCheck->fetchColumn()) {
                flash('error', 'That learning path is not available.');
            } else {
                $enroll = $db->prepare('INSERT IGNORE INTO enrollments (user_id, learning_path_id) VALUES (:user_id, :path_id)');
                $enroll->execute(['user_id' => Auth::user()['id'], 'path_id' => $pathId]);
                flash('success', 'You are enrolled in this learning path.');
            }

        } elseif ($action === 'complete') {
            $lessonCheck = $db->prepare(
                'SELECT l.id FROM lessons l
                 INNER JOIN learning_modules lm ON lm.id = l.module_id
                 INNER JOIN learning_paths lp ON lp.id = lm.learning_path_id
                 INNER JOIN enrollments e ON e.learning_path_id = lp.id AND e.user_id = :user_id
                 WHERE l.id = :lesson_id AND lp.published = 1'
            );
            $lessonCheck->execute(['user_id' => Auth::user()['id'], 'lesson_id' => $lessonId]);
            if (!$lessonCheck->fetchColumn()) {
                flash('error', 'Enroll in the learning path before completing lessons.');
            } else {
                $complete = $db->prepare('INSERT IGNORE INTO lesson_progress (user_id, lesson_id) VALUES (:user_id, :lesson_id)');
                $complete->execute(['user_id' => Auth::user()['id'], 'lesson_id' => $lessonId]);
                flash('success', 'Lesson marked complete.');
            }
        }
        redirect($config, '?page=path&slug=' . rawurlencode((string) ($_GET['slug'] ?? '')));
    }

    if ($page === 'learning') {
        $pathsQuery = $db->query('SELECT lp.id, lp.title, lp.slug, lp.description, lp.level, COUNT(l.id) AS lesson_count FROM learning_paths lp LEFT JOIN learning_modules lm ON lm.learning_path_id = lp.id LEFT JOIN lessons l ON l.module_id = lm.id WHERE lp.published = 1 GROUP BY lp.id ORDER BY lp.title');
        $learningPaths = $pathsQuery->fetchAll();
    } else {
        $pathQuery = $db->prepare('SELECT id, title, slug, description, level FROM learning_paths WHERE slug = :slug AND published = 1 LIMIT 1');
        $pathQuery->execute(['slug' => trim((string) ($_GET['slug'] ?? ''))]);
        $learningPath = $pathQuery->fetch() ?: null;
        if ($learningPath) {
            $modulesQuery = $db->prepare(
                'SELECT lm.id AS module_id, lm.title AS module_title, lm.position,
                    l.id AS lesson_id, l.title AS lesson_title, l.content AS lesson_content, l.position AS lesson_position,
                    CASE WHEN prog.lesson_id IS NULL THEN 0 ELSE 1 END AS completed
                 FROM learning_modules lm
                 LEFT JOIN lessons l ON l.module_id = lm.id
                 LEFT JOIN lesson_progress prog ON prog.lesson_id = l.id AND prog.user_id = :user_id
                 WHERE lm.learning_path_id = :path_id
                 ORDER BY lm.position, l.position'
            );
            $modulesQuery->execute(['user_id' => Auth::user()['id'] ?? 0, 'path_id' => $learningPath['id']]);
            foreach ($modulesQuery->fetchAll() as $row) {
                $moduleId = (int) $row['module_id'];
                if (!isset($learningModules[$moduleId])) {
                    $learningModules[$moduleId] = ['title' => $row['module_title'], 'lessons' => []];
                }
                if ($row['lesson_id'] !== null) {
                    $learningModules[$moduleId]['lessons'][] = $row;
                    $learningTotal++;
                    $learningCompleted += (int) $row['completed'];
                }
            }
            if (Auth::check()) {
                $enrollmentQuery = $db->prepare('SELECT 1 FROM enrollments WHERE user_id = :user_id AND learning_path_id = :path_id');
                $enrollmentQuery->execute(['user_id' => Auth::user()['id'], 'path_id' => $learningPath['id']]);
                $learningEnrolled = (bool) $enrollmentQuery->fetchColumn();
            }
        }
    }
}

if (in_array($page, ['articles', 'article', 'resources'], true) && $db instanceof PDO && $dbError === null) {
    if ($page === 'articles') {
        $search = trim((string) ($_GET['q'] ?? ''));
        if ($search !== '') {
            $articleQuery = $db->prepare('SELECT id, title, slug, excerpt, published_at FROM articles WHERE status = "published" AND (title LIKE :search_title OR excerpt LIKE :search_excerpt OR body LIKE :search_body) ORDER BY published_at DESC, id DESC');
            $searchPattern = '%' . $search . '%';
            $articleQuery->execute(['search_title' => $searchPattern, 'search_excerpt' => $searchPattern, 'search_body' => $searchPattern]);
        } else {
            $articleQuery = $db->query('SELECT id, title, slug, excerpt, published_at FROM articles WHERE status = "published" ORDER BY published_at DESC, id DESC');
        }
        $articles = $articleQuery->fetchAll();
    } elseif ($page === 'article') {
        $articleQuery = $db->prepare('SELECT id, title, slug, excerpt, body, published_at FROM articles WHERE slug = :slug AND status = "published" LIMIT 1');
        $articleQuery->execute(['slug' => trim((string) ($_GET['slug'] ?? ''))]);
        $article = $articleQuery->fetch() ?: null;
        if ($article) {
            $commentQuery = $db->prepare('SELECT c.body, c.created_at, u.name FROM comments c INNER JOIN users u ON u.id = c.user_id WHERE c.article_id = :article_id AND c.status = "approved" ORDER BY c.created_at DESC');
            $commentQuery->execute(['article_id' => $article['id']]);
            $articleComments = $commentQuery->fetchAll();
            if (Auth::check()) {
                $bookmarkQuery = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = :user_id AND article_id = :article_id');
                $bookmarkQuery->execute(['user_id' => Auth::user()['id'], 'article_id' => $article['id']]);
                $articleBookmarked = (bool) $bookmarkQuery->fetchColumn();
            }
        }
    } else {
        $search = trim((string) ($_GET['q'] ?? ''));
        if ($search !== '') {
            $resourceQuery = $db->prepare('SELECT id, title, description, url, category, level, reviewed_at FROM resources WHERE published = 1 AND (title LIKE :search_title OR description LIKE :search_description OR category LIKE :search_category) ORDER BY title');
            $searchPattern = '%' . $search . '%';
            $resourceQuery->execute(['search_title' => $searchPattern, 'search_description' => $searchPattern, 'search_category' => $searchPattern]);
        } else {
            $resourceQuery = $db->query('SELECT id, title, description, url, category, level, reviewed_at FROM resources WHERE published = 1 ORDER BY title');
        }
        $resources = $resourceQuery->fetchAll();
    }
}

if ($page === 'forgot-password' && is_post() && $db instanceof PDO && $dbError === null) {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $resetErrors[] = 'Your form session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resetErrors[] = 'Enter a valid email address.';
    } else {
        $userQuery = $db->prepare('SELECT id FROM users WHERE email = :email AND status = "active" LIMIT 1');
        $userQuery->execute(['email' => $email]);
        $userId = $userQuery->fetchColumn();
        if ($userId !== false) {
            $token = bin2hex(random_bytes(32));
            try {
                $tokenInsert = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
                $tokenInsert->execute(['user_id' => $userId, 'token_hash' => hash('sha256', $token)]);
                $resetLink = url($config, '?page=reset-password&token=' . rawurlencode($token));
                if (($config['environment'] ?? 'production') !== 'development') {
                    mail($email, 'Reset your CareerPath password', "Use this link within one hour:\n\n{$resetLink}");
                    $resetLink = null;
                }
            } catch (PDOException $exception) {
                $resetErrors[] = 'Password recovery is not available until the latest database schema is imported.';
            }
        }
        flash('success', 'If an active account matches that email, password reset instructions are available.');
    }
}

if ($page === 'reset-password' && $db instanceof PDO && $dbError === null) {
    $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
    $tokenHash = hash('sha256', $token);
    try {
    $resetQuery = $db->prepare(
        'SELECT prt.id, prt.user_id, u.email
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.id = prt.user_id
         WHERE prt.token_hash = :token_hash AND prt.used_at IS NULL
           AND prt.expires_at > NOW() AND u.status = "active"
         LIMIT 1'
    );
    $resetQuery->execute(['token_hash' => $tokenHash]);
    $resetRecord = $resetQuery->fetch() ?: null;
    } catch (PDOException $exception) {
        $resetRecord = null;
        $resetErrors[] = 'Password recovery is not available until the latest database schema is imported.';
    }
    if (is_post()) {
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        if (!CSRF::verify($_POST['_csrf'] ?? null)) {
            $resetErrors[] = 'Your form session expired. Please try again.';
        } elseif (!$resetRecord) {
            $resetErrors[] = 'That reset link is invalid or has expired.';
        } elseif (strlen($password) < 8 || !hash_equals($password, $confirmation)) {
            $resetErrors[] = 'The password must contain at least 8 characters and match its confirmation.';
        } else {
            $db->beginTransaction();
            try {
                $passwordUpdate = $db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND status = "active"');
                $passwordUpdate->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $resetRecord['user_id']]);
                $tokenUse = $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
                $tokenUse->execute(['id' => $resetRecord['id']]);
                $db->commit();
                flash('success', 'Your password was reset. You can now sign in.');
                redirect($config, '?page=login');
            } catch (PDOException $exception) {
                $db->rollBack();
                $resetErrors[] = 'Your password could not be reset. Please request a new link.';
            }
        }
    }
}

if ($page === 'assessment' && $db instanceof PDO && $dbError === null) {
    $assessmentQuery = $db->query('SELECT id, title, description FROM assessments WHERE published = 1 ORDER BY id LIMIT 1');
    $assessment = $assessmentQuery->fetch() ?: null;
    if ($assessment) {
        $questionQuery = $db->prepare(
            'SELECT q.id AS question_id, q.question, q.position, o.id AS option_id, o.label
             FROM assessment_questions q
             INNER JOIN assessment_options o ON o.question_id = q.id
             WHERE q.assessment_id = :assessment_id
             ORDER BY q.position, o.id'
        );
        $questionQuery->execute(['assessment_id' => $assessment['id']]);
        foreach ($questionQuery->fetchAll() as $row) {
            $questionId = (int) $row['question_id'];
            if (!isset($assessmentQuestions[$questionId])) {
                $assessmentQuestions[$questionId] = ['question' => $row['question'], 'position' => $row['position'], 'options' => []];
            }
            $assessmentQuestions[$questionId]['options'][] = ['id' => (int) $row['option_id'], 'label' => $row['label']];
        }
    }

    if (is_post() && $assessment) {
        $submitted = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
        $questionIds = array_keys($assessmentQuestions);
        $selectedIds = [];
        foreach ($questionIds as $questionId) {
            $optionId = filter_var($submitted[$questionId] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($optionId === false) {
                $assessmentErrors[] = 'Answer every question before viewing recommendations.';
                continue;
            }
            $selectedIds[] = $optionId;
        }

        if (!$assessmentErrors) {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
            $optionQuery = $db->prepare("SELECT o.id, o.question_id, o.career_id, o.weight, c.title, c.slug, c.summary FROM assessment_options o INNER JOIN careers c ON c.id = o.career_id WHERE o.id IN ($placeholders)");
            $optionQuery->execute($selectedIds);
            $scores = [];
            $careerDetails = [];
            $validatedAnswers = 0;
            foreach ($optionQuery->fetchAll() as $option) {
                $questionId = (int) $option['question_id'];
                if ((int) ($submitted[$questionId] ?? 0) !== (int) $option['id']) {
                    continue;
                }
                $validatedAnswers++;
                $careerId = (int) $option['career_id'];
                $scores[$careerId] = ($scores[$careerId] ?? 0) + (float) $option['weight'];
                $careerDetails[$careerId] = ['title' => $option['title'], 'slug' => $option['slug'], 'summary' => $option['summary']];
            }
            if ($validatedAnswers !== count($questionIds)) {
                $assessmentErrors[] = 'One or more answers were invalid. Please submit the assessment again.';
            } else {
                arsort($scores, SORT_NUMERIC);
                $recommendations = [];
                foreach (array_slice(array_keys($scores), 0, 3) as $careerId) {
                    $recommendations[] = ['career_id' => $careerId, 'score' => $scores[$careerId], ...$careerDetails[$careerId]];
                }
                $assessmentResult = ['recommendations' => $recommendations, 'completed_at' => date(DATE_ATOM)];
                if (Auth::check()) {
                    $resultInsert = $db->prepare('INSERT INTO assessment_results (user_id, assessment_id, result_json) VALUES (:user_id, :assessment_id, :result_json)');
                    $resultInsert->execute([
                        'user_id' => Auth::user()['id'],
                        'assessment_id' => $assessment['id'],
                        'result_json' => json_encode($assessmentResult, JSON_THROW_ON_ERROR),
                    ]);
                }
            }
        }
    }
}

if ($page === 'verify-email' && $db instanceof PDO && $dbError === null) {
    $token = trim((string) ($_GET['token'] ?? ''));
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $verificationErrors[] = 'That verification link is invalid or incomplete.';
    } else {
        try {
            $verificationQuery = $db->prepare(
                'SELECT evt.id, evt.user_id, u.name, u.email
                 FROM email_verification_tokens evt
                 INNER JOIN users u ON u.id = evt.user_id
                 WHERE evt.token_hash = :token_hash AND evt.used_at IS NULL AND evt.expires_at > NOW() AND u.status = "active"
                 LIMIT 1'
            );
            $verificationQuery->execute(['token_hash' => hash('sha256', $token)]);
            $verification = $verificationQuery->fetch();
            if (!$verification) {
                $verificationErrors[] = 'That verification link is invalid or has expired.';
            } else {
                $db->beginTransaction();
                $markUser = $db->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = :id');
                $markUser->execute(['id' => $verification['user_id']]);
                $markToken = $db->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = :id AND used_at IS NULL');
                $markToken->execute(['id' => $verification['id']]);
                $db->commit();
                $verificationMessage = 'Your email address is verified. You can now sign in.';
            }
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $verificationErrors[] = 'Email verification is unavailable until the updated database schema is imported.';
        }
    }
}

if ($page === 'register' && is_post() && $db instanceof PDO && $dbError === null) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $formErrors[] = 'Your form session expired. Please try again.';
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $formErrors[] = 'Enter a name between 2 and 120 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $formErrors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $formErrors[] = 'Your password must contain at least 8 characters.';
    }
    if (!hash_equals($password, $confirmation)) {
        $formErrors[] = 'The password confirmation does not match.';
    }

    if (!$formErrors) {
        $existing = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetch()) {
            $formErrors[] = 'An account already exists for that email address.';
        } else {
            $db->beginTransaction();
            try {
                $insert = $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $userId = (int) $db->lastInsertId();
                $role = $db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
                $role->execute(['name' => 'registered_user']);
                $roleId = $role->fetchColumn();
                if ($roleId === false) {
                    throw new RuntimeException('The registered_user role is missing from the database seed.');
                }
                $assignment = $db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
                $assignment->execute(['user_id' => $userId, 'role_id' => $roleId]);
                $verificationToken = bin2hex(random_bytes(32));
                $verificationInsert = $db->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
                $verificationInsert->execute(['user_id' => $userId, 'token_hash' => hash('sha256', $verificationToken)]);
                $db->commit();
                $verificationLink = url($config, '?page=verify-email&token=' . rawurlencode($verificationToken));
                if (($config['environment'] ?? 'production') !== 'development') {
                    $subject = 'Verify your CareerPath email address';
                    $message = "Please verify your email address by visiting:\n\n" . $verificationLink;
                    if (mail($email, $subject, $message, 'Content-Type: text/plain; charset=UTF-8')) {
                        $verificationMessage = 'Your account was created. Check your email to verify your address before signing in.';
                    } else {
                        $verificationMessage = 'Your account was created, but the verification email could not be sent. Configure mail delivery before using this account.';
                    }
                } else {
                    $verificationMessage = 'Your account was created. Use the development verification link below before signing in.';
                }
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $exception;
            }
        }
    }
}

if ($page === 'login' && is_post() && $db instanceof PDO && $dbError === null) {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!CSRF::verify($_POST['_csrf'] ?? null)) {
        $formErrors[] = 'Your form session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $formErrors[] = 'Enter your email address and password.';
    } else {
        $verificationSupported = true;
        try {
            $query = $db->prepare('SELECT id, name, email, password_hash, email_verified_at FROM users WHERE email = :email AND status = "active" LIMIT 1');
            $query->execute(['email' => $email]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '42S22' || !str_contains($exception->getMessage(), 'email_verified_at')) {
                throw $exception;
            }
            $verificationSupported = false;
            $query = $db->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email AND status = "active" LIMIT 1');
            $query->execute(['email' => $email]);
        }
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $formErrors[] = 'The email or password is incorrect.';
        } elseif ($verificationSupported && $user['email_verified_at'] === null) {
            $formErrors[] = 'Verify your email address before signing in. Check your inbox for the verification link.';
        } else {
            Auth::login($user);
            redirect($config, '?page=dashboard');
        }
    }
}

$pageTitles = [
    'home' => 'Build a career you can grow into',
    'careers' => 'Explore careers',
    'learning' => 'Learning paths',
    'about' => 'About CareerPath',
    'register' => 'Create your account',
    'login' => 'Sign in',
    'forgot-password' => 'Reset your password',
    'reset-password' => 'Choose a new password',
    'verify-email' => 'Verify your email address',
    'dashboard' => 'Your dashboard',
    'profile' => 'Account settings',
    'career' => $career['title'] ?? 'Career profile',
    'path' => $learningPath['title'] ?? 'Learning path',
    'articles' => 'Guides and tutorials',
    'article' => $article['title'] ?? 'Article',
    'resources' => 'Resource library',
    'assessment' => 'Career assessment',
    'compare' => 'Compare careers',
    'admin' => 'Administrator dashboard',
    'admin-path' => 'Manage learning content',
    'users' => 'User management',
    'moderation' => 'Moderation',
    'community' => 'Career community',
    'challenges' => 'Learning challenges',
    'planner' => 'Daily learning planner',
    'journal' => 'Personal development journal',
    'transition' => 'Career transition planner',
    'showcase' => 'Project showcase',
    'settings' => 'Site settings',
    '404' => 'Page not found',
];
$title = $pageTitles[$page] ?? $siteSettings['site_name'];
$description = $siteSettings['site_description'];
$canonical = url($config, $page === 'home' ? '' : '?page=' . rawurlencode($page));
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => $page === 'article' && $article ? 'Article' : 'WebSite',
    'name' => $title . ' | ' . $siteSettings['site_name'],
    'description' => $description,
    'url' => $canonical,
];
if ($page === 'career' && $career) {
    $description = (string) $career['summary'];
    $canonical = url($config, '?page=career&slug=' . rawurlencode((string) $career['slug']));
} elseif ($page === 'article' && $article) {
    $description = (string) $article['excerpt'];
    $canonical = url($config, '?page=article&slug=' . rawurlencode((string) $article['slug']));
} elseif ($page === 'path' && $learningPath) {
    $description = (string) $learningPath['description'];
    $canonical = url($config, '?page=path&slug=' . rawurlencode((string) $learningPath['slug']));
}
if ($page === 'career' && $career) {
    $structuredData['description'] = $description;
    $structuredData['url'] = $canonical;
} elseif ($page === 'article' && $article) {
    $structuredData['description'] = $description;
    $structuredData['url'] = $canonical;
} elseif ($page === 'path' && $learningPath) {
    $structuredData['description'] = $description;
    $structuredData['url'] = $canonical;
}
if ($page === 'article' && $article) {
    $structuredData['headline'] = (string) $article['title'];
    $structuredData['datePublished'] = (string) $article['published_at'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:type" content="<?= $page === 'article' ? 'article' : 'website' ?>">
    <meta property="og:title" content="<?= e($title) ?> | <?= e($config['app_name']) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta name="twitter:card" content="summary">
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>
    <title><?= e($title) ?> | <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset($config, 'css/app.css')) ?>">
    <script defer src="<?= e(asset($config, 'js/app.js')) ?>"></script>
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= e(url($config)) ?>">Career<span>Path</span></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">Menu</button>
        <nav id="primary-navigation" aria-label="Primary navigation">
            <details class="nav-group">
                <summary>Explore</summary>
                <div class="nav-menu">
                    <a href="<?= e(url($config, '?page=careers')) ?>">Explore careers</a>
                    <a href="<?= e(url($config, '?page=assessment')) ?>">Career assessment</a>
                    <a href="<?= e(url($config, '?page=compare')) ?>">Compare careers</a>
                    <a href="<?= e(url($config, '?page=transition')) ?>">Transition planner</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>Learn</summary>
                <div class="nav-menu">
                    <a href="<?= e(url($config, '?page=learning')) ?>">Learning paths</a>
                    <a href="<?= e(url($config, '?page=articles')) ?>">Guides</a>
                    <a href="<?= e(url($config, '?page=resources')) ?>">Resources</a>
                    <a href="<?= e(url($config, '?page=challenges')) ?>">Challenges</a>
                </div>
            </details>
            <details class="nav-group">
                <summary>My progress</summary>
                <div class="nav-menu">
                    <a href="<?= e(url($config, '?page=planner')) ?>">Planner</a>
                    <a href="<?= e(url($config, '?page=journal')) ?>">Journal</a>
                    <a href="<?= e(url($config, '?page=showcase')) ?>">Showcase</a>
                </div>
            </details>
            <a href="<?= e(url($config, '?page=community')) ?>">Community</a>
            <a href="<?= e(url($config, '?page=about')) ?>">About</a>
            <?php if (Auth::check()): ?>
                <a class="button button-small" href="<?= e(url($config, '?page=dashboard')) ?>">Dashboard</a>
                <a href="<?= e(url($config, '?page=profile')) ?>">Account</a>
                <?php if ($page === 'admin' || $page === 'users' || ($db instanceof PDO && Auth::isAdministrator($db))): ?><a href="<?= e(url($config, '?page=admin')) ?>">Admin</a><?php endif; ?>
            <?php else: ?>
                <a href="<?= e(url($config, '?page=login')) ?>">Sign in</a>
                <a class="button button-small" href="<?= e(url($config, '?page=register')) ?>">Get started</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php if ($page === 'home'): ?>
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <p class="eyebrow">Career clarity for the changing world</p>
                <h1>Discover what you can become.</h1>
                <p class="hero-copy">Explore meaningful careers, understand the skills they require, and follow a practical path toward your next opportunity.</p>
                <div class="actions">
                    <a class="button" href="<?= e(url($config, '?page=careers')) ?>">Explore careers</a>
                    <a class="button button-quiet" href="<?= e(url($config, '?page=assessment')) ?>">Discover your potential</a>
                </div>
            </div>
            <div class="hero-card">
                <p class="eyebrow">Your next step</p>
                <h2>Turn curiosity into capability.</h2>
                <p>Start with a career idea, identify the skills that matter, and build evidence through practical projects.</p>
                <div class="progress"><span></span></div>
                <small>Designed for steady, sustainable progress</small>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <div class="section-heading"><div><p class="eyebrow">Start exploring</p><h2>Career development with direction</h2></div><a href="<?= e(url($config, '?page=careers')) ?>">View all careers →</a></div>
            <div class="cards three">
                <article class="card"><span class="icon">01</span><h3>Explore careers</h3><p>Understand real responsibilities, skills, tools, and ways to enter different fields.</p></article>
                <article class="card"><span class="icon">02</span><h3>Build practical skills</h3><p>Follow structured learning paths with projects that turn knowledge into proof.</p></article>
                <article class="card"><span class="icon">03</span><h3>Prepare for change</h3><p>Develop adaptable human and technical skills for an AI-assisted workplace.</p></article>
            </div>
        </div>
    </section>
    <section class="section"><div class="container form-wrap"><div class="card"><p class="eyebrow">Stay informed</p><h2>Get useful career guidance.</h2><p>Receive occasional updates about new guides, learning paths, and resources.</p><?php if ($engagementErrors && ($_POST['action'] ?? '') === 'newsletter'): ?><div class="alert error"><ul><?php foreach ($engagementErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form class="content-search" method="post" action="<?= e(url($config)) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="newsletter"><label for="newsletter-email">Email address</label><input id="newsletter-email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" required><button class="button" type="submit">Subscribe</button></form></div></div></section>
<?php elseif ($page === 'compare'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Career tool</p><h1>Compare your directions.</h1><p>Select up to three career profiles to review their level, work style, category, and starting guidance side by side.</p></div></section>
    <section class="section"><div class="container compare-wrap"><form class="card form-card compare-form" method="get" action="<?= e(url($config)) ?>"><input type="hidden" name="page" value="compare"><fieldset><legend>Select careers</legend><p class="muted">Choose up to three options.</p><div class="compare-options"><?php foreach ($comparisonOptions as $option): ?><label><input type="checkbox" name="ids[]" value="<?= e((string) $option['id']) ?>" <?= in_array((int) $option['id'], $selectedIds ?? [], true) ? 'checked' : '' ?>> <?= e($option['title']) ?></label><?php endforeach; ?></div></fieldset><button class="button" type="submit">Compare selected careers</button></form><?php if ($comparisonCareers): ?><div class="compare-grid"><?php foreach ($comparisonCareers as $comparison): ?><article class="card comparison-card"><span class="tag"><?= e(ucfirst($comparison['level'])) ?></span><h2><?= e($comparison['title']) ?></h2><p class="muted"><?= e($comparison['category_name'] ?? 'Career') ?></p><dl><dt>Work arrangement</dt><dd><?= $comparison['remote_friendly'] ? 'Often compatible with remote work' : 'Varies by employer' ?></dd><dt>Overview</dt><dd><?= e($comparison['overview']) ?></dd></dl><a href="<?= e(url($config, '?page=career&slug=' . rawurlencode($comparison['slug']))) ?>">View full profile →</a></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><h2>Choose careers to compare</h2><p>Your comparison will appear here once you select at least one profile.</p></div><?php endif; ?></div></section>
<?php elseif ($page === 'careers'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Career discovery</p><h1>Find a direction that fits your ambitions.</h1><p>Browse career profiles and compare the skills, learning steps, and opportunities involved.</p></div></section>
    <section class="section"><div class="container">
        <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php else: ?>
        <form class="search-panel filters" method="get" action="<?= e(url($config)) ?>">
            <input type="hidden" name="page" value="careers">
            <div><label for="career-search">Search careers</label><input id="career-search" name="q" type="search" value="<?= e($search) ?>" placeholder="Try “data”, “design”, or “developer”"></div>
            <div><label for="career-category">Category</label><select id="career-category" name="category"><option value="">All categories</option><?php foreach ($categories as $item): ?><option value="<?= e((string) $item['id']) ?>" <?= (string) $category === (string) $item['id'] ? 'selected' : '' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
            <div><label for="career-level">Level</label><select id="career-level" name="level"><option value="">All levels</option><?php foreach (['beginner', 'intermediate', 'advanced'] as $option): ?><option value="<?= e($option) ?>" <?= $level === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
            <button class="button" type="submit">Filter careers</button>
        </form>
        <?php if (!$careers): ?><div class="empty-state"><h2>No careers found</h2><p>Try a broader search or remove one of the filters.</p></div><?php else: ?><div class="cards three"><?php foreach ($careers as $item): ?><article class="card"><span class="tag"><?= e(ucfirst($item['level'])) ?></span><h2><?= e($item['title']) ?></h2><p class="muted"><?= e($item['category_name'] ?? 'Career') ?><?= $item['remote_friendly'] ? ' · Remote-friendly' : '' ?></p><p><?= e($item['summary']) ?></p><a href="<?= e(url($config, '?page=career&slug=' . rawurlencode($item['slug']))) ?>">View profile →</a></article><?php endforeach; ?></div><?php endif; ?>
        <?php endif; ?>
    </div></section>
<?php elseif ($page === 'career'): ?>
    <?php if (!$career): ?>
        <section class="page-hero"><div class="container"><p class="eyebrow">Career discovery</p><h1>Career profile not found.</h1><p>That profile may have been removed or the link may be incorrect.</p><a class="button" href="<?= e(url($config, '?page=careers')) ?>">Browse careers</a></div></section>
    <?php else: ?>
        <section class="page-hero"><div class="container"><p class="eyebrow"><?= e($career['category_name'] ?? 'Career profile') ?></p><h1><?= e($career['title']) ?></h1><p><?= e($career['summary']) ?></p><div class="actions"><a class="button button-quiet" href="<?= e(url($config, '?page=careers')) ?>">← All careers</a><?php if (Auth::check()): ?><form method="post" action="<?= e(url($config, '?page=career&slug=' . rawurlencode($career['slug']))) ?>"><input type="hidden" name="action" value="toggle_saved"><input type="hidden" name="career_id" value="<?= e((string) $career['id']) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><button class="button" type="submit"><?= $careerSaved ? 'Remove from saved' : 'Save career' ?></button></form><?php else: ?><a class="button" href="<?= e(url($config, '?page=login')) ?>">Sign in to save</a><?php endif; ?></div></div></section>
        <section class="section"><div class="container profile-grid"><article class="card profile-main"><p class="eyebrow">What this career involves</p><h2>Overview</h2><p><?= e($career['overview']) ?></p><h2>Good starting points</h2><p>Build foundational knowledge, practise with small projects, and seek feedback from people doing related work. Requirements vary by employer and location, so use this profile as an exploration guide rather than a guarantee.</p></article><aside class="card"><h2>Profile snapshot</h2><dl><dt>Typical starting level</dt><dd><?= e(ucfirst($career['level'])) ?></dd><dt>Work arrangement</dt><dd><?= $career['remote_friendly'] ? 'Often compatible with remote work' : 'Varies by employer' ?></dd><dt>Category</dt><dd><?= e($career['category_name'] ?? 'Uncategorised') ?></dd></dl><?php if ($careerSkills): ?><h3>Related skills</h3><ul class="skill-list"><?php foreach ($careerSkills as $skill): ?><li><?= e($skill['name']) ?><span><?= e(ucfirst($skill['importance'])) ?></span></li><?php endforeach; ?></ul><?php endif; ?></aside></div></section>
    <?php endif; ?>
<?php elseif ($page === 'learning'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Learning paths</p><h1>Learn by building.</h1><p>Structured roadmaps help you move from fundamentals to portfolio-ready practice.</p></div></section>
    <section class="section"><div class="container"><?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php else: ?><div class="cards three"><?php foreach ($learningPaths as $path): ?><article class="card"><span class="tag"><?= e(ucfirst($path['level'])) ?></span><h2><?= e($path['title']) ?></h2><p><?= e($path['description']) ?></p><p class="muted"><?= e((string) $path['lesson_count']) ?> lessons</p><a href="<?= e(url($config, '?page=path&slug=' . rawurlencode($path['slug']))) ?>">View learning path →</a></article><?php endforeach; ?></div><?php endif; ?></div></section>
<?php elseif ($page === 'path'): ?>
    <?php if (!$learningPath): ?><section class="page-hero"><div class="container"><p class="eyebrow">Learning paths</p><h1>Learning path not found.</h1><p>That path may have been removed or the link may be incorrect.</p><a class="button" href="<?= e(url($config, '?page=learning')) ?>">Browse learning paths</a></div></section>
    <?php else: ?><section class="page-hero"><div class="container"><p class="eyebrow"><?= e(ucfirst($learningPath['level'])) ?> learning path</p><h1><?= e($learningPath['title']) ?></h1><p><?= e($learningPath['description']) ?></p><?php if (Auth::check()): ?><?php if (!$learningEnrolled): ?><form method="post" action="<?= e(url($config, '?page=path&slug=' . rawurlencode($learningPath['slug']))) ?>"><input type="hidden" name="action" value="enroll"><input type="hidden" name="path_id" value="<?= e((string) $learningPath['id']) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><button class="button" type="submit">Enroll in this path</button></form><?php else: ?><p class="progress-label"><?= e((string) $learningCompleted) ?> of <?= e((string) $learningTotal) ?> lessons complete</p><?php endif; ?><?php else: ?><a class="button" href="<?= e(url($config, '?page=login')) ?>">Sign in to enroll</a><?php endif; ?></div></section><section class="section"><div class="container learning-content"><?php if (!$learningModules): ?><div class="empty-state"><h2>Lessons are being prepared</h2><p>This path will be available soon.</p></div><?php else: ?><?php foreach ($learningModules as $module): ?><article class="card module"><h2><?= e($module['title']) ?></h2><ol class="lesson-list"><?php foreach ($module['lessons'] as $lesson): ?><li><div><h3><?= e($lesson['lesson_title']) ?></h3><p><?= e($lesson['lesson_content']) ?></p></div><?php if (Auth::check() && $learningEnrolled): ?><?php if ((int) $lesson['completed']): ?><span class="complete">Completed</span><?php else: ?><form method="post" action="<?= e(url($config, '?page=path&slug=' . rawurlencode($learningPath['slug']))) ?>"><input type="hidden" name="action" value="complete"><input type="hidden" name="lesson_id" value="<?= e((string) $lesson['lesson_id']) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><button class="button button-small" type="submit">Mark complete</button></form><?php endif; ?><?php endif; ?></li><?php endforeach; ?></ol></article><?php endforeach; ?><?php endif; ?></div></section><?php endif; ?>
<?php elseif ($page === 'assessment'): ?>
    <?php if ($dbError): ?><section class="page-hero"><div class="container"><p class="eyebrow">Career assessment</p><h1>The assessment is not available yet.</h1><p><?= e($dbError) ?></p></div></section>
    <?php elseif (!$assessment): ?><section class="page-hero"><div class="container"><p class="eyebrow">Career assessment</p><h1>The assessment is not available yet.</h1><p>Please import the seed data or return later while the question set is being prepared.</p></div></section>
    <?php elseif ($assessmentResult): ?><section class="page-hero"><div class="container"><p class="eyebrow">Exploratory results</p><h1>Directions worth exploring.</h1><p>These suggestions reflect your selected interests using a transparent rules-based score. They are not a professional psychological or psychometric assessment.</p></div></section><section class="section"><div class="container"><div class="cards three"><?php foreach ($assessmentResult['recommendations'] as $recommendation): ?><article class="card"><span class="tag">Score <?= e((string) $recommendation['score']) ?></span><h2><?= e($recommendation['title']) ?></h2><p><?= e($recommendation['summary']) ?></p><a href="<?= e(url($config, '?page=career&slug=' . rawurlencode($recommendation['slug']))) ?>">Explore career profile →</a></article><?php endforeach; ?></div><p class="assessment-note">Use these results as a starting point. Try a lesson, project, conversation, or job description before making decisions.</p><a class="button button-quiet" href="<?= e(url($config, '?page=assessment')) ?>">Retake assessment</a></div></section>
    <?php else: ?><section class="page-hero"><div class="container"><p class="eyebrow">Career assessment</p><h1>Find directions worth exploring.</h1><p><?= e($assessment['description']) ?></p></div></section><section class="section"><div class="container form-wrap assessment-wrap"><?php if ($assessmentErrors): ?><div class="alert error"><ul><?php foreach ($assessmentErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form class="assessment-form" method="post" action="<?= e(url($config, '?page=assessment')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><?php foreach ($assessmentQuestions as $questionId => $question): ?><fieldset class="card"><legend><?= e((string) $question['position']) ?>. <?= e($question['question']) ?></legend><?php foreach ($question['options'] as $option): ?><label class="option"><input type="radio" name="answers[<?= e((string) $questionId) ?>]" value="<?= e((string) $option['id']) ?>" required> <span><?= e($option['label']) ?></span></label><?php endforeach; ?></fieldset><?php endforeach; ?><button class="button" type="submit">View exploratory recommendations</button></form><p class="assessment-note">This assessment provides exploratory suggestions only. It does not determine your ideal career or guarantee employment outcomes.</p></div></section><?php endif; ?>
<?php elseif ($page === 'community'): ?>
    <?php if ($discussion): ?><section class="page-hero"><div class="container"><p class="eyebrow">Community discussion</p><h1><?= e($discussion['title']) ?></h1><p>Started by <?= e($discussion['name']) ?> · <?= e(date('F j, Y', strtotime($discussion['created_at']))) ?></p></div></section><section class="section"><div class="container community-wrap"><?php if ($communityErrors): ?><div class="alert error"><ul><?php foreach ($communityErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $communitySuccess = flash('success'); if ($communitySuccess): ?><div class="alert success"><?= e($communitySuccess) ?></div><?php endif; ?><article class="card"><p><?= nl2br(e($discussion['body'])) ?></p><details><summary>Report this discussion</summary><form class="form-card" method="post" action="<?= e(url($config, '?page=community&discussion=' . $discussion['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="report"><input type="hidden" name="discussion_id" value="<?= e((string) $discussion['id']) ?>"><label for="discussion-report-reason">Reason</label><textarea id="discussion-report-reason" name="reason" maxlength="500" required></textarea><button class="button button-small" type="submit">Submit report</button></form></details></article><h2>Replies</h2><?php foreach ($discussionReplies as $reply): ?><article class="card community-reply"><p class="muted"><?= e($reply['name']) ?> · <?= e(date('F j, Y', strtotime($reply['created_at']))) ?></p><p><?= nl2br(e($reply['body'])) ?></p><details><summary>Report reply</summary><form class="form-card" method="post" action="<?= e(url($config, '?page=community&discussion=' . $discussion['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="report"><input type="hidden" name="reply_id" value="<?= e((string) $reply['id']) ?>"><label for="reply-report-<?= e((string) $reply['id']) ?>">Reason</label><textarea id="reply-report-<?= e((string) $reply['id']) ?>" name="reason" maxlength="500" required></textarea><button class="button button-small" type="submit">Submit report</button></form></details></article><?php endforeach; ?><?php if (Auth::check()): ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=community&discussion=' . $discussion['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="reply"><input type="hidden" name="discussion_id" value="<?= e((string) $discussion['id']) ?>"><label for="reply-body">Add a reply</label><textarea id="reply-body" name="body" maxlength="4000" required></textarea><button class="button" type="submit">Submit reply</button></form><?php else: ?><p><a href="<?= e(url($config, '?page=login')) ?>">Sign in</a> to reply or report content.</p><?php endif; ?></div></section>
    <?php else: ?><section class="page-hero"><div class="container"><p class="eyebrow">Career community</p><h1>Learn together and share what works.</h1><p>Ask questions, share experiences, and exchange practical learning ideas. New posts are reviewed before appearing publicly.</p></div></section><section class="section"><div class="container community-wrap"><?php if ($communityErrors): ?><div class="alert error"><ul><?php foreach ($communityErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $communitySuccess = flash('success'); if ($communitySuccess): ?><div class="alert success"><?= e($communitySuccess) ?></div><?php endif; ?><?php if (Auth::check()): ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=community')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="create_discussion"><h2>Start a discussion</h2><label for="discussion-title">Title</label><input id="discussion-title" name="title" maxlength="220" required value="<?= e($_POST['title'] ?? '') ?>"><label for="discussion-body">Message</label><textarea id="discussion-body" name="body" maxlength="5000" rows="5" required><?= e($_POST['body'] ?? '') ?></textarea><button class="button" type="submit">Submit topic</button></form><?php else: ?><div class="card"><p><a href="<?= e(url($config, '?page=login')) ?>">Sign in</a> to start a discussion, reply, or report content.</p></div><?php endif; ?><div class="discussion-list"><?php if (!$discussions): ?><div class="empty-state"><h2>No discussions yet</h2><p>Be the first to share a thoughtful career or learning question.</p></div><?php else: ?><?php foreach ($discussions as $item): ?><article class="card"><p class="eyebrow"><?= e($item['name']) ?> · <?= e((string) $item['reply_count']) ?> replies</p><h2><?= e($item['title']) ?></h2><p><?= e(mb_strimwidth($item['body'], 0, 280, '...')) ?></p><a href="<?= e(url($config, '?page=community&discussion=' . $item['id'])) ?>">Open discussion →</a></article><?php endforeach; ?><?php endif; ?></div></div></section><?php endif; ?>
<?php elseif ($page === 'challenges'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Learning challenges</p><h1>Make progress visible through small experiments.</h1><p>Choose a practical challenge, complete the suggested action, and build evidence of consistent growth.</p></div></section>
    <section class="section"><div class="container challenge-grid"><?php if ($challengeErrors): ?><div class="alert error"><ul><?php foreach ($challengeErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $challengeSuccess = flash('success'); if ($challengeSuccess): ?><div class="alert success"><?= e($challengeSuccess) ?></div><?php endif; ?><?php foreach ($challenges as $challenge): ?><article class="card challenge-card"><p class="eyebrow"><?= e((string) $challenge['duration_days']) ?>-day challenge</p><h2><?= e($challenge['title']) ?></h2><p><?= e($challenge['description']) ?></p><p><strong>Action:</strong> <?= e($challenge['action_text']) ?></p><?php if ($challenge['completed_at']): ?><p class="complete">Completed</p><?php elseif ($challenge['started_at']): ?><form method="post" action="<?= e(url($config, '?page=challenges')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="complete_challenge"><input type="hidden" name="challenge_id" value="<?= e((string) $challenge['id']) ?>"><button class="button" type="submit">Mark complete</button></form><?php else: ?><form method="post" action="<?= e(url($config, '?page=challenges')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="start_challenge"><input type="hidden" name="challenge_id" value="<?= e((string) $challenge['id']) ?>"><button class="button" type="submit">Start challenge</button></form><?php endif; ?></article><?php endforeach; ?><?php if (!$challenges): ?><div class="empty-state"><h2>No challenges are published yet</h2><p>Check back soon for a new practical activity.</p></div><?php endif; ?></div></section>
<?php elseif ($page === 'planner'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Daily learning planner</p><h1>Give your learning time a place to land.</h1><p>Plan focused tasks, estimate the time you need, and mark the work complete when you finish.</p></div></section>
    <section class="section"><div class="container planner-wrap"><?php if ($plannerErrors): ?><div class="alert error"><ul><?php foreach ($plannerErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $plannerSuccess = flash('success'); if ($plannerSuccess): ?><div class="alert success"><?= e($plannerSuccess) ?></div><?php endif; ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=planner')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="add_planner_entry"><h2>Plan a learning task</h2><label for="planned-for">Date</label><input id="planned-for" name="planned_for" type="date" value="<?= e($_POST['planned_for'] ?? date('Y-m-d')) ?>" required><label for="planner-task">Task</label><input id="planner-task" name="task" maxlength="220" value="<?= e($_POST['task'] ?? '') ?>" placeholder="Complete the HTML forms lesson" required><label for="planner-minutes">Planned minutes</label><input id="planner-minutes" name="minutes" type="number" min="5" max="1440" value="<?= e($_POST['minutes'] ?? '30') ?>" required><button class="button" type="submit">Add to planner</button></form><div class="planner-list"><?php if (!$plannerEntries): ?><div class="empty-state"><h2>Your planner is clear</h2><p>Add a small, specific task to create your next learning step.</p></div><?php else: ?><?php foreach ($plannerEntries as $entry): ?><article class="card planner-item"><div><p class="eyebrow"><?= e(date('l, F j, Y', strtotime($entry['planned_for']))) ?> · <?= e((string) $entry['minutes']) ?> minutes</p><h2><?= e($entry['task']) ?></h2><?php if ($entry['completed_at']): ?><p class="complete">Completed</p><?php endif; ?></div><div class="goal-actions"><?php if (!$entry['completed_at']): ?><form method="post" action="<?= e(url($config, '?page=planner')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="complete_planner_entry"><input type="hidden" name="entry_id" value="<?= e((string) $entry['id']) ?>"><button class="button button-small" type="submit">Complete</button></form><?php endif; ?><form method="post" action="<?= e(url($config, '?page=planner')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_planner_entry"><input type="hidden" name="entry_id" value="<?= e((string) $entry['id']) ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form></div></article><?php endforeach; ?><?php endif; ?></div></div></section>
<?php elseif ($page === 'journal'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Personal development journal</p><h1>Notice what you are learning.</h1><p>Keep private reflections about your progress, decisions, experiments, and next steps.</p></div></section>
    <section class="section"><div class="container journal-wrap"><?php if ($journalErrors): ?><div class="alert error"><ul><?php foreach ($journalErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $journalSuccess = flash('success'); if ($journalSuccess): ?><div class="alert success"><?= e($journalSuccess) ?></div><?php endif; ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=journal')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="add_journal_entry"><h2>Write a reflection</h2><label for="journal-date">Date</label><input id="journal-date" name="entry_date" type="date" value="<?= e($_POST['entry_date'] ?? date('Y-m-d')) ?>" required><label for="journal-title">Title</label><input id="journal-title" name="title" maxlength="220" value="<?= e($_POST['title'] ?? '') ?>" placeholder="What changed this week?" required><label for="journal-body">Reflection</label><textarea id="journal-body" name="body" maxlength="10000" rows="8" placeholder="What did you learn, try, or decide?" required><?= e($_POST['body'] ?? '') ?></textarea><button class="button" type="submit">Save reflection</button></form><div class="journal-list"><?php if (!$journalEntries): ?><div class="empty-state"><h2>Your journal is empty</h2><p>Write your first reflection after a lesson, challenge, or career experiment.</p></div><?php else: ?><?php foreach ($journalEntries as $entry): ?><article class="card journal-item"><p class="eyebrow"><?= e(date('F j, Y', strtotime($entry['entry_date']))) ?></p><h2><?= e($entry['title']) ?></h2><p><?= nl2br(e($entry['body'])) ?></p><form method="post" action="<?= e(url($config, '?page=journal')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_journal_entry"><input type="hidden" name="entry_id" value="<?= e((string) $entry['id']) ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form></article><?php endforeach; ?><?php endif; ?></div></div></section>
<?php elseif ($page === 'transition'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Career tools</p><h1>Plan your next career move.</h1><p>Turn a career change into a clear direction, timeline, and reason to keep learning.</p></div></section>
    <section class="section"><div class="container transition-wrap"><?php if ($transitionErrors): ?><div class="alert error"><ul><?php foreach ($transitionErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $transitionSuccess = flash('success'); if ($transitionSuccess): ?><div class="alert success"><?= e($transitionSuccess) ?></div><?php endif; ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=transition')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="add_transition_plan"><h2>Start a transition plan</h2><label for="current-position">Your current role or situation</label><input id="current-position" name="current_position" maxlength="220" value="<?= e($_POST['current_position'] ?? '') ?>" placeholder="Customer support specialist" required><label for="target-career">Career you want to explore</label><select id="target-career" name="target_career_id" required><option value="">Choose a career</option><?php foreach ($transitionCareers as $targetCareer): ?><option value="<?= e((string) $targetCareer['id']) ?>" <?= (string) ($_POST['target_career_id'] ?? '') === (string) $targetCareer['id'] ? 'selected' : '' ?>><?= e($targetCareer['title']) ?></option><?php endforeach; ?></select><label for="target-date">Target date <span class="muted">(optional)</span></label><input id="target-date" name="target_date" type="date" value="<?= e($_POST['target_date'] ?? '') ?>"><label for="transition-reason">Why are you considering this move?</label><textarea id="transition-reason" name="reason" maxlength="5000" rows="6" placeholder="Describe the skills, opportunities, or working style you want to move toward." required><?= e($_POST['reason'] ?? '') ?></textarea><button class="button" type="submit">Save transition plan</button></form><div class="transition-list"><?php if (!$transitionPlans): ?><div class="empty-state"><h2>No transition plans yet</h2><p>Choose a target career to create a focused next step.</p></div><?php else: ?><?php foreach ($transitionPlans as $plan): ?><article class="card transition-item"><div><p class="eyebrow"><?= e(ucfirst($plan['status'])) ?><?= $plan['target_date'] ? ' · Target ' . e(date('F j, Y', strtotime($plan['target_date']))) : '' ?></p><h2><?= e($plan['current_position']) ?> → <a href="<?= e(url($config, '?page=career&slug=' . rawurlencode($plan['target_slug']))) ?>"><?= e($plan['target_title']) ?></a></h2><p><?= nl2br(e($plan['reason'])) ?></p></div><div class="goal-actions"><?php if ($plan['status'] === 'active'): ?><form method="post" action="<?= e(url($config, '?page=transition')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="complete_transition_plan"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><button class="button button-small" type="submit">Mark complete</button></form><?php endif; ?><form method="post" action="<?= e(url($config, '?page=transition')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_transition_plan"><input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form></div></article><?php endforeach; ?><?php endif; ?></div></div></section>
<?php elseif ($page === 'showcase'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Project showcase</p><h1>Show what you can build.</h1><p>Share practical work, explain what you learned, and give your developing skills a clear story.</p></div></section>
    <section class="section"><div class="container showcase-wrap"><?php if ($showcaseErrors): ?><div class="alert error"><ul><?php foreach ($showcaseErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $showcaseSuccess = flash('success'); if ($showcaseSuccess): ?><div class="alert success"><?= e($showcaseSuccess) ?></div><?php endif; ?><?php if (Auth::check()): ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=showcase')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="add_showcase"><h2>Submit a project</h2><label for="showcase-title">Project title</label><input id="showcase-title" name="title" maxlength="220" value="<?= e($_POST['title'] ?? '') ?>" required><label for="showcase-description">What did you build and learn?</label><textarea id="showcase-description" name="description" maxlength="5000" rows="7" required></textarea><label for="showcase-url">Project URL <span class="muted">(optional)</span></label><input id="showcase-url" name="project_url" type="url" maxlength="500" value="<?= e($_POST['project_url'] ?? '') ?>" placeholder="https://example.com/project"><label for="showcase-skills">Skills used <span class="muted">(optional)</span></label><input id="showcase-skills" name="skills" maxlength="500" value="<?= e($_POST['skills'] ?? '') ?>" placeholder="HTML, PHP, user research"><button class="button" type="submit">Submit for review</button></form><?php else: ?><div class="card empty-state"><h2>Have a project to share?</h2><p>Sign in to submit your work for community review.</p><a class="button" href="<?= e(url($config, '?page=login')) ?>">Sign in</a></div><?php endif; ?><div class="showcase-list"><?php if (!$showcases): ?><div class="empty-state"><h2>No projects are published yet</h2><p>Be the first to share a practical project.</p></div><?php else: ?><?php foreach ($showcases as $showcase): ?><article class="card showcase-item"><p class="eyebrow"><?= e($showcase['status'] === 'approved' ? 'Published project' : 'Awaiting moderation') ?> · <?= e($showcase['name']) ?></p><h2><?= e($showcase['title']) ?></h2><p><?= nl2br(e($showcase['description'])) ?></p><?php if ($showcase['skills']): ?><p class="muted"><strong>Skills:</strong> <?= e($showcase['skills']) ?></p><?php endif; ?><?php if ($showcase['project_url']): ?><a href="<?= e($showcase['project_url']) ?>" rel="noopener noreferrer nofollow">View project ↗</a><?php endif; ?><?php if ((int) $showcase['user_id'] === (int) (Auth::user()['id'] ?? 0)): ?><form method="post" action="<?= e(url($config, '?page=showcase')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_showcase"><input type="hidden" name="showcase_id" value="<?= e((string) $showcase['id']) ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form><?php endif; ?></article><?php endforeach; ?><?php endif; ?></div></div></section>
<?php elseif ($page === 'users'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Administrator dashboard</p><h1>Manage user accounts.</h1><p>Search accounts and suspend or reactivate access. Your own account cannot be changed here.</p></div></section>
    <section class="section"><div class="container moderation-list"><?php $userSuccess = flash('success'); $userError = flash('error'); if ($userSuccess): ?><div class="alert success"><?= e($userSuccess) ?></div><?php endif; ?><?php if ($userError): ?><div class="alert error"><?= e($userError) ?></div><?php endif; ?><form class="search-panel content-search" method="get" action="<?= e(url($config, '?page=users')) ?>"><label for="user-search">Search users</label><input id="user-search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search by name or email"><button class="button" type="submit">Search</button></form><?php if (!$userManagementItems): ?><div class="empty-state"><h2>No users found</h2><p>Try a different name or email address.</p></div><?php else: ?><?php foreach ($userManagementItems as $managedUser): ?><article class="card moderation-item"><h2><?= e($managedUser['name']) ?></h2><p class="muted"><?= e($managedUser['email']) ?> · Joined <?= e(date('F j, Y', strtotime($managedUser['created_at']))) ?></p><form class="moderation-form" method="post" action="<?= e(url($config, '?page=users')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="id" value="<?= e((string) $managedUser['id']) ?>"><select name="status" aria-label="User status"><?php foreach (['active', 'suspended', 'deleted'] as $status): ?><option value="<?= e($status) ?>" <?= $managedUser['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select><button class="button button-small" type="submit">Update status</button></form></article><?php endforeach; ?><?php endif; ?></div></section>
<?php elseif ($page === 'admin-path'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Learning content management</p><h1><?= e($adminLearningPath['title'] ?? 'Learning path') ?></h1><p>Manage modules and lessons for this learning path.</p><div class="admin-links"><a class="button button-small" href="<?= e(url($config, '?page=admin&section=learning_paths')) ?>">Back to learning paths</a><a class="button button-small" href="<?= e(url($config, '?page=path&slug=' . rawurlencode($adminLearningPath['slug'] ?? ''))) ?>">View public path</a></div></div></section>
    <section class="section"><div class="container learning-content"><?php $contentSuccess = flash('success'); $contentError = flash('error'); if ($contentSuccess): ?><div class="alert success"><?= e($contentSuccess) ?></div><?php endif; ?><?php if ($contentError): ?><div class="alert error"><?= e($contentError) ?></div><?php endif; ?><form class="card admin-form" method="post" action="<?= e(url($config, '?page=admin-path&id=' . $adminLearningPath['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="path_id" value="<?= e((string) $adminLearningPath['id']) ?>"><input type="hidden" name="action" value="add_module"><h2>Add module</h2><label for="module-title">Module title</label><input id="module-title" name="title" required><label for="module-position">Position</label><input id="module-position" name="position" type="number" min="1" required><button class="button" type="submit">Add module</button></form><?php foreach ($adminLearningModules as $module): ?><article class="card module"><h2><?= e($module['position'] . '. ' . $module['title']) ?></h2><?php foreach ($module['lessons'] as $lesson): ?><div class="admin-row"><div><strong><?= e($lesson['position'] . '. ' . $lesson['title']) ?></strong><small><?= e($lesson['content']) ?></small></div></div><?php endforeach; ?><form class="admin-form" method="post" action="<?= e(url($config, '?page=admin-path&id=' . $adminLearningPath['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="path_id" value="<?= e((string) $adminLearningPath['id']) ?>"><input type="hidden" name="module_id" value="<?= e((string) $module['id']) ?>"><input type="hidden" name="action" value="add_lesson"><h3>Add lesson</h3><label for="lesson-title-<?= e((string) $module['id']) ?>">Lesson title</label><input id="lesson-title-<?= e((string) $module['id']) ?>" name="title" required><label for="lesson-content-<?= e((string) $module['id']) ?>">Lesson content</label><textarea id="lesson-content-<?= e((string) $module['id']) ?>" name="content" rows="5" maxlength="10000" required></textarea><label for="lesson-position-<?= e((string) $module['id']) ?>">Position</label><input id="lesson-position-<?= e((string) $module['id']) ?>" name="position" type="number" min="1" required><button class="button button-small" type="submit">Add lesson</button></form></article><?php endforeach; ?></div></section>
<?php elseif ($page === 'settings'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Administrator settings</p><h1>Shape the public experience.</h1><p>Update the site's name, messaging, and support contact without editing source files.</p></div></section>
    <section class="section"><div class="container form-wrap"><?php $settingsSuccess = flash('success'); if ($settingsSuccess): ?><div class="alert success"><?= e($settingsSuccess) ?></div><?php endif; ?><?php if ($formErrors): ?><div class="alert error"><ul><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php else: ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=settings')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><label for="site-name">Site name</label><input id="site-name" name="site_name" maxlength="120" value="<?= e($_POST['site_name'] ?? $siteSettings['site_name']) ?>" required><label for="site-tagline">Tagline</label><input id="site-tagline" name="site_tagline" maxlength="220" value="<?= e($_POST['site_tagline'] ?? $siteSettings['site_tagline']) ?>" required><label for="site-description">Default site description</label><textarea id="site-description" name="site_description" maxlength="320" rows="4" required><?= e($_POST['site_description'] ?? $siteSettings['site_description']) ?></textarea><label for="contact-email">Contact email <span class="muted">(optional)</span></label><input id="contact-email" name="contact_email" type="email" value="<?= e($_POST['contact_email'] ?? $siteSettings['contact_email']) ?>"><button class="button" type="submit">Save settings</button></form><?php endif; ?></div></section>
<?php elseif ($page === 'admin'): ?>
    <?php if ($dbError): ?><section class="page-hero"><div class="container"><p class="eyebrow">Administrator dashboard</p><h1>Administration is unavailable.</h1><p><?= e($dbError) ?></p></div></section>
    <?php else: ?><section class="page-hero"><div class="container"><p class="eyebrow">Administrator dashboard</p><h1>Keep CareerPath useful and current.</h1><p>Manage the platform's core educational content.</p><div class="admin-links"><a class="button button-small" href="<?= e(url($config, '?page=admin')) ?>">Overview</a><a class="button button-small" href="<?= e(url($config, '?page=users')) ?>">Users</a><a class="button button-small" href="<?= e(url($config, '?page=admin&section=careers')) ?>">Careers</a><a class="button button-small" href="<?= e(url($config, '?page=admin&section=articles')) ?>">Articles</a>    <a class="button button-small" href="<?= e(url($config, '?page=admin&section=learning_paths')) ?>">Learning paths</a>    <a class="button button-small" href="<?= e(url($config, '?page=admin&section=resources')) ?>">Resources</a><a class="button button-small" href="<?= e(url($config, '?page=settings')) ?>">Settings</a></div></div></section><section class="section"><div class="container"><?php if ($adminFormErrors): ?><div class="alert error"><ul><?php foreach ($adminFormErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $successMessage = flash('success'); if ($successMessage): ?><div class="alert success"><?= e($successMessage) ?></div><?php endif; ?><?php if ($adminSection === 'overview'): ?><div class="admin-grid"><?php $metricLabels = ['users' => 'Active users', 'careers' => 'Career profiles', 'articles' => 'Published guides', 'drafts' => 'Draft guides', 'learning_paths' => 'Published learning paths', 'resources' => 'Published resources']; foreach ($metricLabels as $metric => $label): ?><article class="card metric"><p class="eyebrow"><?= e($label) ?></p><strong><?= e((string) ($adminMetrics[$metric] ?? 0)) ?></strong></article><?php endforeach; ?></div><div class="card admin-note"><h2>Management areas</h2><p>Create and update careers, guides, and verified external resources. All changes are recorded in the audit log.</p></div><?php else: ?><div class="admin-workspace"><div class="card admin-list"><div class="section-heading"><h2><?= e(ucfirst($adminSection)) ?></h2><a class="button button-small" href="<?= e(url($config, '?page=admin&section=' . rawurlencode($adminSection))) ?>">New</a></div><?php foreach ($adminItems as $item): ?><div class="admin-row"><div><strong><?= e($item['title']) ?></strong><small><?= e($item['slug'] ?? ($item['category_name'] ?? $item['category'] ?? '')) ?></small></div>    <a href="<?= e(url($config, '?page=admin&section=' . rawurlencode($adminSection) . '&id=' . $item['id'])) ?>">Edit</a><?php if ($adminSection === 'learning_paths'): ?><a href="<?= e(url($config, '?page=admin-path&id=' . $item['id'])) ?>">Modules and lessons</a><?php endif; ?></div><?php endforeach; ?></div><form class="card admin-form" method="post" action="<?= e(url($config, '?page=admin&section=' . rawurlencode($adminSection) . ($adminEdit ? '&id=' . $adminEdit['id'] : ''))) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="section" value="<?= e($adminSection) ?>"><input type="hidden" name="id" value="<?= e((string) ($adminEdit['id'] ?? '')) ?>"><h2><?= $adminEdit ? 'Edit' : 'Create' ?> <?= e(rtrim($adminSection, 's')) ?></h2><label for="admin-title">Title</label><input id="admin-title" name="title" value="<?= e($_POST['title'] ?? $adminEdit['title'] ?? '') ?>" required><label for="admin-slug">Slug</label><input id="admin-slug" name="slug" value="<?= e($_POST['slug'] ?? $adminEdit['slug'] ?? '') ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required><?php if ($adminSection === 'careers'): ?><label for="admin-summary">Summary</label><textarea id="admin-summary" name="summary" required><?= e($_POST['summary'] ?? $adminEdit['summary'] ?? '') ?></textarea><label for="admin-overview">Overview</label><textarea id="admin-overview" name="overview" required><?= e($_POST['overview'] ?? $adminEdit['overview'] ?? '') ?></textarea><label for="admin-category">Category</label><select id="admin-category" name="category_id" required><?php foreach ($categories as $categoryItem): ?><option value="<?= e((string) $categoryItem['id']) ?>" <?= (string) ($_POST['category_id'] ?? $adminEdit['category_id'] ?? '') === (string) $categoryItem['id'] ? 'selected' : '' ?>><?= e($categoryItem['name']) ?></option><?php endforeach; ?></select><label for="admin-level">Level</label><select id="admin-level" name="level"><?php foreach (['beginner','intermediate','advanced'] as $level): ?><option value="<?= e($level) ?>" <?= ($_POST['level'] ?? $adminEdit['level'] ?? '') === $level ? 'selected' : '' ?>><?= e(ucfirst($level)) ?></option><?php endforeach; ?></select><label class="check"><input type="checkbox" name="remote_friendly" <?= isset($_POST['remote_friendly']) || !empty($adminEdit['remote_friendly']) ? 'checked' : '' ?>> Remote-friendly</label><?php elseif ($adminSection === 'articles'): ?><label for="admin-excerpt">Excerpt</label><textarea id="admin-excerpt" name="excerpt" required><?= e($_POST['excerpt'] ?? $adminEdit['excerpt'] ?? '') ?></textarea><label for="admin-body">Body</label><textarea id="admin-body" name="body" rows="8" required><?= e($_POST['body'] ?? $adminEdit['body'] ?? '') ?></textarea><label for="admin-status">Status</label><select id="admin-status" name="status"><option value="draft" <?= ($_POST['status'] ?? $adminEdit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($_POST['status'] ?? $adminEdit['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select>    <?php elseif ($adminSection === 'learning_paths'): ?><label for="admin-description">Description</label><textarea id="admin-description" name="description" required><?= e($_POST['description'] ?? $adminEdit['description'] ?? '') ?></textarea><label for="admin-level">Level</label><select id="admin-level" name="level"><?php foreach (['beginner','intermediate','advanced'] as $level): ?><option value="<?= e($level) ?>" <?= ($_POST['level'] ?? $adminEdit['level'] ?? '') === $level ? 'selected' : '' ?>><?= e(ucfirst($level)) ?></option><?php endforeach; ?></select><label class="check"><input type="checkbox" name="published" <?= isset($_POST['published']) || !empty($adminEdit['published']) ? 'checked' : '' ?>> Published</label><?php else: ?><label for="admin-description">Description</label><textarea id="admin-description" name="description" required><?= e($_POST['description'] ?? $adminEdit['description'] ?? '') ?></textarea><label for="admin-url">Official URL</label><input id="admin-url" name="url" type="url" value="<?= e($_POST['url'] ?? $adminEdit['url'] ?? '') ?>" required><label for="admin-resource-category">Category</label><input id="admin-resource-category" name="category" value="<?= e($_POST['category'] ?? $adminEdit['category'] ?? '') ?>" required><label for="admin-resource-level">Level</label><select id="admin-resource-level" name="level"><?php foreach (['all','beginner','intermediate','advanced'] as $level): ?><option value="<?= e($level) ?>" <?= ($_POST['level'] ?? $adminEdit['level'] ?? '') === $level ? 'selected' : '' ?>><?= e(ucfirst($level)) ?></option><?php endforeach; ?></select><label class="check"><input type="checkbox" name="published" <?= isset($_POST['published']) || !empty($adminEdit['published']) ? 'checked' : '' ?>> Published</label><?php endif; ?><button class="button" type="submit">Save changes</button></form></div><?php endif; ?></div></section><?php endif; ?>
<?php elseif ($page === 'articles'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Guides and tutorials</p><h1>Learn something useful today.</h1><p>Practical articles for career decisions, skill development, and professional growth.</p></div></section>
    <section class="section"><div class="container"><form class="search-panel content-search" method="get" action="<?= e(url($config)) ?>"><input type="hidden" name="page" value="articles"><label for="article-search">Search guides</label><input id="article-search" name="q" value="<?= e($search) ?>" placeholder="Search articles and tutorials"><button class="button" type="submit">Search</button></form><?php if (!$articles): ?><div class="empty-state"><h2>No published guides found</h2><p>Try a different search term.</p></div><?php else: ?><div class="cards three"><?php foreach ($articles as $item): ?><article class="card"><p class="eyebrow"><?= e($item['published_at'] ? date('F j, Y', strtotime($item['published_at'])) : 'Guide') ?></p><h2><?= e($item['title']) ?></h2><p><?= e($item['excerpt']) ?></p><a href="<?= e(url($config, '?page=article&slug=' . rawurlencode($item['slug']))) ?>">Read guide →</a></article><?php endforeach; ?></div><?php endif; ?></div></section>
<?php elseif ($page === 'article'): ?>
    <?php if (!$article): ?><section class="page-hero"><div class="container"><p class="eyebrow">Guides and tutorials</p><h1>Article not found.</h1><p>This guide may have been unpublished or the link may be incorrect.</p><a class="button" href="<?= e(url($config, '?page=articles')) ?>">Browse guides</a></div></section>
    <?php else: ?><?php $articleSuccess = flash('success'); ?><section class="page-hero"><div class="container"><p class="eyebrow"><?= e($article['published_at'] ? date('F j, Y', strtotime($article['published_at'])) : 'Guide') ?></p><h1><?= e($article['title']) ?></h1><p><?= e($article['excerpt']) ?></p><?php if (Auth::check()): ?><form method="post" action="<?= e(url($config, '?page=article&slug=' . rawurlencode($article['slug']))) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="bookmark"><input type="hidden" name="article_id" value="<?= e((string) $article['id']) ?>"><button class="button button-quiet" type="submit"><?= $articleBookmarked ? 'Remove bookmark' : 'Bookmark guide' ?></button></form><?php else: ?><a class="button button-quiet" href="<?= e(url($config, '?page=login')) ?>">Sign in to bookmark</a><?php endif; ?></div></section><section class="section"><div class="container article-body"><article class="card"><div class="article-copy"><?= nl2br(e($article['body'])) ?></div><a href="<?= e(url($config, '?page=articles')) ?>">← Back to guides</a></article><section class="comments"><h2>Discussion</h2><?php if ($engagementErrors): ?><div class="alert error"><ul><?php foreach ($engagementErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php if ($articleSuccess): ?><div class="alert success"><?= e($articleSuccess) ?></div><?php endif; ?><?php if (!$articleComments): ?><p class="muted">No approved comments yet. Be the first to contribute.</p><?php else: ?><?php foreach ($articleComments as $comment): ?><div class="comment"><strong><?= e($comment['name']) ?></strong><small><?= e(date('F j, Y', strtotime($comment['created_at']))) ?></small><p><?= nl2br(e($comment['body'])) ?></p></div><?php endforeach; ?><?php endif; ?><?php if (Auth::check()): ?><form class="card comment-form" method="post" action="<?= e(url($config, '?page=article&slug=' . rawurlencode($article['slug']))) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="comment"><input type="hidden" name="article_id" value="<?= e((string) $article['id']) ?>"><label for="comment-body">Add a comment</label><textarea id="comment-body" name="body" maxlength="4000" required><?= e($_POST['body'] ?? '') ?></textarea><button class="button" type="submit">Submit for moderation</button></form><?php else: ?><p><a href="<?= e(url($config, '?page=login')) ?>">Sign in</a> to join the discussion.</p><?php endif; ?></section></div></section><?php endif; ?>
<?php elseif ($page === 'resources'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Resource library</p><h1>Trusted places to keep learning.</h1><p>Curated external resources with official links and review dates.</p></div></section>
    <section class="section"><div class="container"><form class="search-panel content-search" method="get" action="<?= e(url($config)) ?>"><input type="hidden" name="page" value="resources"><label for="resource-search">Search resources</label><input id="resource-search" name="q" value="<?= e($search) ?>" placeholder="Search documentation, courses, and tools"><button class="button" type="submit">Search</button></form><?php if (!$resources): ?><div class="empty-state"><h2>No resources found</h2><p>Try a different search term.</p></div><?php else: ?><div class="cards three"><?php foreach ($resources as $resource): ?><article class="card"><span class="tag"><?= e($resource['category']) ?></span><h2><?= e($resource['title']) ?></h2><p><?= e($resource['description']) ?></p><p class="muted">Level: <?= e(ucfirst($resource['level'])) ?><?php if ($resource['reviewed_at']): ?> · Reviewed <?= e(date('F Y', strtotime($resource['reviewed_at']))) ?><?php endif; ?></p><a href="<?= e($resource['url']) ?>" target="_blank" rel="noopener noreferrer">Visit official resource ↗</a></article><?php endforeach; ?></div><?php endif; ?></div></section>
<?php elseif ($page === 'contact'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Contact CareerPath</p><h1>Tell us what would help.</h1><p>Send a question, suggestion, or partnership enquiry and our team will review it.</p></div></section>
    <section class="section"><div class="container form-wrap"><?php if ($engagementErrors): ?><div class="alert error"><ul><?php foreach ($engagementErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=contact')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><label for="contact-name">Name</label><input id="contact-name" name="name" value="<?= e($_POST['name'] ?? '') ?>" autocomplete="name" required><label for="contact-email">Email address</label><input id="contact-email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required><label for="contact-subject">Subject</label><input id="contact-subject" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" maxlength="220" required><label for="contact-message">Message</label><textarea id="contact-message" name="message" rows="8" maxlength="5000" required><?= e($_POST['message'] ?? '') ?></textarea><button class="button" type="submit">Send message</button></form></div></section>
<?php elseif ($page === 'moderation'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Administrator moderation</p><h1><?= e(ucfirst($moderationType)) ?></h1><p>Review submitted community content and update its moderation status.</p><div class="admin-links"><a class="button button-small" href="<?= e(url($config, '?page=moderation&type=comments')) ?>">Comments</a><a class="button button-small" href="<?= e(url($config, '?page=moderation&type=discussions')) ?>">Discussions</a><a class="button button-small" href="<?= e(url($config, '?page=moderation&type=reports')) ?>">Reports</a><a class="button button-small" href="<?= e(url($config, '?page=moderation&type=messages')) ?>">Messages</a></div></div></section>
    <section class="section"><div class="container moderation-list"><?php $moderationSuccess = flash('success'); $moderationError = flash('error'); if ($moderationSuccess): ?><div class="alert success"><?= e($moderationSuccess) ?></div><?php endif; ?><?php if ($moderationError): ?><div class="alert error"><?= e($moderationError) ?></div><?php endif; ?><?php if (!$moderationItems): ?><div class="empty-state"><h2>Nothing to review</h2><p>New submissions will appear here.</p></div><?php else: ?><?php foreach ($moderationItems as $item): ?><article class="card moderation-item"><p class="eyebrow"><?php if ($moderationType === 'comments'): ?><?= e($item['article_title'] ?? 'Article comment') ?><?php elseif ($moderationType === 'discussions'): ?>Discussion<?php elseif ($moderationType === 'reports'): ?>Report from <?= e($item['name']) ?><?php else: ?>Contact message<?php endif; ?></p><h2><?= e($moderationType === 'comments' || $moderationType === 'discussions' ? $item['name'] : ($moderationType === 'reports' ? ($item['discussion_title'] ?? 'Reported reply') : $item['subject'])) ?></h2><?php if ($moderationType === 'messages'): ?><p class="muted"><?= e($item['email']) ?></p><?php endif; ?><p><?= nl2br(e($moderationType === 'comments' || $moderationType === 'discussions' ? $item['body'] : ($moderationType === 'reports' ? $item['reason'] . ($item['reply_body'] ? "\n\nReported reply: " . $item['reply_body'] : '') : $item['message'])) ) ?></p><form class="moderation-form" method="post" action="<?= e(url($config, '?page=moderation&type=' . rawurlencode($moderationType))) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">    <select name="status" aria-label="Status"><?php $statuses = $moderationType === 'comments' || $moderationType === 'discussions' ? ['pending', 'approved', 'rejected'] : ($moderationType === 'reports' ? ['new', 'reviewed', 'dismissed'] : ['new', 'read', 'closed']); foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select><button class="button button-small" type="submit">Update status</button></form></article><?php endforeach; ?><?php endif; ?></div></section>
<?php elseif ($page === 'about'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">About CareerPath</p><h1>Practical guidance for lifelong growth.</h1><p>CareerPath is designed to help people make informed decisions, build relevant skills, and adapt with confidence.</p></div></section>
<?php elseif ($page === 'profile'): ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">Account settings</p><h1>Keep your account current.</h1><p>Update your details or securely close your account.</p></div></section>
    <section class="section"><div class="container form-wrap">
        <?php $profileSuccess = flash('success'); if ($profileSuccess): ?><div class="alert success"><?= e($profileSuccess) ?></div><?php endif; ?>
        <?php if ($profileErrors): ?><div class="alert error"><ul><?php foreach ($profileErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form class="card form-card" method="post" action="<?= e(url($config, '?page=profile')) ?>" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="upload_avatar">
            <h2>Profile image</h2>
            <?php if (!empty($profileUser['avatar_path'])): ?><img class="profile-avatar" src="<?= e(url($config, (string) $profileUser['avatar_path'])) ?>" alt="Your profile image"><?php endif; ?>
            <label for="profile-avatar">Upload JPEG, PNG, or WebP (2 MB maximum)</label><input id="profile-avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
            <button class="button" type="submit">Upload image</button>
        </form>
        <form class="card form-card" method="post" action="<?= e(url($config, '?page=profile')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="update_profile">
            <label for="profile-name">Name</label><input id="profile-name" name="name" value="<?= e($_POST['name'] ?? $profileUser['name'] ?? '') ?>" autocomplete="name" required>
            <label for="profile-email">Email address</label><input id="profile-email" name="email" type="email" value="<?= e($_POST['email'] ?? $profileUser['email'] ?? '') ?>" autocomplete="email" required>
            <label for="profile-current-password">Current password</label><input id="profile-current-password" name="current_password" type="password" autocomplete="current-password" required>
            <label for="profile-new-password">New password <span class="muted">(optional)</span></label><input id="profile-new-password" name="new_password" type="password" autocomplete="new-password">
            <label for="profile-new-password-confirmation">Confirm new password</label><input id="profile-new-password-confirmation" name="new_password_confirmation" type="password" autocomplete="new-password">
            <button class="button" type="submit">Save account changes</button>
        </form>
        <form class="card form-card danger-zone" method="post" action="<?= e(url($config, '?page=profile')) ?>" onsubmit="return confirm('Close your CareerPath account? This cannot be undone.');">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_account">
            <h2>Close account</h2><p>Your account will be deactivated and you will be signed out.</p>
            <label for="delete-current-password">Current password</label><input id="delete-current-password" name="current_password" type="password" autocomplete="current-password" required>
            <button class="button button-danger" type="submit">Close my account</button>
        </form>
    </div></section>
<?php elseif ($page === 'forgot-password'): ?>
    <section class="section"><div class="container form-wrap"><p class="eyebrow">Account recovery</p><h1>Reset your password.</h1><p>Enter your account email and we will provide reset instructions if an active account matches.</p><?php $resetSuccess = flash('success'); if ($resetSuccess): ?><div class="alert success"><?= e($resetSuccess) ?></div><?php endif; ?><?php if ($resetErrors): ?><div class="alert error"><ul><?php foreach ($resetErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php if ($resetLink): ?><div class="alert success">Local development reset link: <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></div><?php endif; ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=forgot-password')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><label for="reset-email">Email address</label><input id="reset-email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required><button class="button" type="submit">Request reset link</button></form><p><a href="<?= e(url($config, '?page=login')) ?>">Return to sign in</a></p></div></section>
<?php elseif ($page === 'reset-password'): ?>
    <section class="section"><div class="container form-wrap"><p class="eyebrow">Account recovery</p><h1>Choose a new password.</h1><?php if ($resetErrors): ?><div class="alert error"><ul><?php foreach ($resetErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php if ($resetRecord): ?><form class="card form-card" method="post" action="<?= e(url($config, '?page=reset-password&token=' . rawurlencode((string) ($_GET['token'] ?? '')))) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="token" value="<?= e((string) ($_GET['token'] ?? '')) ?>"><label for="reset-password">New password</label><input id="reset-password" name="password" type="password" autocomplete="new-password" required><label for="reset-password-confirmation">Confirm new password</label><input id="reset-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><button class="button" type="submit">Set new password</button></form><?php else: ?><p class="alert error">That reset link is invalid or has expired. Request a new link to continue.</p><a class="button" href="<?= e(url($config, '?page=forgot-password')) ?>">Request another link</a><?php endif; ?></div></section>
<?php elseif ($page === 'verify-email'): ?>
    <section class="section"><div class="container form-wrap"><p class="eyebrow">Email verification</p><h1>Verify your email address.</h1><?php if ($verificationMessage): ?><div class="alert success"><?= e($verificationMessage) ?></div><a class="button" href="<?= e(url($config, '?page=login')) ?>">Continue to sign in</a><?php else: ?><p>Use the link from your CareerPath email to activate your account.</p><?php if ($verificationErrors): ?><div class="alert error"><ul><?php foreach ($verificationErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><a class="button button-quiet" href="<?= e(url($config, '?page=register')) ?>">Create a new account</a><?php endif; ?></div></section>
<?php elseif ($page === 'register' || $page === 'login'): ?>
    <section class="section"><div class="container form-wrap">
        <p class="eyebrow"><?= $page === 'register' ? 'Create your account' : 'Welcome back' ?></p>
        <h1><?= $page === 'register' ? 'Start building your path.' : 'Continue your journey.' ?></h1>
        <?php if ($dbError): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
        <?php if ($page === 'login' && ($_GET['deleted'] ?? '') === '1'): ?><div class="alert success">Your account has been closed.</div><?php endif; ?><?php $loginSuccess = $page === 'login' ? flash('success') : null; if ($loginSuccess): ?><div class="alert success"><?= e($loginSuccess) ?></div><?php endif; ?>
        <?php if ($page === 'register' && $verificationMessage): ?><div class="alert success"><?= e($verificationMessage) ?><?php if ($verificationLink): ?><br><a href="<?= e($verificationLink) ?>">Local development verification link</a><?php endif; ?></div><?php endif; ?>
        <?php if ($formErrors): ?><div class="alert error"><ul><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form class="card form-card" method="post" action="<?= e(url($config, '?page=' . $page)) ?>">
            <input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>">
            <?php if ($page === 'register'): ?>
                <label for="name">Name</label><input id="name" name="name" value="<?= old_input('name') ?>" autocomplete="name" required>
            <?php endif; ?>
            <label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old_input('email') ?>" autocomplete="email" required>
            <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="<?= $page === 'register' ? 'new-password' : 'current-password' ?>" required>
            <?php if ($page === 'register'): ?><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required><?php endif; ?>
            <button class="button" type="submit"><?= $page === 'register' ? 'Create account' : 'Sign in' ?></button>
        </form>
        <p><?= $page === 'register' ? 'Already have an account?' : 'Need an account?' ?> <a href="<?= e(url($config, '?page=' . ($page === 'register' ? 'login' : 'register'))) ?>"><?= $page === 'register' ? 'Sign in' : 'Create one' ?></a><?php if ($page === 'login'): ?> · <a href="<?= e(url($config, '?page=forgot-password')) ?>">Forgot password?</a><?php endif; ?></p>
    </div></section>
<?php elseif ($page === 'dashboard'): ?>
    <section class="section"><div class="container"><?php if ($goalErrors): ?><div class="alert error"><ul><?php foreach ($goalErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><?php $successMessage = flash('success'); if ($successMessage): ?><div class="alert success"><?= e($successMessage) ?></div><?php endif; ?><div class="section-heading"><div><p class="eyebrow">SMART career goals</p><h2>Turn intention into a next step.</h2></div></div><div class="goal-grid"><form class="card form-card" method="post" action="<?= e(url($config, '?page=dashboard')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="create_goal"><label for="goal-title">Goal title</label><input id="goal-title" name="title" maxlength="220" required value="<?= e($_POST['title'] ?? '') ?>"><label for="goal-description">How will you measure progress?</label><textarea id="goal-description" name="description" maxlength="5000" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea><label for="goal-target-date">Target date <span class="muted">(optional)</span></label><input id="goal-target-date" name="target_date" type="date" value="<?= e($_POST['target_date'] ?? '') ?>"><button class="button" type="submit">Add goal</button></form><div class="card"><?php if ($dashboardGoals): ?><?php foreach ($dashboardGoals as $goal): ?><div class="goal-item"><p class="eyebrow"><?= e($goal['status'] === 'completed' ? 'Completed' : 'Active') ?><?= $goal['target_date'] ? ' · ' . e($goal['target_date']) : '' ?></p><h3><?= e($goal['title']) ?></h3><p><?= nl2br(e($goal['description'])) ?></p><div class="goal-actions"><?php if ($goal['status'] === 'active'): ?><form method="post" action="<?= e(url($config, '?page=dashboard')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="complete_goal"><input type="hidden" name="goal_id" value="<?= e((string) $goal['id']) ?>"><button class="button button-small" type="submit">Mark complete</button></form><?php endif; ?><form method="post" action="<?= e(url($config, '?page=dashboard')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><input type="hidden" name="action" value="delete_goal"><input type="hidden" name="goal_id" value="<?= e((string) $goal['id']) ?>"><button class="button button-small button-quiet" type="submit">Remove</button></form></div></div><?php endforeach; ?><?php else: ?><p>No goals yet. Add one with a measurable outcome and target date.</p><?php endif; ?></div></div></div></section>
    <section class="page-hero"><div class="container"><p class="eyebrow">Your dashboard</p><h1>Welcome, <?= e(Auth::user()['name']) ?>.</h1><p>This is your workspace for saved careers, learning progress, goals, and assessment results.</p></div></section>
    <section class="section"><div class="container"><div class="cards three"><article class="card"><span class="icon">01</span><h3>Saved careers</h3><?php if ($dashboardSavedCareers): ?><?php foreach ($dashboardSavedCareers as $savedCareer): ?><p><strong><?= e($savedCareer['title']) ?></strong><br><?= e($savedCareer['summary']) ?></p><a href="<?= e(url($config, '?page=career&slug=' . rawurlencode($savedCareer['slug']))) ?>">View profile →</a><?php endforeach; ?><?php else: ?><p>Keep promising directions together while you compare your options.</p><?php endif; ?><a href="<?= e(url($config, '?page=careers')) ?>">Explore careers →</a></article><article class="card"><span class="icon">02</span><h3>Learning progress</h3><?php if ($dashboardPaths): ?><?php foreach ($dashboardPaths as $path): ?><p><strong><?= e($path['title']) ?></strong><br><?= e((string) $path['completed_lessons']) ?> of <?= e((string) $path['total_lessons']) ?> lessons complete</p><a href="<?= e(url($config, '?page=path&slug=' . rawurlencode($path['slug']))) ?>">Continue path →</a><?php endforeach; ?><?php else: ?><p>You have not enrolled in a learning path yet.</p><a href="<?= e(url($config, '?page=learning')) ?>">Browse learning paths →</a><?php endif; ?></article><article class="card"><span class="icon">03</span><h3>Bookmarked guides</h3><?php if ($dashboardBookmarks): ?><?php foreach ($dashboardBookmarks as $bookmark): ?><p><strong><?= e($bookmark['title']) ?></strong><br><?= e($bookmark['excerpt']) ?></p><a href="<?= e(url($config, '?page=article&slug=' . rawurlencode($bookmark['slug']))) ?>">Read guide →</a><?php endforeach; ?><?php else: ?><p>Save useful guides here while you build your career plan.</p><a href="<?= e(url($config, '?page=articles')) ?>">Browse guides →</a><?php endif; ?><hr><h3>Account</h3><p><?= e(Auth::user()['email']) ?></p><form method="post" action="<?= e(url($config, '?page=logout')) ?>"><input type="hidden" name="_csrf" value="<?= e(CSRF::token()) ?>"><button class="button button-quiet" type="submit">Sign out</button></form></article></div><div class="dashboard-insights"><article class="card streak-card"><p class="eyebrow">Learning consistency</p><strong><?= e((string) $learningStreak) ?> day<?= $learningStreak === 1 ? '' : 's' ?></strong><p><?= $learningStreak > 0 ? 'Keep your learning habit going.' : 'Complete a lesson today to start a streak.' ?></p></article><article class="card badge-card"><p class="eyebrow">Milestone badges</p><?php if ($dashboardBadges): ?><div class="badge-list"><?php foreach ($dashboardBadges as $badge): ?><span class="badge" title="<?= e($badge['description']) ?>"><?= e($badge['name']) ?></span><?php endforeach; ?></div><?php else: ?><p>Complete lessons to earn your first badge.</p><?php endif; ?></article></div><div class="dashboard-history"><div class="section-heading"><div><p class="eyebrow">Assessment history</p><h2>Review your earlier directions.</h2></div><a class="button button-small" href="<?= e(url($config, '?page=assessment')) ?>">Take assessment</a></div><?php if ($dashboardAssessments): ?><?php foreach ($dashboardAssessments as $historyItem): ?><article class="card history-item"><p class="eyebrow"><?= e($historyItem['title']) ?> · <?= e(date('F j, Y', strtotime($historyItem['created_at']))) ?></p><p><?php if ($historyItem['recommendations']): ?><?php foreach ($historyItem['recommendations'] as $recommendation): ?><a class="tag" href="<?= e(url($config, '?page=career&slug=' . rawurlencode((string) ($recommendation['slug'] ?? '')))) ?>"><?= e((string) ($recommendation['title'] ?? 'Career direction')) ?></a><?php endforeach; ?><?php else: ?>No recommendations were recorded for this result.<?php endif; ?></p></article><?php endforeach; ?><?php else: ?><div class="empty-state"><p>You have not completed the career assessment yet.</p><a href="<?= e(url($config, '?page=assessment')) ?>">Explore your directions →</a></div><?php endif; ?></div></div></section>
<?php else: ?>
    <section class="page-hero"><div class="container"><p class="eyebrow">404</p><h1>That page is not available.</h1><p>Return home and continue exploring.</p><a class="button" href="<?= e(url($config)) ?>">Return home</a></div></section>
<?php endif; ?>
</main>
<footer class="site-footer"><div class="container footer-grid"><div><a class="brand" href="<?= e(url($config)) ?>">Career<span>Path</span></a><p>Discover what you can become.</p></div><div><h2>Explore</h2><a href="<?= e(url($config, '?page=careers')) ?>">Careers</a><a href="<?= e(url($config, '?page=learning')) ?>">Learning paths</a></div><div><h2>Support</h2><a href="<?= e(url($config, '?page=about')) ?>">About us</a><a href="<?= e(url($config, '?page=contact')) ?>">Contact</a></div></div></footer>
</body>
</html>

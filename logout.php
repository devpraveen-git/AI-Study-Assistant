<?php
declare(strict_types=1);

require_once __DIR__ . '/app/Config/App.php';
require_once __DIR__ . '/app/Core/Auth.php';

use App\Core\Auth;

Auth::logout();
flash('info', 'You have been logged out successfully.');
redirect('login.php');
